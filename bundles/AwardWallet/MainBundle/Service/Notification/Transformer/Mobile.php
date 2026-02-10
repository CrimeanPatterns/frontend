<?php

namespace AwardWallet\MainBundle\Service\Notification\Transformer;

use AwardWallet\MainBundle\Entity\AbMessage;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Subaccount;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Event\PushNotification\Target\ItineraryUpdate;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\AlertTarget;
use AwardWallet\MainBundle\Service\LegacyUrlGenerator;
use AwardWallet\MainBundle\Service\Notification\Content;
use AwardWallet\MainBundle\Service\Notification\TransformedContent;
use AwardWallet\MainBundle\Service\Notification\TransformerInterface;
use AwardWallet\MainBundle\Service\Notification\Unsubscriber;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\Options;
use AwardWallet\MobileBundle\View\Booking\Messages\MessageCriterion;
use AwardWallet\MobileBundle\View\Booking\Messages\MessagesFormatter;
use Herrera\Version\Parser;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\lazy;

class Mobile implements TransformerInterface
{
    public const DATA_TYPE_TIMELINE = 'tl';
    public const DATA_TYPE_ACCOUNT = 'a';
    public const DATA_TYPE_BOOKING_MESSAGE = 'bm';
    public const DATA_TYPE_EXTERNAL = 'ex';
    public const DATA_TYPE_PHONE_NUMBER = 'tel';

    private TranslatorInterface $translator;

    private MessagesFormatter $bookingMessageFormatter;

    private Unsubscriber $unsubscriber;

    private ApiVersioningService $apiVersioning;

    private RouterInterface $router;

    private LegacyUrlGenerator $legacyUrlGenerator;

    private string $personalHost;

    private string $businessHost;

    public function __construct(
        TranslatorInterface $translator,
        MessagesFormatter $formatter,
        Unsubscriber $unsubscriber,
        ApiVersioningService $apiVersionMobile,
        RouterInterface $router,
        LegacyUrlGenerator $legacyUrlGenerator,
        string $host,
        string $businessHost
    ) {
        $this->translator = $translator;
        $this->bookingMessageFormatter = $formatter;
        $this->unsubscriber = $unsubscriber;
        $this->apiVersioning = $apiVersionMobile;
        $this->router = $router;
        $this->legacyUrlGenerator = $legacyUrlGenerator;
        $this->personalHost = $host;
        $this->businessHost = $businessHost;
    }

    public function transform(MobileDevice $device, Content $content): ?TransformedContent
    {
        $title = $content->title;
        $message = $content->message;
        $target = $content->target;

        if (\is_string($target)) {
            $target = TransformerUtils::transformRefcode($device->getUser(), $target);
        }

        $type = $content->type;
        $payload = [];
        $debugUrl = null;
        $debugHost = null;
        $version = lazy(function () use ($device) { return Parser::toVersion($device->getAppVersion()); });
        $supports = function (string $feature) use ($version) { return $this->apiVersioning->versionSupports($version(), $feature); };
        $isUrlFormat = lazy(function () use ($supports) { return $supports(MobileVersions::PUSH_NOTIFICATIONS_URL_FORMAT); });

        if (in_array($type, [Content::TYPE_BLOG_POST, Content::TYPE_PRODUCT_UPDATES, Content::TYPE_OFFER])) {
            $debugUrl = $target;
            $payload = [self::DATA_TYPE_EXTERNAL => $this->unsubscriber->addUnsubscribeCode($device, 'https://awardwallet.com' . $target)];

            if (!empty($title) && !$supports(MobileVersions::LOCATION_STORAGE)) {
                $message = sprintf("%s %s", $title, $message);
                $title = null;
            }
        } elseif (
            (Content::TYPE_HOTEL_PHONE === $type)
            && ($target instanceof AlertTarget)
        ) {
            if ($supports(MobileVersions::PHONE_CALL_FROM_NOTIFICATION)) {
                $payload = [self::DATA_TYPE_PHONE_NUMBER => $target->data];
            } else {
                return null;
            }
        } elseif (!empty($target)) {
            if (is_object($target)) {
                $targetClass = get_class($target);

                if ($target instanceof Account) {
                    $debugUrl = '/m/account/details/a' . $target->getAccountid();

                    if ($isUrlFormat()) {
                        $payload = [self::DATA_TYPE_EXTERNAL => $this->legacyUrlGenerator->generateAbsoluteUrl($debugUrl)];
                    } else {
                        $payload = [self::DATA_TYPE_ACCOUNT => 'a' . $target->getAccountid()];
                    }
                }

                if ($target instanceof Subaccount) {
                    $debugUrl = '/m/account/details/a' . $target->getAccountid()->getAccountid() . '/' . $target->getSubaccountid();

                    if ($isUrlFormat()) {
                        $payload = [self::DATA_TYPE_EXTERNAL => $this->legacyUrlGenerator->generateAbsoluteUrl($debugUrl)];
                    } else {
                        $payload = [self::DATA_TYPE_ACCOUNT => 'a' . $target->getAccountid()->getAccountid() . "." . $target->getSubaccountid()];
                    }
                }

                if ($target instanceof Providercoupon) {
                    $debugUrl = "/m/account/details/c" . $target->getProvidercouponid() . "/";

                    if ($isUrlFormat()) {
                        $payload = [self::DATA_TYPE_EXTERNAL => $this->legacyUrlGenerator->generateAbsoluteUrl($debugUrl)];
                    } else {
                        $payload = [self::DATA_TYPE_ACCOUNT => 'c' . $target->getProvidercouponid()];
                    }
                }

                if ($target instanceof AlertTarget) {
                    $target = $target->tripSegment;
                }

                if ($target instanceof Tripsegment) {
                    [$id, $debugUrl] = $this->getTimelineRoute($target);

                    if ($isUrlFormat()) {
                        $payload = [self::DATA_TYPE_EXTERNAL => $this->legacyUrlGenerator->generateAbsoluteUrl($debugUrl)];
                    } else {
                        $payload = [self::DATA_TYPE_TIMELINE => [$id]];
                    }
                }

                if ($target instanceof ItineraryUpdate) {
                    [$id, $debugUrl] = $this->getTimelineRoute($target->getEntity());
                    $targetClass = get_class($target->getEntity());

                    if ($isUrlFormat()) {
                        $payload = [self::DATA_TYPE_EXTERNAL => $this->legacyUrlGenerator->generateAbsoluteUrl($debugUrl)];
                    } else {
                        $payload = [self::DATA_TYPE_TIMELINE => [$id]];
                    }
                }

                if ($target instanceof AbMessage) {
                    $abRequest = $target->getRequest();
                    $viewer = $target->getRequest()->getUser();
                    $formatted = $this->bookingMessageFormatter->formatMessage($target, new MessageCriterion($abRequest, $viewer), true);
                    // filter tags
                    $message = trim(StringHandler::strLimit(str_replace(["\n", "\r"], ' ', strip_tags($formatted->body)), 100));

                    if ('' === $message) {
                        $message = $this->translator->trans('have.new.message', [], 'mobile');
                    }
                    $message = "{$formatted->author}:\r\n$message";

                    if ($target->getFromBooker()) {
                        $debugHost = $this->personalHost;
                        $debugUrl = '/m/booking/' . $target->getRequestID()->getAbRequestID() . '/details';
                    } else {
                        $debugHost = $this->businessHost;
                        $debugUrl = $this->router->generate('aw_booking_view_index', ['id' => $target->getRequestID()->getAbRequestID()]) . '#' . $target->getAbMessageID();
                    }

                    $bmContext = [
                        'internalDate' => $formatted->getInternalDate(),
                        'source' => 'remote',
                    ];

                    if ($isUrlFormat()) {
                        $payload = \array_merge(
                            $bmContext,
                            [self::DATA_TYPE_EXTERNAL => $this->legacyUrlGenerator->generateAbsoluteUrl($debugUrl)]
                        );
                    } else {
                        $payload = \array_merge(
                            $bmContext,
                            [self::DATA_TYPE_BOOKING_MESSAGE => $target->getRequestID()->getAbRequestID() . '.' . $target->getAbMessageID()]
                        );
                    }
                }

                if (empty($payload)) {
                    throw new \Exception("Unknown target type: " . get_class($target));
                }
            } else {
                switch ($target) {
                    case Content::TARGET_PAY:
                        // @TODO: VKurdin, please add jump to /user/pay
                        break;

                    default:
                        throw new \Exception("Unknown target type: " . $target);
                }
            }
        }

        $payload['_ts'] = time();

        if (!empty($title)) {
            $payload['title'] = $title;
        }

        if (in_array($device->getDeviceType(), [MobileDevice::TYPE_ANDROID])) {
            $payload['channel'] = $payload['channelId'] = Content::ANDROID_CHANNELS[$type] ?? null;
        }

        if (isset($debugUrl)) {
            $context = $this->router->getContext();
            $debugUrl = sprintf(
                "%s://%s%s",
                $context->getScheme(),
                $debugHost ?? $context->getHost(),
                $debugUrl
            );
        }
        $options = $content->options ?: new Options();
        $options->setLogContext(array_merge(
            $options->getLogContext(),
            [
                'debug' => [
                    'title' => $title,
                    'message' => $message,
                    'url' => $debugUrl,
                ],
                '_aw_push_subtype' => (isset($targetClass) && ($classParts = explode('\\', $targetClass))) ?
                    end($classParts) : null,
            ]
        ));

        return new TransformedContent(
            $message,
            $payload,
            Content::getTypeName($type),
            $options
        );
    }

    /**
     * @param Itinerary|Tripsegment $entity
     * @return array (id, url)
     */
    private function getTimelineRoute($entity)
    {
        $kind = TransformerUtils::getTimelineKindByEntity($entity);
        $ua = 'my';

        if ($entity instanceof Itinerary) {
            if ($agent = $entity->getUserAgent()) {
                $ua = $agent->getUseragentid();
            }

            return [
                $ua . '.' . $kind . '.' . $entity->getId(),
                "/m/timeline/{$ua}/{$kind}/" . $entity->getId() . "/details",
            ];
        } elseif ($entity instanceof Tripsegment) {
            if ($agent = $entity->getTripid()->getUserAgent()) {
                $ua = $agent->getUseragentid();
            }

            return [
                $ua . '.' . $kind . '.' . $entity->getTripsegmentid(),
                "/m/timeline/{$ua}/{$kind}/" . $entity->getTripsegmentid() . "/details",
            ];
        } else {
            throw new \RuntimeException(sprintf('Unknown entity type: "%s"', get_class($entity)));
        }
    }
}
