<?php

namespace AwardWallet\MainBundle\Service\Notification;

use AwardWallet\MainBundle\Entity\Groupuserlink;
use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Translator\AbstractTranslatable;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\SocksMessaging\ClientInterface;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\Notification;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\Options;
use AwardWallet\MainBundle\Worker\PushNotification\LogHelper;
use Clock\ClockInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class Sender
{
    public const TRACKED_ON = 1;
    public const TRACKED_OFF = 2;
    public const TRACKED_ALL = 3;

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var EntityRepository
     */
    private $deviceRep;

    /**
     * @var TransformerInterface[]
     */
    private $transformers = [];

    /**
     * @var array
     */
    private $routes = [
        MobileDevice::TYPE_ANDROID => 'android_3',
        MobileDevice::TYPE_CHROME => 'android_3',
        MobileDevice::TYPE_FIREFOX => 'android_3',
        MobileDevice::TYPE_IOS => 'ios_3',
        MobileDevice::TYPE_SAFARI => 'ios_3',
        MobileDevice::TYPE_PUSHY_ANDROID => 'pushy_2',
    ];
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var ProducerInterface
     */
    private $producer;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var ClientInterface
     */
    private $chatClient;
    /**
     * @var LogHelper
     */
    private $logHelper;
    /**
     * @var LocalizeService
     */
    private $localizer;
    /**
     * @var ApiVersioningService
     */
    private $apiVersioning;
    /**
     * @var Spy
     */
    private $spy;
    /**
     * @var ProducerInterface
     */
    private $delayedProducer;
    private EventDispatcherInterface $eventDispatcher;
    private ClockInterface $clock;

    public function __construct(
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        LocalizeService $localizer,
        ApiVersioningService $apiVersionMobile,
        LoggerInterface $pushLogger,
        ProducerInterface $oldSoundRabbitMqPushNotificationProducer,
        ProducerInterface $oldSoundRabbitMqPushNotificationDelayedProducer,
        ClientInterface $chatClient,
        Spy $spy,
        EventDispatcherInterface $eventDispatcher,
        ClockInterface $clock
    ) {
        $this->logger = $pushLogger;
        $this->deviceRep = $entityManager->getRepository(\AwardWallet\MainBundle\Entity\MobileDevice::class);
        $this->entityManager = $entityManager;
        $this->producer = $oldSoundRabbitMqPushNotificationProducer;
        $this->delayedProducer = $oldSoundRabbitMqPushNotificationDelayedProducer;
        $this->translator = $translator;
        $this->chatClient = $chatClient;
        $this->logHelper = new LogHelper('debug');
        $this->localizer = $localizer;
        $this->apiVersioning = $apiVersionMobile;
        $this->spy = $spy;
        $this->eventDispatcher = $eventDispatcher;
        $this->clock = $clock;
    }

    public function addTransformer($deviceType, TransformerInterface $transformer)
    {
        $this->transformers[$deviceType] = $transformer;
    }

    /**
     * @param Usr[] $recipients
     * @param $contentType - one of Content::TYPE constants
     * @param $toPersonal - null|true|false - send push only to all | personal | business subscriptions
     * @return \AwardWallet\MainBundle\Entity\MobileDevice[]
     * @throws \Exception
     */
    public function loadDevices(array $recipients, array $deviceTypes, $contentType, $toPersonal = null, int $tracked = self::TRACKED_ON)
    {
        $recipients = $this->excludeOnlineUsers($recipients, $contentType);

        if (empty($recipients)) {
            return [];
        }

        return $this->getUserDevicesQuery($recipients, $deviceTypes, $contentType, $toPersonal, $tracked)->execute();
    }

    /**
     * @param Usr[] $recipients
     */
    public function getUserDevicesQuery(array $recipients, array $deviceTypes, $contentType, $toPersonal = null, int $tracked = self::TRACKED_ON)
    {
        $this->checkDeviceTypes($deviceTypes);

        $qb = $this->entityManager->createQueryBuilder();
        $expr = $qb->expr();

        $qb
            ->select('d, user')
            ->from(MobileDevice::class, 'd')
            ->join('d.userId', 'user')
            ->where(
                (self::TRACKED_ALL === $tracked) ?
                    $expr->in('d.deviceType', $deviceTypes) :
                    $expr->andX(
                        $expr->in('d.deviceType', $deviceTypes),
                        $expr->eq('d.tracked', $tracked === self::TRACKED_ON)
                    )
            );

        if (in_array($contentType, [Content::TYPE_BLOG_POST, Content::TYPE_OFFER, Content::TYPE_PRODUCT_UPDATES], true)) {
            $qb->leftJoin(
                Groupuserlink::class,
                'gul',
                'WITH',
                $expr->andX(
                    $expr->eq('gul.userid', 'd.userId'),
                    $expr->eq('gul.sitegroupid', 50)
                )
            )->andWhere($expr->isNull('gul.sitegroupid'));
        }

        if (!empty($recipients)) {
            $qb->andWhere($expr->in('d.userId', array_map(function (Usr $user) {
                return $user->getUserid();
            }, $recipients)));
        }

        if ($contentType === Content::TYPE_BOOKING) {
            $qb->leftJoin(
                Useragent::class,
                'ua',
                'WITH',
                $expr->andX(
                    $expr->eq('ua.agentid', 'd.userId'),
                    $expr->eq('ua.isapproved', 1),
                    $expr->in('ua.accesslevel', [ACCESS_ADMIN, ACCESS_BOOKING_MANAGER, ACCESS_BOOKING_VIEW_ONLY])
                )
            )->leftJoin(
                Usr::class,
                'business',
                'WITH',
                $expr->andX(
                    $expr->eq('business.userid', 'ua.clientid'),
                    $expr->eq('business.accountlevel', ACCOUNT_LEVEL_BUSINESS)
                )
            );
            $wpConditions = $expr->andX(
                $expr->in('d.deviceType', MobileDevice::TYPES_DESKTOP),
                $expr->orX(
                    $expr->isNull('business.userid'),
                    isset(Content::$wpUserFields[$contentType])
                        ? $expr->eq('user.' . Content::$wpUserFields[$contentType], 1)
                        : '1 = 1'
                )
            );
            $mpConditions = $expr->andX(
                $expr->in('d.deviceType', MobileDevice::TYPES_MOBILE),
                $expr->orX(
                    $expr->isNull('business.userid'),
                    isset(Content::$mpUserFields[$contentType])
                        ? $expr->eq('user.' . Content::$mpUserFields[$contentType], 1)
                        : '1 = 1'
                )
            );
        } else {
            $wpConditions = $expr->andX(
                $expr->in('d.deviceType', MobileDevice::TYPES_DESKTOP),
                $expr->eq('user.wpDisableAll', 0),
                isset(Content::$wpUserFields[$contentType])
                    ? $expr->eq('user.' . Content::$wpUserFields[$contentType], 1)
                    : '1 = 1'
            );
            $mpConditions = $expr->andX(
                $expr->in('d.deviceType', MobileDevice::TYPES_MOBILE),
                $expr->eq('user.mpDisableAll', 0),
                isset(Content::$mpUserFields[$contentType])
                    ? $expr->eq('user.' . Content::$mpUserFields[$contentType], 1)
                    : '1 = 1'
            );
        }

        $qb->andWhere(
            $expr->orX(
                $wpConditions,
                $mpConditions
            )
        );

        if ($contentType != Content::TYPE_BOOKING || $toPersonal) {
            $qb->andWhere($expr->neq('d.appVersion', "'web:business'"));
        } elseif ($toPersonal === false) {
            $qb->andWhere($expr->neq('d.appVersion', "'web:personal'"));
        }

        return $qb->getQuery();
    }

    public function getAnonymousDevicesQuery(array $deviceTypes, $toPersonal = null, int $tracked = self::TRACKED_ON)
    {
        $this->checkDeviceTypes($deviceTypes);

        $dql = "
            select 
                d 
            from AwardWallet\MainBundle\Entity\MobileDevice d 
            where 
                d.deviceType in (:deviceType) and 
                " . ((self::TRACKED_ALL === $tracked) ? "" : "d.tracked = " . (self::TRACKED_ON === $tracked) . ' and ') . "
                d.userId is null";

        if ($toPersonal) {
            $dql .= " and d.appVersion <> 'web:business'";
        } elseif ($toPersonal === false) {
            $dql .= " and d.appVersion <> 'web:personal'";
        }

        $parameters = [
            'deviceType' => $deviceTypes,
        ];

        return $this->entityManager->createQuery($dql)->setParameters($parameters);
    }

    /**
     * @param string[] $recipients
     * @param $contentType - one of Content::TYPE constants
     * @return \AwardWallet\MainBundle\Entity\MobileDevice[]
     * @throws \Exception
     */
    public function loadAnonymousDevices(array $recipients, array $deviceTypes, $contentType, int $tracked = self::TRACKED_ON)
    {
        if ($missing = array_diff($deviceTypes, array_keys($this->routes))) {
            throw new \Exception('No route for device types ' . implode(", ", $missing));
        }

        if ($missing = array_diff($deviceTypes, array_keys($this->transformers))) {
            $this->logger->critical(sprintf('No transformer for types ' . implode(", ", $missing)));
        }

        $dql = "
            select d from AwardWallet\MainBundle\Entity\MobileDevice d 
            where 
                d.userId is null 
                and d.ip in (:ip)  
                " . ((self::TRACKED_ALL === $tracked) ? "" : "and d.tracked = " . (self::TRACKED_ON === $tracked)) . "
                and d.deviceType in (:deviceType)";

        if ($contentType != Content::TYPE_BOOKING) {
            $dql .= " and d.appVersion <> 'web:business'";
        }

        $devices = $this->entityManager->createQuery($dql)->execute(['ip' => $recipients, 'deviceType' => $deviceTypes]);

        return $devices;
    }

    /**
     * @param MobileDevice[] $devices
     */
    public function send(Content $content, array $devices): bool
    {
        $currentTs = $this->clock->current()->getAsSecondsInt();

        if (
            $content->options
            && !\is_null($deadlineTs = $content->options->getDeadlineTimestamp())
            && ($currentTs >= $deadlineTs)
        ) {
            $this->logger->info("will not send message, hit deadline", $this->logHelper->getContextByContent($content));

            return false;
        }

        $isSent = false;

        /** @var MobileDevice $device */
        foreach (array_merge($devices, $this->spy->getPushCopyDevices($content->type, true)) as $device) {
            $this->logger->debug("sending push to device", ["MobileDeviceID" => $device->getMobileDeviceId()]);
            $deviceType = $device->getDeviceType();
            /** @var TransformedContent $content */
            $transformedContent = $this->transform($content, $deviceType, $device);

            if (empty($transformedContent)) {
                continue;
            }

            $this->sendToQueue($transformedContent, $deviceType, $device);
            $isSent = true;
        }

        return $isSent;
    }

    private function checkDeviceTypes(array $deviceTypes)
    {
        if ($missing = array_diff($deviceTypes, array_keys($this->routes))) {
            throw new \Exception('No route for device types ' . implode(", ", $missing));
        }

        if ($missing = array_diff($deviceTypes, array_keys($this->transformers))) {
            $this->logger->critical(sprintf('No transformer for types ' . implode(", ", $missing)));
        }
    }

    private function getContentTypeFieldFilterSQL(array $types, $contentType)
    {
        return isset($types[$contentType]) ? " user." . $types[$contentType] . " = 1" : " 1 = 1 ";
    }

    /**
     * @param Usr[] $recipients
     * @param int $contentType
     * @return Usr[]
     */
    private function excludeOnlineUsers(array $recipients, $contentType)
    {
        if (!in_array($contentType, [Content::TYPE_BOOKING], true)) {
            return $recipients;
        }

        return array_filter($recipients, function (Usr $user) use ($contentType) {
            $presence = $this->chatClient->presence("notifications_" . $contentType . "_" . $user->getUserid());
            $result = empty($presence[0]['body']['data']);

            if (!$result) {
                $this->logger->info("excluded user {$user->getUserid()} as online on notifications channel {$contentType}");
            }

            return $result;
        });
    }

    private function transform(Content $content, $deviceType, MobileDevice $device)
    {
        $transformer = $this->transformers[$deviceType];
        $translated = clone $content;
        $locale = $this->getLocaleByDevice($device);

        foreach ($content as $key => $value) {
            if ($value instanceof AbstractTranslatable) {
                $value->setLocale($locale);

                $translated->$key = $value->trans(/** @Ignore */ $this->translator);
            } else {
                $translated->$key = $value;
            }
        }

        return $transformer->transform($device, $translated);
    }

    /**
     * @return [pushLanguage, pushLocale]
     */
    private function getLocaleByDevice(MobileDevice $device)
    {
        $user = $device->getUser();

        if (
            // apply regional settings for registered users
            $user
            && (
                // apply regional settings to desktops and devices with newer mobile versions
                !in_array($device->getDeviceType(), MobileDevice::TYPES_MOBILE, true)
                || $this->apiVersioning->versionStringSupports($device->getAppVersion(), MobileVersions::REGIONAL_SETTINGS)
            )
        ) {
            $localeParts = [];

            if (!StringUtils::isEmpty($userlanguage = $user->getLanguage())) {
                $localeParts[] = $userlanguage;
            }

            if (!StringUtils::isEmpty($userRegion = $user->getRegion())) {
                $localeParts[] = $userRegion;
            }

            return \implode('_', $localeParts);
        } else {
            return $device->getLang();
        }
    }

    /**
     * @param int $deviceType
     */
    private function sendToQueue(TransformedContent $content, $deviceType, MobileDevice $device)
    {
        $routingKey = $this->routes[$deviceType];
        $notification = Notification::createFromTransformedContent(
            $content,
            $device,
            $routingKey,
            $this->clock->current()->getAsSecondsInt()
        );
        $options = $notification->getOptions();
        $serialized = @serialize($notification);

        if (strlen($serialized) < 20) {
            $this->logger->warning(sprintf('Serialization failed with message "%s"', $serialized), ['notification' => $notification]);

            return;
        }

        if ($options->hasFlag(Options::FLAG_DRY_RUN)) {
            $this->logger->warning('debug push notification was about to be sent', $this->logHelper->getContext($notification, ['_aw_push_debug' => 1]));

            return;
        }

        try {
            $delayedPublish = false;
            $this->logger->info("publishing push to device", [
                "MobileDeviceID" => $device->getMobileDeviceId(),
                "serialized" => $serialized, "routing" => $routingKey,
                "UserID" => $device->getUser() ? $device->getUser()->getId() : null,
            ]);
            $props = [];

            if (null !== ($priority = $options->getPriority())) {
                $props['priority'] = $priority;
            }

            if (null !== ($delay = $options->getDelay())) {
                $props['application_headers']['x-delay'] = ['I', $delay * 1000];
                $delayedPublish = true;
            }

            if ($delayedPublish) {
                $this->delayedProducer->publish($serialized, $routingKey, $props);
            } else {
                $this->producer->publish($serialized, $routingKey, $props);
            }
        } catch (\Exception $e) {
            $this->logger->warning(sprintf('Producer failed to enqueue. Exception "%s"', $e->getMessage()), ['UserID' => $device->getUser()->getUserid()]);
        }

        $this->eventDispatcher->dispatch(new PushEvent($notification->getUserId(), $notification->getMessage()));
    }
}
