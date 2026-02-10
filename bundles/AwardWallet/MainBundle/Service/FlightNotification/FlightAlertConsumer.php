<?php

namespace AwardWallet\MainBundle\Service\FlightNotification;

use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Entity\Repositories\TripsegmentRepository;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Translator\Trans;
use AwardWallet\MainBundle\FrameworkExtension\Translator\TransChoice;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\LogProcessor;
use AwardWallet\MainBundle\Service\Notification\Content;
use AwardWallet\MainBundle\Service\Notification\Sender;
use AwardWallet\MainBundle\Service\TaskScheduler\ConsumerInterface;
use AwardWallet\MainBundle\Service\TaskScheduler\Producer;
use AwardWallet\MainBundle\Service\TaskScheduler\TaskInterface;
use AwardWallet\MainBundle\Timeline\TripInfo\TripInfo;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\InterruptionLevel;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\Options;
use Clock\ClockInterface;
use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\ORM\EntityManagerInterface;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Monolog\Handler\PsrHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class FlightAlertConsumer implements ConsumerInterface, TranslationContainerInterface
{
    private OffsetHandler $offsetHandler;

    private QueueLocker $queueLocker;

    private LoggerInterface $logger;

    private EntityManagerInterface $em;

    private Sender $pushSender;

    private Producer $producer;

    private LegSegmentDetector $legSegmentDetector;

    private TripsegmentRepository $tsRep;

    private ClockInterface $clock;

    public function __construct(
        OffsetHandler $offsetHandler,
        QueueLocker $queueLocker,
        LoggerInterface $logger,
        EntityManagerInterface $em,
        Sender $pushSender,
        Producer $producer,
        LegSegmentDetector $legSegmentDetector,
        TripsegmentRepository $tsRep,
        ClockInterface $clock
    ) {
        $this->offsetHandler = $offsetHandler;
        $this->queueLocker = $queueLocker;
        $logProcessor = new LogProcessor('flight_notification_worker', [], [], ['ts:%d!ts', 'target', 'email']);
        $this->logger = new Logger('flight_notification', [new PsrHandler($logger)], [$logProcessor]);
        $this->em = $em;
        $this->pushSender = $pushSender;
        $this->producer = $producer;
        $this->legSegmentDetector = $legSegmentDetector;
        $this->tsRep = $tsRep;
        $this->clock = $clock;
    }

    /**
     * @param FlightAlertTask $task
     */
    public function consume(TaskInterface $task): void
    {
        $now = $this->clock->current()->getAsDateTime();
        $tripSegment = $this->tsRep->find($segmentId = $task->getSegmentId());
        $context = ['ts' => $segmentId];

        if (is_null($tripSegment)) {
            $this->logger->info('not found in the db', $context);

            return;
        }

        /** @var Trip $trip */
        $trip = $tripSegment->getTripid();

        if ($trip->getCategory() != TRIP_CATEGORY_AIR) {
            $this->logger->debug('notification for flights only', $context);

            return;
        }

        if ($trip->getHidden() || $tripSegment->getHidden()) {
            $this->logger->debug('hidden trip or segment', $context);

            return;
        }

        $user = $trip->getUser();
        $fm = $trip->getUserAgent();
        $statuses = $this->offsetHandler->getOffsetsStatusesBySegment($tripSegment, $now);

        try {
            if (!($depGeotag = $tripSegment->getDepgeotagid())) {
                $this->logger->info('empty dep geotag', $context);

                return;
            }

            if (!($arrGeotag = $tripSegment->getArrgeotagid())) {
                $this->logger->info('empty arr geotag', $context);

                return;
            }

            if (StringHandler::isEmpty($depCountry = $depGeotag->getCountryCode())) {
                $this->logger->info('empty dep country', $context);

                return;
            }

            if (StringHandler::isEmpty($arrCountry = $arrGeotag->getCountryCode())) {
                $this->logger->info('empty arr country', $context);

                return;
            }

            foreach ($statuses as $k => $status) {
                if ($status->getSendingDelay() > 0) {
                    $this->logger->info(sprintf('early notification {%s}, skip', $status), $context);
                    $this->queueLocker->release($tripSegment, $status);
                    unset($statuses[$k]);
                }
            }

            if (count($statuses) === 0) {
                $this->logger->info('nothing to send', $context);

                return;
            }

            if (!$this->legSegmentDetector->isLegSegment($tripSegment, $depCountry === $arrCountry ? 4 : 12)) {
                $this->logger->info('is not leg segment', $context);

                return;
            }

            $q = $this->findRecipients($segmentId);
            $recipients = 0;
            $push = 0;
            $email = 0;

            while ($row = $q->fetchAssociative()) {
                $recipients++;
                $recepientContext = array_merge($context, ['target' => $row['Target'], 'email' => $row['Email']]);
                $copy = (bool) $row['Copy'];
                $toUser = $row['Target'] === 'U';

                $this->logger->info(sprintf('process target%s', $copy ? ', copy' : ''), $recepientContext);

                foreach ($statuses as $k => $status) {
                    if (!is_null(NotificationDate::getDate($tripSegment, $status->getKind()))) {
                        $this->logger->info(sprintf('message {%s} has already been sent, executor, skip', $status), $recepientContext);
                        $this->queueLocker->release($tripSegment, $status);
                        unset($statuses[$k]);
                    }
                }

                if (!$copy && $toUser) {
                    foreach ($statuses as $status) {
                        if ($status->hasCategory(OffsetHandler::CATEGORY_PUSH)) {
                            if ($this->sendPush($user, $tripSegment, $status, $now, $recepientContext)) {
                                $push++;
                            }
                        }
                    }
                }

                if ($user->getCheckinreminder()) {
                    foreach ($statuses as $status) {
                        if ($status->hasCategory(OffsetHandler::CATEGORY_MAIL)) {
                            if ($this->sendEmail($user, $fm, $tripSegment, $status, $row)) {
                                $email++;
                            }
                        }
                    }
                } else {
                    $this->logger->info('user has disabled setting "checkin"', $recepientContext);
                }
            }

            if ($recipients === 0) {
                $this->logger->info('recipients not found', $context);
            } else {
                $this->logger->info(sprintf('sent %d emails, push: %d', $email, $push), $context);
            }
        } finally {
            foreach ($statuses as $status) {
                $this->queueLocker->release($tripSegment, $status);
            }
        }
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message('push-notifications.flight-reminder.normal.flight-number'))
                ->setDesc("%airline% flight %flight-number% from %dep-code% to %arr-code% is departing in %time-interval%; time to check in!"),

            (new Message('push-notifications.flight-reminder.normal.flight-number.terminal'))
                ->setDesc("%airline% flight %flight-number% from %dep-code% to %arr-code% is departing in %time-interval% from Terminal %terminal%; time to check in!"),

            (new Message('push-notifications.flight-reminder.normal'))
                ->setDesc("%airline% flight from %dep-code% to %arr-code% is departing in %time-interval%; time to check in!"),

            (new Message('push-notifications.flight-reminder.normal.terminal'))
                ->setDesc("%airline% flight from %dep-code% to %arr-code% is departing in %time-interval% from Terminal %terminal%; time to check in!"),

            (new Message('push-notifications.flight-reminder.urgent'))
                ->setDesc("%airline% flight from %dep-code% to %arr-code% is departing in %time-interval%."),

            (new Message('push-notifications.flight-reminder.urgent.terminal'))
                ->setDesc("%airline% flight from %dep-code% to %arr-code% is departing in %time-interval% from Terminal %terminal%."),

            (new Message('push-notifications.flight-reminder.urgent.terminal.gate'))
                ->setDesc("%airline% flight from %dep-code% to %arr-code% is departing in %time-interval% from Terminal %terminal%, Gate %gate%."),

            (new Message('push-notifications.flight-reminder.urgent.gate'))
                ->setDesc("%airline% flight from %dep-code% to %arr-code% is departing in %time-interval% from Gate %gate%."),

            (new Message('push-notifications.flight-reminder.urgent.flight-number'))
                ->setDesc("%airline% flight %flight-number% from %dep-code% to %arr-code% is departing in %time-interval%."),

            (new Message('push-notifications.flight-reminder.urgent.flight-number.terminal'))
                ->setDesc("%airline% flight %flight-number% from %dep-code% to %arr-code% is departing in %time-interval% from Terminal %terminal%."),

            (new Message('push-notifications.flight-reminder.urgent.flight-number.terminal.gate'))
                ->setDesc("%airline% flight %flight-number% from %dep-code% to %arr-code% is departing in %time-interval% from Terminal %terminal%, Gate %gate%."),

            (new Message('push-notifications.flight-reminder.urgent.flight-number.gate'))
                ->setDesc("%airline% flight %flight-number% from %dep-code% to %arr-code% is departing in %time-interval% from Gate %gate%."),

            (new Message('push-notifications.pre-flight-reminder'))
                ->setDesc("Get ready to check in to your %airline% flight from %dep-code% to %arr-code% in %minutes%!"),

            (new Message('push-notifications.pre-flight-reminder.flight-number'))
                ->setDesc("Get ready to check in to your %airline% flight %flight-number% from %dep-code% to %arr-code% in %minutes%!"),
        ];
    }

    private function findRecipients(int $id)
    {
        return $this->em->getConnection()->executeQuery("
            SELECT
                'U' AS Target,
                u.Email,
                IF(t.UserAgentID IS NULL, 0, 1) AS Copy
            FROM
                TripSegment ts
                JOIN Trip t ON ts.TripID = t.TripID
                JOIN Usr u ON t.UserID = u.UserID
            WHERE
                ts.TripSegmentID = :id
                AND t.Hidden = 0
                AND ts.Hidden = 0
                AND t.Category = :category
                AND (t.UserAgentID IS NULL OR u.EmailFamilyMemberAlert = 1)
                AND u.AccountLevel <> :business
                            
            UNION
    
            SELECT
                'UA' AS Target,
                ua.Email,
                0 AS Copy
            FROM
                TripSegment ts
                JOIN Trip t ON ts.TripID = t.TripID
                JOIN Usr u ON t.UserID = u.UserID
                LEFT JOIN UserAgent ua ON t.UserAgentID = ua.UserAgentID
            WHERE
                ts.TripSegmentID = :id
                AND t.Hidden = 0
                AND ts.Hidden = 0
                AND t.Category = :category
                AND t.UserAgentID IS NOT NULL
                AND ua.SendEmails = 1
                AND ua.Email <> ''
                AND (u.AccountLevel = :business OR u.EmailFamilyMemberAlert = 0 OR u.Email <> ua.Email)
        ", [
            'id' => $id,
            'category' => TRIP_CATEGORY_AIR,
            'business' => ACCOUNT_LEVEL_BUSINESS,
        ], [
            'id' => \PDO::PARAM_INT,
            'category' => \PDO::PARAM_INT,
            'business' => \PDO::PARAM_INT,
        ]);
    }

    private function sendPush(Usr $user, Tripsegment $tripSegment, OffsetStatus $status, \DateTime $now, array $context): bool
    {
        $this->logger->info(sprintf('trying send push, {%s}', $status), $context);

        if (StringHandler::isEmpty($depCode = $tripSegment->getDepcode())) {
            $this->logger->info('empty depcode', $context);

            return false;
        }

        if (StringHandler::isEmpty($arrCode = $tripSegment->getArrcode())) {
            $this->logger->info('empty arrcode', $context);

            return false;
        }

        if (StringHandler::isEmpty($airlineName = $this->resolveAirline($tripSegment))) {
            $this->logger->info('empty airline name', $context);

            return false;
        }

        $transParams = [
            '%dep-code%' => $depCode,
            '%arr-code%' => $arrCode,
            '%time-interval%' => new TransChoice('hours', $status->getOffsetHours(), ['%count%' => $status->getOffsetHours()], 'messages'),
            '%airline%' => $airlineName,
        ];
        $flightNumber = $this->getFlightNumber($tripSegment);
        $options = (new Options())
            ->setPriority(5)
            ->setInterruptionLevel(InterruptionLevel::TIME_SENSITIVE);

        switch ($status->getKind()) {
            case OffsetHandler::KIND_BOARDING:
                $contentType = Content::TYPE_FLIGHT_BOARDING;
                $title = new Trans(/** @Desc("Boarding reminder") */ 'boarding_reminder.title');
                $key = 'push-notifications.flight-reminder.urgent';
                $optionalTransParams = [
                    'flight-number' => $flightNumber,
                    'terminal' => $tripSegment->getDepartureTerminal(),
                    'gate' => $tripSegment->getDepartureGate(),
                ];

                break;

            case OffsetHandler::KIND_DEPARTURE:
                $contentType = Content::TYPE_FLIGHT_DEPARTURE;
                $title = new Trans(/** @Desc("Departure reminder") */ 'departure_reminder.title');
                $key = 'push-notifications.flight-reminder.urgent';
                $optionalTransParams = [
                    'flight-number' => $flightNumber,
                    'terminal' => $tripSegment->getDepartureTerminal(),
                    'gate' => $tripSegment->getDepartureGate(),
                ];

                break;

            case OffsetHandler::KIND_CHECKIN:
                $contentType = Content::TYPE_CHECKIN_REMINDER;
                $title = new Trans(/** @Desc("Flight Check-in Reminder") */ 'checkin_reminder.title');
                $key = 'push-notifications.flight-reminder.normal';
                $optionalTransParams = [
                    'flight-number' => $flightNumber,
                    'terminal' => $tripSegment->getDepartureTerminal(),
                ];

                break;

            case OffsetHandler::KIND_PRECHECKIN:
                $contentType = Content::TYPE_PRECHECKIN_REMINDER;
                $title = new Trans(/** @Desc("Flight Check-in Reminder") */ 'checkin_reminder.title');
                $key = 'push-notifications.pre-flight-reminder';
                $optionalTransParams = [
                    'flight-number' => $flightNumber,
                ];
                $nextStatus = $status->getNextStatus();

                if (is_null($nextStatus)) {
                    $this->logger->critical('empty next offset status', $context);
                } elseif ($nextStatus->getKind() !== OffsetHandler::KIND_CHECKIN) {
                    $this->logger->critical(sprintf('wrong type "%s" of offset', $nextStatus->getKind()), $context);
                } else {
                    $offset = max(1, floor(($nextStatus->getTimestamp() - $now->getTimestamp()) / 60));
                    $transParams['%minutes%'] = new Trans('minutes', ['%count%' => $offset]);
                }

                break;

            default:
                $this->logger->error(sprintf('wrong offset type "%s"', $status->getKind()), $context);

                return false;
        }

        $options->setDeadlineTimestamp(
            $now->getTimestamp()
            + ($status->getOffset() - $status->getDeadline())
            + $status->getSendingDelay()
        );
        $devices = $this->pushSender->loadDevices([$user], MobileDevice::TYPES_ALL, $contentType);

        if (!$devices) {
            $this->logger->info(sprintf('no devices, user: #%d', $user->getId()), $context);

            return false;
        }

        $message = $this->generateVaryingTrans(
            $key,
            $transParams,
            $optionalTransParams
        );

        $isSent = $this->pushSender->send(new Content($title, $message, $contentType, $tripSegment, $options), $devices);
        $field = NotificationDate::getField($status->getKind());

        if ($isSent && !is_null($field)) {
            for ($attempt = 0; $attempt < 3; $attempt++) {
                try {
                    $this->em->getConnection()->update(
                        'TripSegment',
                        [$field => $this->clock->current()->getAsDateTime()->format('Y-m-d H:i:s')],
                        ['TripSegmentID' => $tripSegment->getTripsegmentid()]
                    );

                    break;
                }
                // handle LockWaitTimeoutException
                catch (RetryableException $exception) {
                    $this->logger->notice("RetryableException: " . $exception->getMessage());
                    sleep(random_int(1, 5));
                }
            }
        }

        return $isSent;
    }

    private function sendEmail(
        Usr $user,
        ?Useragent $fm,
        Tripsegment $tripSegment,
        OffsetStatus $status,
        array $recepient
    ): bool {
        $this->producer->publish(new FlightEmailAlertTask(
            $tripSegment->getId(),
            $user->getId(),
            $fm ? $fm->getId() : null,
            $status->getProviderId(),
            (bool) $recepient['Copy'],
            $status->getKind()
        ));

        return true;
    }

    private function resolveAirline(Tripsegment $tripSegment): ?string
    {
        $tripInfo = TripInfo::createFromTripSegment($tripSegment);

        if (isset($tripInfo->primaryTripNumberInfo->companyInfo) && !empty($companyName = $tripInfo->primaryTripNumberInfo->companyInfo->companyName)) {
            return $companyName;
        }

        return null;
    }

    private function getFlightNumber(Tripsegment $tripSegment)
    {
        if (
            !StringHandler::isEmpty($flightNumber = $tripSegment->getFlightNumber())
            && !StringHandler::isEmpty(preg_replace('/[^0-9]/', '', $flightNumber))
        ) {
            return $flightNumber;
        }

        return null;
    }

    private function generateVaryingTrans($key, array $transParams, array $optionalTransParams = [], $keyDelimiter = '.'): Trans
    {
        foreach ($optionalTransParams as $transParamKey => $transParamValue) {
            if (StringHandler::isEmpty($transParamValue)) {
                continue;
            }

            $key .= "{$keyDelimiter}{$transParamKey}";
            $transParams["%{$transParamKey}%"] = $transParamValue;
        }

        return new Trans(/** @Ignore */ $key, $transParams);
    }
}
