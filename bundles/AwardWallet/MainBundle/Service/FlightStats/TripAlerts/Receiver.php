<?php

namespace AwardWallet\MainBundle\Service\FlightStats\TripAlerts;

use AwardWallet\Common\Memcached\Item;
use AwardWallet\Common\Memcached\Util;
use AwardWallet\Common\TimeCommunicator;
use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\FrameworkExtension\Translator\Trans;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Loyalty\Resources\Itineraries\FlightSegment;
use AwardWallet\MainBundle\Service\BackgroundCheckScheduler;
use AwardWallet\MainBundle\Service\DateTimeInterval\Formatter as DateTimeIntervalFormatter;
use AwardWallet\MainBundle\Service\FlightStats\AirlineConverter;
use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\API\Model\Alert;
use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\API\Model\AlertDetails;
use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\API\Model\FlightStatus;
use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\API\Model\FlightStatusDetail;
use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\API\Model\FlightWithStatus;
use AwardWallet\MainBundle\Service\Notification\Content;
use AwardWallet\MainBundle\Service\Notification\Sender;
use AwardWallet\MainBundle\Service\Overlay\Writer;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\InterruptionLevel;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\Options;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class Receiver implements TranslationContainerInterface
{
    public const SOURCE = 'fs.alerts';

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var Writer
     */
    private $overlayWriter;
    /**
     * @var AirlineConverter
     */
    private $airlineConverter;
    /**
     * @var Sender
     */
    private $sender;
    /**
     * @var LocalizeService
     */
    private $localizer;
    /**
     * @var EntityRepository
     */
    private $tripSegmentsRepo;
    /**
     * @var UsrRepository
     */
    private $usrRepo;
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var Util
     */
    private $memcachedUtil;
    /**
     * @var Statement
     */
    private $tzQuery;
    /**
     * @var BackgroundCheckScheduler
     */
    private $checkScheduler;
    /**
     * @var TimeCommunicator
     */
    private $timeCommunicator;

    /**
     * @var DateTimeIntervalFormatter
     */
    private $intervalFormatter;
    private \Memcached $memcached;

    public function __construct(
        LoggerInterface $logger,
        Writer $overlayWriter,
        AirlineConverter $airlineConverter,
        Sender $sender,
        LocalizeService $localizer,
        EntityManagerInterface $em,
        TranslatorInterface $translator,
        Util $memcachedUtil,
        BackgroundCheckScheduler $checkScheduler,
        TimeCommunicator $timeCommunicator,
        DateTimeIntervalFormatter $intervalFormatter,
        \Memcached $memcached
    ) {
        $this->logger = $logger;
        $this->overlayWriter = $overlayWriter;
        $this->airlineConverter = $airlineConverter;
        $this->sender = $sender;
        $this->localizer = $localizer;
        $this->tripSegmentsRepo = $em->getRepository(\AwardWallet\MainBundle\Entity\Tripsegment::class);
        $this->usrRepo = $em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);
        $this->connection = $em->getConnection();
        $this->translator = $translator;
        $this->memcachedUtil = $memcachedUtil;
        $this->tzQuery = $this->connection->prepare("select TimeZoneLocation from AirCode where AirCode = :airCode");
        $this->checkScheduler = $checkScheduler;
        $this->timeCommunicator = $timeCommunicator;
        $this->intervalFormatter = $intervalFormatter;
        $this->memcached = $memcached;
    }

    /**
     * process alert received from flightstats and save it to overlays.
     *
     * alert samples:
     * https://developer.flightstats.com/api-docs/trips/alert-samples
     *
     * @param array $alert
     */
    public function process($userId, Alert $alert)
    {
        if (empty($alert->getAlertDetails()->getType())) {
            $this->logger->warning("invalid flightstats alert format", ["alert" => $alert]);

            return;
        }

        $details = $alert->getAlertDetails();
        $flightIndex = $details->getFlightIndex();

        if ($flightIndex === null) {
            $flightIndex = $this->getFlightIndex($details);
        }

        if (
            !isset($alert->getTrip()->getLegs()[$details->getLegIndex()])
            || !isset($alert->getTrip()->getLegs()[$details->getLegIndex()]->getFlights()[$flightIndex])
        ) {
            $this->logger->warning("invalid flighstats legIndex/flightIndex");

            return;
        }
        $flight = $alert->getTrip()->getLegs()[$details->getLegIndex()]->getFlights()[$flightIndex];

        if (empty($flight->getBookedAirlineIataCode()) && null === $this->airlineConverter->FSCodeToIata($flight->getBookedAirlineCode())) {
            $this->logger->warning("flightstats airline code not found", ["alert" => $alert]);

            return;
        }
        $this->logger->info("processing flight", ["provider" => $flight->getBookedAirlineCode(), "flightNumber" => $flight->getFlightNumber()]);

        $statuses = $flight->getFlightStatuses();

        if (empty($statuses)) {
            $this->logger->warning("no flight statuses", ["alert" => $alert]);

            return;
        }
        /** @var FlightStatus $status */
        $status = array_shift($statuses);

        if (!empty($statuses)) {
            $this->logger->warning("more than one flightstatus", ["alert" => $alert]);

            return;
        }

        /** @var ReceiverProcessResponse $processResponse */
        $method = "process" . str_replace(" ", "", $alert->getAlertDetails()->getType());

        if (method_exists($this, $method)) {
            $processResponse = call_user_func([$this, $method], $userId, $alert, $details, $flight, $status);
        } else {
            $processResponse = $this->extractFlightStatusInfo($status);
        }

        if (!$this->isNewFlightUpdate($alert->getAlertDetails()->getType(), $flight, $details)) {
            return;
        }

        $this->overlayWriter->updateOverlay($flight, $processResponse->segment, self::SOURCE);
        /** @var Tripsegment[] $tripSegments */
        $tripSegments = $this->tripSegmentsRepo->findByTripAlertsFlight($flight);

        if (count($tripSegments) > 1) {
            $this->logger->warning("more than one tripsegment", ["alert" => $alert]);
        }
        $sentPushesToUsers = [];

        foreach ($tripSegments as $tripSegment) {
            $changedProperties = $this->overlayWriter->updateTripSegment($tripSegment, $processResponse->segment);

            if (null !== $processResponse->hideSegment) {
                if ($processResponse->hideSegment) {
                    $tripSegment->cancel();
                } else {
                    $tripSegment->unhide();
                }
            }

            if (!in_array($tripSegment->getUser()->getId(), $sentPushesToUsers)) {
                $this->sendPush($tripSegment, $changedProperties, $processResponse);
                $sentPushesToUsers[] = $tripSegment->getUser()->getId();
            }
        }
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message('push-notifications.flight-delay.title'))
                ->setDesc("Flight Delay!"),
            (new Message('push-notifications.flight-delay.body.no-gate'))
                ->setDesc("%airline% flight %flightNumber% %depCode% to %arrCode% is delayed by %minutes%. Now departing on %depDate%, at %depTime%"),
            (new Message('push-notifications.flight-delay.body.with-gate'))
                ->setDesc("%airline% flight %flightNumber% %depCode% to %arrCode% is delayed by %minutes%. Now departing on %depDate%, at %depTime% from %terminal-gate%"),

            (new Message('push-notifications.flight-time-changed.title'))
                ->setDesc("Flight Departure Time Change"),
            (new Message('push-notifications.flight-time-changed.body.no-gate'))
                ->setDesc("%airline% flight %flightNumber% %depCode% to %arrCode% is now departing on %date%, at %time%"),
            (new Message('push-notifications.flight-time-changed.body.with-gate'))
                ->setDesc("%airline% flight %flightNumber% %depCode% to %arrCode% is now departing on %date%, at %time% from %terminal-gate%"),

            (new Message('push-notifications.flight-baggage-change.title'))
                ->setDesc('Flight Baggage Change'),
            (new Message('push-notifications.flight-baggage-change.body'))
                ->setDesc('Baggage carousel change detected. Luggage is now arriving at carousel #%carousel%'),

            (new Message('push-notifications.leg-arrived.title'))
                ->setDesc('You have arrived!'),
            (new Message('push-notifications.leg-arrived.body.with-to'))
                ->setDesc('Welcome to %city%, you are arriving at %terminal-gate%. Luggage will be available on carousel #%carousel%'),
            (new Message('push-notifications.leg-arrived.body.no-to'))
                ->setDesc('Welcome to %city%. Luggage will be available on carousel #%carousel%'),

            (new Message('push-notifications.hotel-phone.title'))
                ->setDesc('Phone Number'),
            (new Message('push-notifications.hotel-phone.body'))
                ->setDesc('The phone number for %hotel% is %phone%, tap to call'),

            (new Message('push-notifications.flight-cancellation.title'))
                ->setDesc('Flight Cancellation!'),
            (new Message('push-notifications.flight-cancellation.body'))
                ->setDesc('%airline% flight %flightNumber% %depCode% to %arrCode% on %depDate% at %depTime% has been canceled'),

            (new Message('push-notifications.flight-reinstated.title'))
                ->setDesc('Flight Reinstated'),
            (new Message('push-notifications.flight-reinstated.body'))
                ->setDesc('Previously canceled flight %airline% %flightNumber% %depCode% to %arrCode% on %depDate% at %depTime% has been reinstated'),

            (new Message('push-notifications.connection-info.title'))
                ->setDesc('Connection Info'),
            (new Message('push-notifications.connection-info.body'))
                ->setDesc('Welcome to %arrCity%, you are arriving at %arr-terminal-gate%. Your next flight, %nextAirline% %nextFlightNumber% to %nextDepCode%, is scheduled to depart at %time%, from %dep-terminal-gate% (%connection-time% connection)'),

            (new Message('push-notifications.connection-info-gate-change.title'))
                ->setDesc('Gate Change Alert!'),
            (new Message('push-notifications.connection-info-gate-change.body'))
                ->setDesc('Your departure gate has changed from %prev-dep-terminal-gate% to %dep-terminal-gate% for %airline% flight %flightNumber% to %arrCode% departing at %time% (in %minutes%)'),
        ];
    }

    private function getFlightIndex(AlertDetails $details)
    {
        if ($details->getOutboundFlightIndex() !== null
        && in_array($details->getType(), ["Flight Departure Gate Change", "Connection Information Gate Change"])) {
            return $details->getOutboundFlightIndex();
        }

        if ($details->getInboundFlightIndex() !== null) {
            return $details->getInboundFlightIndex();
        }

        return null;
    }

    private function sendPush(Tripsegment $tripSegment, array $changedProperties, ReceiverProcessResponse $response)
    {
        if (empty($response->onGetPushes)) {
            return;
        }

        if (!$response->sendToHidden && ($tripSegment->getHidden() || $tripSegment->getTripid()->getHidden())) {
            $this->logger->info("want to pushes by segment, but it is hidden: " . $tripSegment->getTripsegmentid());

            return;
        }
        $this->logger->info("sending pushes by segment: " . $tripSegment->getTripsegmentid());
        /** @var Push[] $pushes */
        $pushes = call_user_func($response->onGetPushes, $tripSegment, $changedProperties);

        if (!empty($pushes)) {
            foreach ($pushes as $push) {
                $devices = $this->sender->loadDevices([$tripSegment->getTripid()->getUserid()], $push->deviceTypes, $push->content->type);

                if (!empty($devices)) {
                    $push->content->target = new AlertTarget(
                        $tripSegment,
                        $push->content->target
                    );

                    $this->sender->send($push->content, $devices);
                }
            }
        }
    }

    private function extractFlightStatusInfo(FlightStatus $status)
    {
        $result = new ReceiverProcessResponse();

        $result->segment->departure->gate = $status->getDeparture()->getGate();
        $result->segment->arrival->gate = $status->getArrival()->getGate();

        $result->segment->departure->baggage = $status->getDeparture()->getBaggage();
        $result->segment->arrival->baggage = $status->getArrival()->getBaggage();

        $result->segment->departure->terminal = $status->getDeparture()->getTerminal();
        $result->segment->arrival->terminal = $status->getArrival()->getTerminal();

        $result->segment->departure->localDateTime = $status->getDeparture()->getEstimatedGateDateTime();
        $result->segment->arrival->localDateTime = $status->getArrival()->getEstimatedGateDateTime();

        return $result;
    }

    private function processFlightDepartureDelay($userId, Alert $alert, AlertDetails $details, FlightWithStatus $flight, FlightStatus $status)
    {
        $result = $this->extractFlightStatusInfo($status);

        if (!empty($result->segment->departure->localDateTime)) {
            $result->onGetPushes = function (Tripsegment $tripSegment) use ($result, $details, $flight, $status) {
                $account = $tripSegment->getTripid()->getAccount();

                if (null !== $account) {
                    $this->checkScheduler->schedule($account->getId());
                }

                return $this->createFlightDelayPush($result->segment, $details, $flight, $status);
            };
        }

        return $result;
    }

    private function createFlightDelayPush(FlightSegment $segment, AlertDetails $details, FlightWithStatus $flight, FlightStatus $status)
    {
        $title = null;
        $body = null;
        $key = null;
        $contentType = null;

        if ($details->getDelay()->getCurrent() > 0) {
            $contentType = Content::TYPE_FLIGHT_DELAY;
            $title = new Trans('push-notifications.flight-delay.title');

            if (!empty($segment->departure->gate) || !empty($segment->departure->terminal)) {
                $key = 'push-notifications.flight-delay.body.with-gate';
            } else {
                $key = 'push-notifications.flight-delay.body.no-gate';
            }
        }

        if ($details->getDelay()->getCurrent() == 0 && $details->getDelay()->getPrevious() > 0) {
            $contentType = Content::TYPE_FLIGHT_TIME_CHANGED;
            $title = new Trans('push-notifications.flight-time-changed.title');

            if (!empty($segment->departure->gate)) {
                $key = 'push-notifications.flight-time-changed.body.with-gate';
            } else {
                $key = 'push-notifications.flight-time-changed.body.no-gate';
            }
        }

        if (isset($title)) {
            $body = new Trans($key, array_merge($this->getFlightPushParams($flight, $status), [
                '%minutes%' => function ($id, $params, $domain, $locale) use ($details) {
                    return $this->intervalFormatter->formatDurationViaInterval(
                        \DateInterval::createFromDateString(sprintf('%d minute', abs($details->getDelay()->getCurrent()))),
                        false,
                        false,
                        false,
                        $locale
                    );
                },
                '%terminal-gate%' => function ($id, $params, $domain, $locale) use ($status) {
                    return $this->getTerminalAndGate($status->getDeparture(), $locale);
                },
            ]));
            $content = new Content(
                $title,
                $body,
                $contentType,
                null,
                (new Options())
                    ->setDeadlineTimestamp($this->getGateTimestamp($status->getDeparture()))
                    ->setPriority(8)
                    ->setInterruptionLevel(InterruptionLevel::TIME_SENSITIVE)
            );

            return [new Push(MobileDevice::TYPES_ALL, $content)];
        }

        return null;
    }

    private function processLegBaggageChange($userId, Alert $alert, AlertDetails $details, FlightWithStatus $flight, FlightStatus $status)
    {
        $result = $this->extractFlightStatusInfo($status);

        if (!empty($result->segment->arrival->baggage)) {
            $result->onGetPushes = function (Tripsegment $tripSegment, array $changedProperties) use ($result) {
                if (!in_array('BaggageClaim', $changedProperties)) {
                    return null;
                }

                return [
                    new Push(
                        MobileDevice::TYPES_MOBILE,
                        new Content(
                            new Trans('push-notifications.flight-baggage-change.title'),
                            new Trans('push-notifications.flight-baggage-change.body', ['%carousel%' => $result->segment->arrival->baggage]),
                            Content::TYPE_FLIGHT_BAGGAGE_CHANGE,
                            null,
                            (new Options())
                                ->setDeadlineTimestamp(time() + 1800)
                                ->setPriority(6)
                                ->setInterruptionLevel(InterruptionLevel::TIME_SENSITIVE)
                        )
                    ),
                ];
            };
        }

        return $result;
    }

    private function processLegArrived($userId, Alert $alert, AlertDetails $details, FlightWithStatus $flight, FlightStatus $status)
    {
        $result = $this->extractFlightStatusInfo($status);

        $result->onGetPushes = function () use ($flight, $status, $result, $userId) {
            $pushes = [];

            // welcome to city
            $city = $this->connection->executeQuery("select CityName from AirCode where AirCode = ?", [$flight->getArrival()->getAirportCode()])->fetchColumn();

            if (
                !empty($city)
                && !empty($result->segment->arrival->baggage)
            ) {
                if (!empty($result->segment->arrival->terminal) || !empty($result->segment->arrival->gate)) {
                    $bodyKey = 'push-notifications.leg-arrived.body.with-to';
                } else {
                    $bodyKey = 'push-notifications.leg-arrived.body.no-to';
                }
                $pushes[] = new Push(
                    MobileDevice::TYPES_MOBILE,
                    new Content(
                        new Trans('push-notifications.leg-arrived.title'),
                        new Trans($bodyKey, [
                            '%city%' => $city,
                            '%carousel%' => $result->segment->arrival->baggage,
                            '%terminal-gate%' => function ($id, $params, $domain, $locale) use ($status) {
                                return $this->getTerminalAndGate($status->getArrival(), $locale);
                            },
                        ]),
                        Content::TYPE_LEG_ARRIVED,
                        (new Options())
                            ->setDeadlineTimestamp(time() + 1800)
                            ->setPriority(5)
                            ->setInterruptionLevel(InterruptionLevel::TIME_SENSITIVE)
                            ->setLogContext([
                                "ArrCode" => $result->segment->arrival->airportCode,
                            ])
                    )
                );
            }

            // hotel phone
            $hotel = $this->searchHotel($userId, $flight->getArrival()->getAirportCode(), strtotime($flight->getArrival()->getDateTime()));

            if (!empty($hotel)) {
                $pushes[] = new Push(
                    MobileDevice::TYPES_MOBILE,
                    new Content(
                        new Trans('push-notifications.hotel-phone.title'),
                        new Trans('push-notifications.hotel-phone.body', $hotel),
                        Content::TYPE_HOTEL_PHONE,
                        preg_replace('/[^0-9\+]/', '', $hotel['%phone%']),
                        (new Options())
                            ->setDeadlineTimestamp(time() + 1800)
                            ->setDelay(300)
                            ->setPriority(5)
                            ->setInterruptionLevel(InterruptionLevel::TIME_SENSITIVE)
                    )
                );
            }

            return $pushes;
        };

        return $result;
    }

    private function searchHotel($userId, $airCode, $localDate)
    {
        $this->logger->info("searching hotel", ["userId" => $userId, "ts" => $localDate]);
        $hotel = $this->connection->executeQuery("
        select
            r.HotelName,
            r.Phone
        from
            Reservation r
        where
            r.UserID = :userId
            and abs(unix_timestamp(r.CheckInDate) - :localDate) < (3600 * 20)
            and r.Phone is not null
            and r.Phone <> ''
        order by 
            abs(unix_timestamp(r.CheckInDate) - :localDate) desc
        limit 1
        ", ["userId" => $userId, "localDate" => $localDate])->fetch(\PDO::FETCH_ASSOC);

        if (!empty($hotel)) {
            return ['%hotel%' => $hotel['HotelName'], '%phone%' => $hotel['Phone']];
        } else {
            return null;
        }
    }

    private function processFlightCancellation($userId, Alert $alert, AlertDetails $details, FlightWithStatus $flight, FlightStatus $status)
    {
        $result = $this->extractFlightStatusInfo($status);
        $result->hideSegment = true;
        $result->sendToHidden = true;

        $result->onGetPushes = function () use ($flight, $status) {
            return [new Push(
                MobileDevice::TYPES_ALL,
                new Content(
                    new Trans('push-notifications.flight-cancellation.title'),
                    new Trans('push-notifications.flight-cancellation.body', $this->getFlightPushParams($flight, $status)),
                    Content::TYPE_FLIGHT_CANCELLATION,
                    null,
                    (new Options())
                        ->setDeadlineTimestamp($this->getGateTimestamp($status->getDeparture()))
                        ->setPriority(8)
                        ->setInterruptionLevel(InterruptionLevel::TIME_SENSITIVE)
                )
            )];
        };

        return $result;
    }

    private function processFlightReinstated($userId, Alert $alert, AlertDetails $details, FlightWithStatus $flight, FlightStatus $status)
    {
        $result = $this->extractFlightStatusInfo($status);
        $result->hideSegment = false;

        $result->onGetPushes = function () use ($flight, $status) {
            return [new Push(
                MobileDevice::TYPES_ALL,
                new Content(
                    new Trans('push-notifications.flight-reinstated.title'),
                    new Trans('push-notifications.flight-reinstated.body', $this->getFlightPushParams($flight, $status)),
                    Content::TYPE_FLIGHT_REINSTATED,
                    null,
                    (new Options())
                        ->setDeadlineTimestamp($this->getGateTimestamp($status->getDeparture()))
                        ->setPriority(8)
                        ->setInterruptionLevel(InterruptionLevel::TIME_SENSITIVE)
                )
            )];
        };

        return $result;
    }

    // FlightStats will delay ConnectionInfo alert for unknown reasons,
    // so, we will send Connection Info when Flight Arrived
    private function processFlightArrived(int $userId, Alert $alert, AlertDetails $details, FlightWithStatus $flight, FlightStatus $status)
    {
        $leg = $alert->getTrip()->getLegs()[$details->getLegIndex()];

        // no connection info on last flight in leg
        if ($details->getFlightIndex() == (count($leg->getFlights()) - 1)) {
            return $this->extractFlightStatusInfo($status);
        }

        $arrFlight = $leg->getFlights()[$details->getFlightIndex()];
        $depFlight = $leg->getFlights()[$details->getFlightIndex() + 1];

        if (empty($depFlight->getFlightStatuses())) {
            return $this->extractFlightStatusInfo($status);
        }

        $connectionTime = round(($this->getGateTimestamp($depFlight->getFlightStatuses()[0]->getDeparture()) - $this->getGateTimestamp($arrFlight->getFlightStatuses()[0]->getArrival())) / 60);

        return $this->processConnectionInfoOrFlightArrived(
            $userId,
            $alert,
            $details,
            $flight,
            $status,
            $details->getFlightIndex() + 1,
            $connectionTime,
        );
    }

    private function processConnectionInfo(int $userId, Alert $alert, AlertDetails $details, FlightWithStatus $flight, FlightStatus $status)
    {
        return $this->processConnectionInfoOrFlightArrived(
            $userId,
            $alert,
            $details,
            $flight,
            $status,
            $details->getOutboundFlightIndex(),
            $details->getConnectionTime()->getCurrent()
        );
    }

    private function processConnectionInfoOrFlightArrived(
        int $userId,
        Alert $alert,
        AlertDetails $details,
        FlightWithStatus $flight,
        FlightStatus $status,
        int $outboundFlightIndex,
        int $connectionTime
    ) {
        $result = $this->extractFlightStatusInfo($status);

        $result->onGetPushes = function (Tripsegment $tripSegment) use ($flight, $status, $alert, $details, $userId, $outboundFlightIndex, $connectionTime) {
            if ($tripSegment->getUser()->getId() !== $userId) {
                return [];
            }

            $city = $this->connection->executeQuery("select CityName from AirCode where AirCode = ?", [$flight->getArrival()->getAirportCode()])->fetchColumn();
            $depFlight = $alert->getTrip()->getLegs()[$details->getLegIndex()]->getFlights()[$outboundFlightIndex];
            $nextStatus = $depFlight->getFlightStatuses()[0];
            $airlineIataCode = $depFlight->getBookedAirlineIataCode();

            if (empty($airlineIataCode)) {
                $airlineIataCode = $this->airlineConverter->FSCodeToIata($depFlight->getBookedAirlineCode());
            }

            if (empty($city) || (empty($status->getArrival()->getTerminal()) && empty($status->getArrival()->getGate()))
            || empty($airlineIataCode)
            || (empty($nextStatus->getDeparture()->getTerminal()) && empty($nextStatus->getDeparture()->getGate()))) {
                return [];
            }

            $nextAirline = $this->airlineConverter->FSCodeToName($depFlight->getBookedAirlineCode()) ?? $depFlight->getBookedAirlineCode();

            // we do not want to send double pushes on "Flight Arrived" then "Conneciton Info"
            $key = "ta_cinfo_" . sha1("
                {$userId}
                {$flight->getArrival()->getAirportCode()} 
                {$status->getArrival()->getScheduledGateDateTime()} 
                {$status->getArrival()->getTerminal()} 
                {$status->getArrival()->getGate()}
                {$nextAirline}
                {$depFlight->getFlightNumber()}
                {$depFlight->getArrival()->getAirportCode()}
            ");

            if (!$this->memcached->add($key, time(), 3600 * 6)) {
                $this->logger->warning("gate change already sent, will not send push");

                return [];
            }

            return [new Push(
                MobileDevice::TYPES_MOBILE,
                new Content(
                    new Trans('push-notifications.connection-info.title'),
                    new Trans('push-notifications.connection-info.body', [
                        '%arrCity%' => $city,
                        '%arr-terminal-gate%' => function ($id, $params, $domain, $locale) use ($status) {
                            return $this->getTerminalAndGate($status->getArrival(), $locale);
                        },
                        '%nextAirline%' => $nextAirline,
                        '%nextFlightNumber%' => $depFlight->getFlightNumber(),
                        '%nextDepCode%' => $depFlight->getArrival()->getAirportCode(),
                        '%connection-time%' => function ($id, $params, $domain, $locale) use ($connectionTime) {
                            $result = $this->intervalFormatter->formatDurationViaInterval(
                                \DateInterval::createFromDateString(sprintf('%d minute', abs($connectionTime))),
                                false,
                                false,
                                false,
                                $locale
                            );

                            if ($connectionTime < 0) {
                                $result = "-" . $result;
                            }

                            return $result;
                        },
                        '%time%' => function ($id, $params, $domain, $locale) use ($nextStatus) {
                            return $this->localizer->formatTime(strtotime($this->getGateTime($nextStatus->getDeparture())), 'short', $locale);
                        },
                        '%dep-terminal-gate%' => function ($id, $params, $domain, $locale) use ($nextStatus) {
                            return $this->getTerminalAndGate($nextStatus->getDeparture(), $locale);
                        },
                    ]),
                    Content::TYPE_CONNECTION_INFO,
                    null,
                    (new Options())
                        ->setDeadlineTimestamp(min(time() + 1800, max(time(), $this->getGateTimestamp($nextStatus->getDeparture()))))
                        ->setPriority(6)
                        ->setInterruptionLevel(InterruptionLevel::TIME_SENSITIVE)
                )
            )];
        };

        return $result;
    }

    private function processConnectionInformationGateChange(int $userId, Alert $alert, AlertDetails $details, FlightWithStatus $flight, FlightStatus $status)
    {
        $result = $this->extractFlightStatusInfo($status);

        $result->onGetPushes = function (Tripsegment $tripSegment) use ($flight, $alert, $details, $userId) {
            $city = $this->connection->executeQuery("select CityName from AirCode where AirCode = ?", [$flight->getArrival()->getAirportCode()])->fetchColumn();

            if ($details->getType() === 'Flight Departure Gate Change') {
                $flightIndex = $details->getFlightIndex();
                $contentType = Content::TYPE_FLIGHT_DEPARTURE_GATE_CHANGE;
            } else {
                // Connection Information Gate Change
                if ($tripSegment->getUser()->getId() !== $userId) {
                    return [];
                }
                $flightIndex = $details->getOutboundFlightIndex();
                $contentType = Content::TYPE_CONNECTION_INFO_GATE_CHANGE;
            }
            $depFlight = $alert->getTrip()->getLegs()[$details->getLegIndex()]->getFlights()[$flightIndex];
            $nextStatus = $depFlight->getFlightStatuses()[0];

            $previousTerminal = null;

            if (!empty($details->getTerminal()) && !empty($details->getTerminal()->getPrevious())) {
                $previousTerminal = $details->getTerminal()->getPrevious();
            } elseif (!empty($nextStatus->getDeparture()->getTerminal())) {
                $previousTerminal = $nextStatus->getDeparture()->getTerminal();
            }

            if (!empty($details->getTerminal()) && !empty($details->getTerminal()->getCurrent())) {
                $currentTerminal = $details->getTerminal()->getCurrent();
            } else {
                $currentTerminal = $nextStatus->getDeparture()->getTerminal();
            }

            $previousGate = null;

            if (!empty($details->getGate()) && !empty($details->getGate()->getPrevious())) {
                $previousGate = $details->getGate()->getPrevious();
            } elseif (!empty($nextStatus->getDeparture()->getGate())) {
                $previousGate = $nextStatus->getDeparture()->getGate();
            }

            if (!empty($details->getGate()) && !empty($details->getGate()->getCurrent())) {
                $currentGate = $details->getGate()->getCurrent();
            } else {
                $currentGate = $nextStatus->getDeparture()->getGate();
            }

            $this->logger->info("about to send gate change push from {$previousTerminal} {$previousGate} to {$currentTerminal} {$currentGate}");

            if (empty($city)) {
                $this->logger->warning("no city, will not send push");

                return [];
            }

            if (empty($currentGate) && empty($currentTerminal)) {
                $this->logger->warning("empty terminal and gate, will not send push");

                return [];
            }

            $airlineIataCode = $depFlight->getBookedAirlineIataCode();

            if (empty($airlineIataCode)) {
                $airlineIataCode = $this->airlineConverter->FSCodeToIata($depFlight->getBookedAirlineCode());
            }

            if (empty($airlineIataCode)) {
                $this->logger->warning("failed to convert iata code {$depFlight->getBookedAirlineCode()}, will not send push");

                return [];
            }

            if ($previousTerminal === $currentTerminal && $previousGate === $currentGate) {
                $this->logger->warning("gate and status not changed, will not send push");

                return [];
            }

            $key = "ta_gch_{$userId}_{$depFlight->getArrival()->getAirportCode()} {$depFlight->getBookedAirlineCode()} {$depFlight->getFlightNumber()} {$previousTerminal} {$previousGate} to {$currentTerminal} {$currentGate}";

            if ($this->memcached->get($key) !== false) {
                $this->logger->warning("gate change already sent, will not send push");

                return [];
            }

            $this->memcached->add($key, time(), 3600);

            return [new Push(
                MobileDevice::TYPES_MOBILE,
                new Content(
                    new Trans('push-notifications.connection-info-gate-change.title'),
                    new Trans('push-notifications.connection-info-gate-change.body', [
                        '%prev-dep-terminal-gate%' => function ($id, $params, $domain, $locale) use ($previousTerminal, $previousGate) {
                            $result = [];

                            if (!empty($previousTerminal)) {
                                $result[] = $this->translator->trans("itineraries.trip.air.terminal", [], "trips", $locale) . " " . $previousTerminal;
                            }

                            if (!empty($previousGate)) {
                                $result[] = $this->translator->trans("departure-gate", [], "trips", $locale) . " " . $previousGate;
                            }

                            return implode(", ", $result);
                        },
                        '%dep-terminal-gate%' => function ($id, $params, $domain, $locale) use ($nextStatus) {
                            return $this->getTerminalAndGate($nextStatus->getDeparture(), $locale);
                        },
                        '%airline%' => $this->airlineConverter->FSCodeToName($depFlight->getBookedAirlineCode()) ?? $depFlight->getBookedAirlineCode(),
                        '%flightNumber%' => $depFlight->getFlightNumber(),
                        '%arrCode%' => $depFlight->getArrival()->getAirportCode(),
                        '%minutes%' => function ($id, $params, $domain, $locale) use ($nextStatus, $depFlight) {
                            $date1 = $this->timeCommunicator->getCurrentDateTime();
                            $tz = $this->connection->executeQuery(
                                "select TimeZoneLocation from AirCode where AirCode = ?",
                                [$depFlight->getDeparture()->getAirportCode()]
                            )->fetchColumn();
                            $date2 = new \DateTime($this->getGateTime($nextStatus->getDeparture()), new \DateTimeZone($tz));
                            $result = $this->intervalFormatter->formatDuration(
                                $date1,
                                $date2,
                                false,
                                false,
                                false,
                                $locale
                            );

                            if ($date1->getTimestamp() > $date2->getTimestamp()) {
                                $result = "-" . $result;
                                $this->logger->warning("departure in past: $result");
                            }

                            return $result;
                        },
                        '%time%' => function ($id, $params, $domain, $locale) use ($nextStatus) {
                            return $this->localizer->formatTime(strtotime($this->getGateTime($nextStatus->getDeparture())), 'short', $locale);
                        },
                    ]),
                    $contentType,
                    null,
                    (new Options())
                        ->setDeadlineTimestamp(min(time() + 1800, max(time(), $this->getGateTimestamp($nextStatus->getDeparture()))))
                        ->setPriority(7)
                        ->setInterruptionLevel(InterruptionLevel::TIME_SENSITIVE)
                        ->setLogContext([
                            "DepCode" => $depFlight->getDeparture()->getAirportCode(),
                            "ArrCode" => $depFlight->getArrival()->getAirportCode(),
                        ])
                )
            )];
        };

        return $result;
    }

    private function processFlightDepartureGateChange($userId, Alert $alert, AlertDetails $details, FlightWithStatus $flight, FlightStatus $status)
    {
        return $this->processConnectionInformationGateChange($userId, $alert, $details, $flight, $status);
    }

    private function getGateTime(FlightStatusDetail $detail)
    {
        if (!empty($time = $detail->getActualGateDateTime())) {
            return $time;
        }

        if (!empty($time = $detail->getEstimatedGateDateTime())) {
            return $time;
        }

        return $detail->getScheduledGateDateTime();
    }

    private function getGateTimestamp(FlightStatusDetail $detail)
    {
        $timeStr = $this->getGateTime($detail);
        $timeZone = $this->getAirportTimezone($detail->getAirportCode());

        return (new \DateTime($timeStr, new \DateTimeZone($timeZone)))->getTimestamp();
    }

    private function getAirportTimezone(string $airportCode): string
    {
        return $this->memcachedUtil->getThrough("airport_timezone_" . $airportCode, function () use ($airportCode) {
            return new Item($this->tzQuery->executeQuery(["airCode" => $airportCode])->fetchOne(), 3600);
        });
    }

    private function getTerminalAndGate(FlightStatusDetail $detail, $locale)
    {
        $result = [];

        if (!empty($detail->getTerminal()) && !(in_array($detail->getTerminal(), ['S', 'N']) && preg_match('#^[a-z]\d+$#ims', $detail->getGate()))) {
            $result[] = $this->translator->trans("itineraries.trip.air.terminal", [], "trips", $locale) . " " . $detail->getTerminal();
        }

        if (!empty($detail->getGate())) {
            $result[] = $this->translator->trans("departure-gate", [], "trips", $locale) . " " . $detail->getGate();
        }

        return implode(", ", $result);
    }

    private function getFlightPushParams(FlightWithStatus $flight, FlightStatus $status)
    {
        $date = strtotime($this->getGateTime($status->getDeparture()));

        return [
            '%airline%' => $this->airlineConverter->FSCodeToName($flight->getBookedAirlineCode()) ?? $flight->getBookedAirlineCode(),
            '%flightNumber%' => $flight->getFlightNumber(),
            '%depCode%' => $flight->getDeparture()->getAirportCode(),
            '%arrCode%' => $flight->getArrival()->getAirportCode(),
            '%depDate%' => function ($id, $params, $domain, $locale) use ($date) {
                return $this->localizer->formatDate($date, 'long', $locale);
            },
            '%depTime%' => function ($id, $params, $domain, $locale) use ($date) {
                return $this->localizer->formatTime($date, 'short', $locale);
            },
        ];
    }

    private function isNewFlightUpdate(string $alertType, FlightWithStatus $flight, AlertDetails $details): bool
    {
        $info = (string) $flight;
        $info .= $details->getBaggage();
        $info .= $details->getConnectionTime();
        $info .= $details->getDelay();
        $info .= $details->getGate();
        $info .= $details->getTerminal();
        $key = "ta_fl_" . $alertType . sha1($info);

        return $this->memcached->add($key, time(), 3600);
    }
}
