<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\Common\Airport\AirportTime;
use AwardWallet\Common\Doctrine\BatchUpdater;
use AwardWallet\Common\TimeCommunicator;
use AwardWallet\MainBundle\Entity\Airline;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Globals\Geo;
use AwardWallet\MainBundle\Loyalty\Resources\RewardAvailability\CalendarItem;
use AwardWallet\MainBundle\Loyalty\Resources\RewardAvailability\RewardAvailabilityFlights;
use AwardWallet\MainBundle\Loyalty\Resources\RewardAvailability\Segment;
use AwardWallet\MainBundle\Service\AppBot\Adapter\Slack;
use AwardWallet\MainBundle\Service\AppBot\AppBot;
use AwardWallet\MainBundle\Service\MileValue\TimeDiff;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RewardAvailabilityCallbackController
{
    private LoggerInterface $logger;

    private string $callbackPassword;

    private EntityManagerInterface $em;

    private SerializerInterface $serializer;

    private AirportTime $airportTime;

    private Connection $connection;

    private AppBot $appBot;

    private \Memcached $cache;

    private TimeCommunicator $timeCommunicator;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $em,
        SerializerInterface $serializer,
        $callbackPassword,
        AirportTime $airportTime,
        AppBot $appBot,
        \Memcached $memcached,
        TimeCommunicator $timeCommunicator
    ) {
        $this->logger = $logger;
        $this->em = $em;
        $this->serializer = $serializer;

        $this->callbackPassword = $callbackPassword;
        $this->airportTime = $airportTime;
        $this->appBot = $appBot;
        $this->cache = $memcached;
        $this->timeCommunicator = $timeCommunicator;

        $this->connection = $this->em->getConnection();
    }

    /**
     * @Route("/api/reward-availability/flights", methods={"POST"}, name="aw_reward_availability_flights")
     * @return Response
     */
    public function RewardAvailabilityFlightsAction(Request $httpRequest)
    {
        if (!$this->checkAccess($httpRequest->getUser(), $httpRequest->getPassword())) {
            return new Response('access denied', 403);
        }

        $requestArray = unserialize($httpRequest->getContent());

        if (!is_array($requestArray)) {
            return new Response(
                Response::$statusTexts[Response::HTTP_BAD_REQUEST],
                Response::HTTP_BAD_REQUEST
            );
        }
        $data = [];

        foreach ($requestArray as $item) {
            $raFlights = $this->serializer->deserialize($item, RewardAvailabilityFlights::class,
                'json');

            if (!$raFlights instanceof RewardAvailabilityFlights) {
                return new Response(
                    Response::$statusTexts[Response::HTTP_BAD_REQUEST],
                    Response::HTTP_BAD_REQUEST
                );
            }
            $data[] = $raFlights;
        }

        $result = new Response('OK');

        foreach ($data as $raFlights) {
            /** @var ProviderRepository $userRepo */
            $providerRepo = $this->em->getRepository(Provider::class);
            $provider = $providerRepo->findOneBy(['code' => $raFlights->getProvider()]);

            if (!$provider instanceof Provider) {
                $this->logger->critical('RewardAvailabilityFlights result Unavailable provider',
                    ['requestId' => $raFlights->getRequestId()]);

                continue;
            }

            try {
                $this->logger->pushProcessor(function (array $record) use ($raFlights) {
                    $record['context']['RequestID'] = $raFlights->getRequestId();

                    return $record;
                });
                $this->appendFlights($raFlights);
                $this->appendCalendar($raFlights);
            } finally {
                $this->logger->popProcessor();
            }
        }

        return $result;
    }

    private function checkAccess($user, $pass)
    {
        $result = $user === 'awardwallet' && $pass === $this->callbackPassword;

        if (!$result) {
            $this->logger->notice("access denied for " . $user);
        }

        return $result;
    }

    private function appendCalendar(RewardAvailabilityFlights $data)
    {
        $checkData = $this->connection->executeQuery(
            /** @lang MySQL */ "SELECT 1 FROM RACalendar WHERE RequestID=:RequestID LIMIT 1",
            ['RequestID' => $data->getRequestId()],
            [\PDO::PARAM_STR]
        )->fetchOne();

        if (!empty($checkData)) {
            return;
        }
        $stmt = $this->connection->prepare("
            insert into RACalendar (RequestID, SearchDate, Provider, FromAirport, ToAirport, StandardItineraryCOS, BrandedItineraryCOS, DepartureDate, MileCost, CashCost, Currency)
            values (:RequestID, :SearchDate, :Provider, :FromAirport, :ToAirport, :StandardItineraryCOS, :BrandedItineraryCOS, :DepartureDate, :MileCost, :CashCost, :Currency)
            on duplicate key update RequestID = :RequestID, SearchDate = :SearchDate, MileCost = :MileCost, CashCost = :CashCost, Currency = :Currency
        ");

        $baseParams = [
            'RequestID' => $data->getRequestId(),
            'SearchDate' => $data->getRequestDate()->format('Y-m-d H:i:s'),
            'Provider' => $data->getProvider(),
            'FromAirport' => $data->getDepCode(),
            'ToAirport' => $data->getArrCode(),
            'StandardItineraryCOS' => null,
            'BrandedItineraryCOS' => null,
            'DepartureDate' => null,
            'MileCost' => null,
            'CashCost' => null,
            'Currency' => null,
        ];

        foreach (($data->getCalendar() ?? []) as $flight) {
            /** @var CalendarItem $flight */
            $params = $baseParams;
            $params['StandardItineraryCOS'] = $flight->getStandardItineraryCOS() ?? '';
            $params['BrandedItineraryCOS'] = $flight->getBrandedItineraryCOS() ?? '';

            try {
                $date = (new \DateTime($flight->getDate()))->format('Y-m-d');
            } catch (\Exception $e) {
                $date = null;
            }

            if (empty($date)) {
                continue;
            }
            $params['DepartureDate'] = $date;

            if (empty($flight->getCashCost())) {
                continue;
            }
            $params['MileCost'] = $flight->getMiles();
            $params['CashCost'] = ($flight->getCashCost()->getFees() ?? 0) + ($flight->getCashCost()->getTaxes() ?? 0);
            $params['Currency'] = $flight->getCashCost()->getCurrency() ?? '';

            $toCache = $params;
            unset($toCache['RequestID'], $toCache['SearchDate']);
            $keyMemData = 'aw_racalendar_' . sha1(json_encode($toCache));

            if (false !== $this->cache->add($keyMemData, 24 * 60 * 60)) {
                try {
                    $stmt->executeQuery($params);
                } catch (\Exception $e) {
                    $this->logger->notice($e->getMessage());
                }
            }
        }
    }

    private function appendFlights(RewardAvailabilityFlights $data)
    {
        $checkData = $this->connection->executeQuery(
            /** @lang MySQL */ "SELECT 1 FROM RAFlight WHERE RequestID=:RequestID LIMIT 1",
            ['RequestID' => $data->getRequestId()],
            [\PDO::PARAM_STR]
        )->fetchOne();

        if (!empty($checkData)) {
            return;
        }

        $sql = /** @lang MySQL */ "
            INSERT IGNORE INTO RAFlight (RequestID, SearchDate, Provider, Airlines, StandardSegmentCOS, FareClasses, AwardType, FlightType, Route, FromAirport, FromRegion, FromCountry, ToAirport, ToRegion, ToCountry, MileCost, Taxes, Currency, DaysBeforeDeparture, DepartureDate, ArrivalDate, TravelTime, Stopovers, Layovers, TotalDistance, ODDistance, LayoverOne, LayoverOneDistance, StopoverOne, StopoverOneDistance, LayoverTwo, LayoverTwoDistance, StopoverTwo, StopoverTwoDistance, IsMixedCabin, IsFastest, IsCheapest, Passengers, StandardItineraryCOS, BrandedItineraryCOS, SeatsLeft, SeatsLeftOnRoute, BrandedSegmentCOS, CostPerHour)
            VALUES (:RequestID, :SearchDate, :Provider, :Airlines, :StandardSegmentCOS, :FareClasses, :AwardType, :FlightType, :Route, :FromAirport, :FromRegion, :FromCountry, :ToAirport, :ToRegion, :ToCountry, :MileCost, :Taxes, :Currency, :DaysBeforeDeparture, :DepartureDate, :ArrivalDate, :TravelTime, :Stopovers, :Layovers, :TotalDistance, :ODDistance, :LayoverOne, :LayoverOneDistance, :StopoverOne, :StopoverOneDistance, :LayoverTwo, :LayoverTwoDistance, :StopoverTwo, :StopoverTwoDistance, :IsMixedCabin, :IsFastest, :IsCheapest, :Passengers, :StandardItineraryCOS, :BrandedItineraryCOS, :SeatsLeft, :SeatsLeftOnRoute, :BrandedSegmentCOS, :CostPerHour)
        ";
        $stmt = $this->connection->prepare($sql);
        $keysForMem = [
            'Provider' => true,
            'Airlines' => true,
            'StandardSegmentCOS' => true,
            'FareClasses' => true,
            'Route' => true,
            'MileCost' => true,
            'Taxes' => true,
            'DaysBeforeDeparture' => true,
            'DepartureDate' => true,
            'ArrivalDate' => true,
            'IsFastest' => true,
            'IsCheapest' => true,
            'Passengers' => true,
            'BrandedItineraryCOS' => true,
            'AwardType' => true,
            'BrandedSegmentCOS' => true,
        ];
        $baseParams = [
            'RequestID' => $data->getRequestId(),
            'SearchDate' => $data->getRequestDate()->format('Y-m-d H:i:s'),
            'Provider' => $data->getProvider(),
            'Passengers' => $data->getPassengers(),
            'Stopovers' => 0,
            'Layovers' => 0,
            'LayoverOne' => '',
            'LayoverOneDistance' => 0,
            'StopoverOne' => '',
            'StopoverOneDistance' => 0,
            'LayoverTwo' => '',
            'LayoverTwoDistance' => 0,
            'StopoverTwo' => '',
            'StopoverTwoDistance' => 0,
        ];
        $flights = $data->getFlights();
        $airClasses = [];
        $flightRoutes = [];
        $wasSaved = false;
        $stats = $data->getStats();

        foreach ($flights as $flightType => $routes) {
            /** @var \AwardWallet\MainBundle\Loyalty\Resources\RewardAvailability\Route $route */
            foreach ($routes as $routeOrig) {
                $params = $baseParams;
                $params['FlightType'] = $flightType;
                $routeJson = $this->serializer->serialize($routeOrig, 'json');
                $route = $this->serializer->deserialize($routeJson,
                    \AwardWallet\MainBundle\Loyalty\Resources\RewardAvailability\Route::class, 'json');
                $airlines = $standardSegmentCOS = $fareClasses = $brandedSegmentCOS = $airports = [];
                $depDate = $arrDate = $depAirport = $arrAirport = null; // for from-to etc
                $distance = 0;
                $routeToFormat = [];
                $segments = $route->getSegmentsToSerialize();
                $SeatsLeft = [];

                /** @var Segment $segment */
                foreach ($segments as $numSeg => $segment) {
                    if (!empty($segment->getClassOfService())) {
                        $airClasses[] = [
                            'airline' => $segment->getAirlineCode(),
                            'class' => $segment->getClassOfService(),
                            'provider' => $data->getProvider(),
                        ];
                    }

                    if (!empty($route->getClassOfService())) {
                        $airClasses[] = [
                            'airline' => $segment->getAirlineCode(),
                            'class' => $route->getClassOfService(),
                            'provider' => $data->getProvider(),
                        ];
                    }

                    $keyFlightRoute = $this->createKeyFlightRoute($data->getProvider(), $segment);

                    if (!isset($flightRoutes[$keyFlightRoute])) {
                        $flightRoutes[$keyFlightRoute] = 1;
                    } else {
                        $flightRoutes[$keyFlightRoute]++;
                    }
                    $SeatsLeft[] = $segment->getTickets() ?? '';
                    $airlines[] = $segment->getAirlineCode();
                    $airports[] = $segment->getDepartAirport();
                    $brandedSegmentCOS[] = $segment->getClassOfService();
                    $standardSegmentCOS[] = $segment->getCabin();
                    $fareClasses[] = $segment->getFareClass();

                    if (!isset($depDate)) {
                        $depDate = $segment->getDepartDate();
                        $depAirport = $segment->getDepartAirport();
                    }
                    $distance += $this->calcDistance($segment->getDepartAirport(), $segment->getArrivalAirport());

                    if (isset($arrDate)) {
                        // previous
                        $layDistance = $this->calcDistance($arrAirport, $segment->getDepartAirport());
                        $prevArrivalGmt = $this->airportTime->convertToGmt($arrDate->getTimestamp(), $arrAirport);
                        $departureGmt = $this->airportTime->convertToGmt($segment->getDepartDate()->getTimestamp(),
                            $segment->getDepartAirport());

                        $layoverTime = $departureGmt - $prevArrivalGmt;

                        if ($layoverTime > 86400) {
                            $routeToFormat[] = 'so:' . TimeDiff::format($layoverTime);
                        } else {
                            $routeToFormat[] = 'lo:' . TimeDiff::format($layoverTime);
                        }

                        switch ($numSeg) {
                            case 1:
                                if ($layoverTime > 86400) {
                                    $params['Stopovers']++;
                                    $params['StopoverOne'] = $arrAirport;
                                    $params['StopoverOneDistance'] = $layDistance;
                                    $params['LayoverOne'] = '';
                                    $params['LayoverOneDistance'] = 0;
                                } else {
                                    $params['Layovers']++;
                                    $params['LayoverOne'] = $arrAirport;
                                    $params['LayoverOneDistance'] = $layDistance;
                                    $params['StopoverOne'] = '';
                                    $params['StopoverOneDistance'] = 0;
                                }

                                break;

                            case 2:
                                if ($layoverTime > 86400) {
                                    $params['Stopovers']++;
                                    $params['StopoverTwo'] = $arrAirport;
                                    $params['StopoverTwoDistance'] = $layDistance;
                                    $params['LayoverTwo'] = '';
                                    $params['LayoverTwoDistance'] = 0;
                                } else {
                                    $params['Layovers']++;
                                    $params['LayoverTwo'] = $arrAirport;
                                    $params['LayoverTwoDistance'] = $layDistance;
                                    $params['StopoverTwo'] = '';
                                    $params['StopoverTwoDistance'] = 0;
                                }
                        }
                    }
                    $arrDate = $segment->getArrivalDate();
                    $arrAirport = $segment->getArrivalAirport();
                    $routeToFormat[] = $segment->getDepartAirport() . '-' . $segment->getArrivalAirport();
                }
                $airports[] = $arrAirport;
                $params['Route'] = implode(',', $routeToFormat);
                $params['Airlines'] = implode(',', $airlines);
                $params['StandardSegmentCOS'] = implode(',', $standardSegmentCOS);
                $params['IsMixedCabin'] = count(array_unique($standardSegmentCOS)) > 1 ? 1 : 0;
                $params['IsFastest'] = $route->isFastest() ? 1 : 0;
                $params['IsCheapest'] = $route->isCheapest() ? 1 : 0;
                $params['StandardItineraryCOS'] = $route->getCabinType() ?? '';
                $params['BrandedItineraryCOS'] = $route->getClassOfService() ?? '';

                if (!empty(array_filter($brandedSegmentCOS))) {
                    $params['BrandedSegmentCOS'] = implode(',', $brandedSegmentCOS);
                } else {
                    $params['BrandedSegmentCOS'] = null;
                }
                $params['SeatsLeft'] = implode(',', $SeatsLeft);

                if (strlen($params['SeatsLeft']) < count($SeatsLeft)) {
                    $params['SeatsLeft'] = null;
                }
                $params['SeatsLeftOnRoute'] = $route->getTickets();

                $params['FareClasses'] = count(array_filter($fareClasses)) > 0 ? implode(',', $fareClasses) : '';

                $params['AwardType'] = $route->getAwardTypes() ?? '';

                $params['MileCost'] = $route->getMileCost()->getMiles();
                $params['Taxes'] = ($route->getCashCost()->getTaxes() ?? 0.0)
                    + ($route->getCashCost()->getFees() ?? 0.0);
                $params['Currency'] = $route->getCashCost()->getCurrency();
                $params['FromAirport'] = $depAirport;
                [$params['FromRegion'], $params['FromCountry']] = $this->getRegionDataByAirCode($depAirport);

                if (empty($params['FromCountry'])) {
                    $this->logger->error("RAFlight: can't detect country of airport",
                        ['airport' => $depAirport, 'point' => 'depart', 'requestId' => $data->getRequestId()]);

                    continue;
                }

                $params['ToAirport'] = $arrAirport;
                [$params['ToRegion'], $params['ToCountry']] = $this->getRegionDataByAirCode($arrAirport);

                if (empty($params['ToCountry'])) {
                    $this->logger->error("RAFlight: can't detect country of airport",
                        ['airport' => $arrAirport, 'point' => 'arrive', 'requestId' => $data->getRequestId()]);

                    continue;
                }
                $params['TotalDistance'] = $distance;
                $params['DepartureDate'] = date('Y-m-d H:i', $depDate->getTimestamp());
                $params['ArrivalDate'] = date('Y-m-d H:i', $arrDate->getTimestamp());
                $params['ODDistance'] = $this->calcDistance($depAirport, $arrAirport);

                $params['DaysBeforeDeparture'] = (int) (
                    ($depDate->getTimestamp() - $data->getRequestDate()->getTimestamp()) / 86400
                );

                $departureGmt = $this->airportTime->convertToGmt($depDate->getTimestamp(), $depAirport);
                $arrivalGmt = $this->airportTime->convertToGmt($arrDate->getTimestamp(), $arrAirport);
                $params['TravelTime'] = (int) (($arrivalGmt - $departureGmt) / 60);
                $params['CostPerHour'] = (int) round($route->getMileCost()->getMiles() / ($route->getTimes()->getFlight() / 60));
                $paramsForMem = array_intersect_key($params, $keysForMem);
                $keyMemData = 'aw_raflight_' . sha1(json_encode($paramsForMem));

                $toSave = $this->checkHardLimit($data->getProvider(), $params['StandardItineraryCOS'], $params['MileCost'], $route->getTimes()->getFlight() / 60);

                if (!empty($stats)) {
                    $stats = $this->extendedStatsData($stats, $depDate, $airports, $airlines, $standardSegmentCOS);
                }

                if ($toSave) {
                    if ($params['StandardItineraryCOS'] === $data->getCabin()) {
                        $wasSaved = true;
                    }

                    if (false !== $this->cache->add($keyMemData, 24 * 60 * 60)) {
                        $stmt->executeQuery($params);
                    }
                }
            }
        }

        if ($data->getDepCode() !== null && $data->getArrCode() !== null) {
            $this->saveRouteSearch($data->getProvider(), $data->getDepCode(), $data->getArrCode(), $data->getCabin(),
                $data->getRequestDate()->format('Y-m-d H:i:s'), $wasSaved, empty($flights));
        } else {
            $this->logger->info("bad data RAFlight", ['raFlight' => json_encode($data)]);
        }

        $airClasses = array_unique($airClasses, SORT_REGULAR);
        $this->saveClasses($airClasses);
        $this->saveFlightRoute($flightRoutes, $data->getRequestDate()->format('Y-m-d H:i:s'));

        if ($stats !== null) {
            $this->saveStats($stats, $data->getRequestDate()->format('Y-m-d H:i:s'), $data->getRequestId());
        }
    }

    private function extendedStatsData(
        array $stats,
        \DateTime $depDate,
        array $airports,
        array $airlines,
        array $standardSegmentCOS
    ): array {
        $prevStats = $stats;

        foreach ($prevStats as $num => $stat) {
            if ($stat['route'] !== $airports || !in_array($stat['carrier'], $airlines, true)) {
                continue;
            }

            if (isset($stats[$num]['airlines'])) {
                break;
            }
            $stats[$num]['airlines'] = $airlines;
            $stats[$num]['standardSegmentCOS'] = $standardSegmentCOS;
            $stats[$num]['depDate'] = $depDate->format('Y-m-d H:i:s');
        }

        return $stats;
    }

    private function checkHardLimit(string $providerCode, string $standardCOS, int $mileCost, float $hours): bool
    {
        $providerId = $this->connection->executeQuery(/** @lang MySQL */ "SELECT ProviderID FROM Provider WHERE Code = ?",
            [$providerCode], [\PDO::PARAM_STR])->fetchOne();

        $limits = $this->connection->executeQuery(/** @lang MySQL */ "SELECT * FROM RAFlightHardLimit WHERE ProviderID = ? AND ClassOfService = ?",
            [$providerId, $standardCOS], [\PDO::PARAM_INT])->fetchAssociative();

        if (empty($limits)) {
            $this->sendAlertWithLimit($providerCode, $standardCOS);

            // save all data if there is no information about limits in the RAFlightHardLimit table
            return true;
        }

        // main logic
        return ($mileCost <= $limits['HardCap']) && ($mileCost <= $limits['Base'] + ($limits['Multiplier'] * $hours));
    }

    private function sendAlertWithLimit(string $providerCode, string $standardCOS): void
    {
        $pair = $providerCode . '_' . $standardCOS;
        $cacheKeyList = 'ra_no_hard_limit_pairs';
        $cacheKeySendTime = 'ra_no_hard_limit_send_to_slack';

        $lastSendTime = $this->cache->get($cacheKeySendTime);

        if (!is_int($lastSendTime)) {
            $lastSendTime = $this->timeCommunicator->getCurrentTime();
            $this->cache->set($cacheKeySendTime, $lastSendTime, 60 * 60 * 24);
        }

        $noHardLimitList = $this->cache->get($cacheKeyList);

        if (!is_array($noHardLimitList)) {
            $noHardLimitList = [];
        }

        if (!in_array($pair, $noHardLimitList, true)) {
            $noHardLimitList[] = $pair;
        }
        // debug
        $this->logger->info('sendAlertWithLimit',
            ['lastSend' => date('Y-m-d H:i', $lastSendTime), 'noHardLimitList' => json_encode($noHardLimitList)]);

        if ($this->timeCommunicator->getCurrentTime() - $lastSendTime < 60 * 60 * 24) {
            $this->cache->set($cacheKeyList, $noHardLimitList, 60 * 60 * 24);

            return;
        }
        $this->cache->set($cacheKeySendTime, $this->timeCommunicator->getCurrentTime(), 60 * 60 * 24);
        $this->cache->set($cacheKeyList, [], 60 * 60 * 24);
        sort($noHardLimitList);
        $noHardLimitList = array_map(function ($s) {
            return str_replace('_', ' - ', $s);
        }, $noHardLimitList);
        // debug
        $this->logger->info('sendAlertWithLimit - action',
            ['lastSend' => date('Y-m-d H:i', $lastSendTime), 'noHardLimitList' => json_encode($noHardLimitList)]);

        if (empty($noHardLimitList)) {
            return;
        }
        $message = [
            'text' => '',
            'blocks' => [
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => '*Alert: RAFlightHardLimit - no data for pair(s):*' . "\n"
                            . implode("\n", $noHardLimitList),
                    ],
                ],
            ],
        ];
        $this->appBot->send(Slack::CHANNEL_AW_AWARD_ALERTS, $message);
    }

    private function saveClasses(array $airClasses): void
    {
        $prov = array_values(array_unique(array_map(function ($s) {
            return $s['provider'];
        }, $airClasses)));
        $providers = $this->connection->executeQuery(/** @lang MySQL */ "SELECT ProviderID, Code FROM Provider WHERE Code IN (?)",
            [$prov], [Connection::PARAM_STR_ARRAY])->fetchAllAssociative();
        $listProvider = [];

        foreach ($providers as $provider) {
            $listProvider[$provider['Code']] = $provider['ProviderID'];
        }

        $pairsToAdd = [];

        foreach ($airClasses as $item) {
            if (!isset($listProvider[$item['provider']])) {
                continue;
            }
            $key = $item['airline'] . '-' . ucwords(strtolower($item['class']));

            if (!isset($pairsToAdd[$key])) {
                $pairsToAdd[$key] = [];
            }
            $pairsToAdd[$key][] = $listProvider[$item['provider']];
        }

        $batcher = new BatchUpdater($this->connection);

        $updDate = date('Y-m-d H:i:s');
        $target = \AwardWallet\MainBundle\Service\MileValue\Constants::CLASS_MAP_UNKNOWN;
        $sqlInsertUpdate = /** @lang MySQL */ "
                INSERT INTO AirClassDictionary (ProviderIDs, AirlineCode, Source, FirstSeenDate, LastSeenDate, SourceFareClass, Target)
                    VALUES (:listOfProvs, :airline, :source, :date, :date, 2, :target)
                    ON DUPLICATE KEY UPDATE 
                        SourceFareClass = 2,
                        ProviderIDs = :listOfProvs,
                        LastSeenDate = IF (LastSeenDate IS NULL OR :date > LastSeenDate, :date , LastSeenDate),
                        FirstSeenDate = IF (FirstSeenDate IS NULL  OR :date < FirstSeenDate, :date , FirstSeenDate)
        ";

        $sqlUpdate = /** @lang MySQL */ "
                UPDATE AirClassDictionary SET 
                        ProviderIDs = :listOfProvs,           
                        SourceFareClass = 2,
                        LastSeenDate = IF (LastSeenDate IS NULL OR :date > LastSeenDate, :date , LastSeenDate),
                        FirstSeenDate = IF (FirstSeenDate IS NULL  OR :date < FirstSeenDate, :date , FirstSeenDate)
                WHERE AirlineCode = :airline AND Source = :source
        ";

        $sqlCheck = /** @lang MySQL */ "
                SELECT ProviderIDs FROM AirClassDictionary WHERE AirlineCode=:airline AND Source = :source
        ";
        $paramsInsUpd = [];
        $paramsUpd = [];

        foreach ($pairsToAdd as $key => $providers) {
            $data = explode('-', $key);
            $airline = array_shift($data);
            $class = implode('-', $data);
            $inTableProvIds = $seeInTable = $this->connection->executeQuery($sqlCheck, [
                'airline' => $airline,
                'source' => $class,
            ])->fetchOne();
            $listOfProviders = implode(',', $providers);

            if (false !== $inTableProvIds) {
                if (!empty($inTableProvIds)) {
                    $listOfProviders = implode(',',
                        array_unique(array_merge(explode(',', $inTableProvIds), $providers)));
                }
                $paramsUpd[] = [
                    'date' => $updDate,
                    'listOfProvs' => $listOfProviders,
                    'airline' => $airline,
                    'source' => $class,
                ];
            } else {
                $paramsInsUpd[] = [
                    'date' => $updDate,
                    'listOfProvs' => $listOfProviders,
                    'airline' => $airline,
                    'source' => $class,
                    'target' => $target,
                ];
            }
        }
        $batcher->batchUpdate($paramsUpd, $sqlUpdate, 0);
        $batcher->batchUpdate($paramsInsUpd, $sqlInsertUpdate, 0);
    }

    private function saveRouteSearch(string $providerCode, string $depCode, string $arrCode, string $cabin, string $date, bool $wasSaved, bool $emptyResult): void
    {
        $providerId = $this->connection->executeQuery(/** @lang MySQL */ "SELECT ProviderID FROM Provider WHERE Code = ?",
            [$providerCode], [\PDO::PARAM_STR])->fetchOne();
        $cacheKey = 'ra_route_search_' . $providerCode . $depCode . $arrCode . $cabin;

        $incExcluded = !$emptyResult && !$wasSaved ? 1 : 0;
        $incSaved = $wasSaved ? 1 : 0;

        if ($this->cache->get($cacheKey) !== false) {
            $sql = /** @lang MySQL */
                "
                UPDATE RAFlightRouteSearchVolume 
                SET LastSearch = IF (LastSearch IS NULL OR :date > LastSearch, :date , LastSearch),
                    TimesSearched = TimesSearched + 1,
                    Excluded = Excluded + :incExcluded,
                    Saved = Saved + :incSaved
                WHERE ProviderID=:provider 
                  AND DepartureAirport=:depCode 
                  AND ArrivalAirport=:arrCode
                  AND ClassOfService=:cabin
                 ";
        } else {
            $sql = /** @lang MySQL */
                "
                INSERT INTO RAFlightRouteSearchVolume (ProviderID, DepartureAirport, ArrivalAirport, ClassOfService, LastSearch, TimesSearched, Excluded, Saved)
                    VALUES (:provider, :depCode, :arrCode, :cabin, :date, 1, :incExcluded, :incSaved)
                    ON DUPLICATE KEY UPDATE 
                        LastSearch = IF (LastSearch IS NULL OR :date > LastSearch, :date , LastSearch),
                        TimesSearched = TimesSearched + 1,
                        Excluded = Excluded + :incExcluded,
                        Saved = Saved + :incSaved
                        ";
        }
        $params = [
            'provider' => $providerId,
            'depCode' => $depCode,
            'arrCode' => $arrCode,
            'cabin' => $cabin,
            'date' => $date,
            'incExcluded' => $incExcluded,
            'incSaved' => $incSaved,
        ];
        $this->connection->executeStatement($sql, $params);
        $this->cache->set($cacheKey, true, 86400 * 30);
    }

    private function createKeyFlightRoute(string $provider, Segment $segment): string
    {
        return sprintf('%s_%s_%s_%s_%s',
            $provider, $segment->getDepartAirport(), $segment->getArrivalAirport(),
            $segment->getCabin(), $segment->getAirlineCode());
    }

    private function parseKeyFlightRoute(string $key): ?array
    {
        $list = explode('_', $key);

        if (count($list) !== 5) {
            $this->logger->error('something wrong with key', ['key' => $key]);

            return null;
        }

        return array_combine(['provider', 'depCode', 'arrCode', 'cabin', 'airline'], $list);
    }

    private function saveFlightRoute(array $flightRoutes, string $date): void
    {
        $batcher = new BatchUpdater($this->connection);
        $sqlInsUpd = /** @lang MySQL */
            "
            INSERT INTO RAFlightSegment (ProviderID, DepartureAirport, ArrivalAirport, ClassOfService, Airline, TimesSeen, LastParsedDate)
            VALUES (:provider, :depCode, :arrCode, :cabin, :airline, :times, :date)
            ON DUPLICATE KEY UPDATE 
                LastParsedDate = IF (LastParsedDate IS NULL OR :date > LastParsedDate, :date , LastParsedDate),
                TimesSeen = TimesSeen + :times 
        ";
        $sqlUpd = /** @lang MySQL */
            "
            UPDATE RAFlightSegment
            SET LastParsedDate=:date, TimesSeen = TimesSeen + :times
            WHERE ProviderID = :provider
              AND DepartureAirport=:depCode 
              AND ArrivalAirport=:arrCode
              AND ClassOfService=:cabin 
              AND Airline=:airline
        ";
        $updCacheKeys = $paramsUpd = $paramsInsUpd = [];

        foreach ($flightRoutes as $key => $value) {
            $data = $this->parseKeyFlightRoute($key);

            if (!$data) {
                continue;
            }
            /** @var Provider $provider */
            $provider = $this->em->getRepository(Provider::class)->findOneBy(['code' => $data['provider']]);
            $data['provider'] = $provider->getId();
            $data['date'] = $date;
            $data['times'] = $value;
            $cacheKey = 'ra_flight_route_' . $key;

            if ($this->cache->get($cacheKey) !== false) {
                $paramsUpd[] = $data;
            } else {
                $paramsInsUpd[] = $data;
            }
            $updCacheKeys[] = $cacheKey;
        }

        if (!empty($paramsUpd)) {
            $batcher->batchUpdate($paramsUpd, $sqlUpd, 0);
        }

        if (!empty($paramsInsUpd)) {
            $batcher->batchUpdate($paramsInsUpd, $sqlInsUpd, 0);
        }
        $updCacheKeys = array_unique($updCacheKeys);

        foreach ($updCacheKeys as $cacheKey) {
            $this->cache->set($cacheKey, true, 86400 * 30);
        }
    }

    private function saveStats(array $stats, $lastSeen, $requestId): void
    {
        $batcher = new BatchUpdater($this->connection);
        $sqlInsUpd = /** @lang MySQL */ "
                INSERT INTO RAFlightStat (Provider, Carrier, FirstSeen, LastSeen) VALUES (:provider, :carrier, :date, :date)
                    ON DUPLICATE KEY UPDATE 
                        LastSeen = IF (LastSeen IS NULL OR :date > LastSeen, :date , LastSeen),
                        FirstSeen = IF (FirstSeen IS NULL  OR :date < FirstSeen, :date , FirstSeen)
        ";
        $sqlUpd = /** @lang MySQL */ "
                UPDATE RAFlightStat 
                    SET 
                        LastSeen = IF (LastSeen IS NULL OR :date > LastSeen, :date , LastSeen),
                        FirstSeen = IF (FirstSeen IS NULL  OR :date < FirstSeen, :date , FirstSeen)
                    WHERE Provider = :provider AND Carrier = :carrier
        ";
        $sqlCheck = /** @lang MySQL */ "
                SELECT 1 FROM RAFlightStat WHERE Provider = ? AND Carrier = ?
        ";

        foreach ($stats as $stat) {
            $seeInTable = $this->connection->executeQuery($sqlCheck, [$stat['provider'], $stat['carrier']])->fetchOne();

            if ($seeInTable) {
                $paramsUpd[] = ['provider' => $stat['provider'], 'carrier' => $stat['carrier'], 'date' => $lastSeen];
            } else {
                $paramsInsUpd[] = ['provider' => $stat['provider'], 'carrier' => $stat['carrier'], 'date' => $lastSeen];
            }
        }
        // notifications before batch to ignore existence
        $this->sendNotify($stats, $requestId);

        if (!empty($paramsUpd)) {
            $batcher->batchUpdate($paramsUpd, $sqlUpd, 0);
        }

        if (!empty($paramsInsUpd)) {
            $batcher->batchUpdate($paramsInsUpd, $sqlInsUpd, 0);
        }
        //        $this->connection->executeQuery(/** @lang MySQL */'ALTER TABLE RAFlightStat AUTO_INCREMENT = 1');
    }

    private function calcDistance(string $depart, string $arrive)
    {
        $dep = $this->connection->executeQuery("SELECT Lat, Lng FROM AirCode WHERE AirCode=:AirCode",
            ['AirCode' => $depart], [\PDO::PARAM_STR])->fetchAssociative();

        if (!$dep) {
            $dep = $this->connection->executeQuery("SELECT Lat, Lng FROM StationCode WHERE StationCode=:AirCode",
                ['AirCode' => $depart], [\PDO::PARAM_STR])->fetchAssociative();
        }
        $arr = $this->connection->executeQuery("SELECT Lat, Lng FROM AirCode WHERE AirCode=:AirCode",
            ['AirCode' => $arrive], [\PDO::PARAM_STR])->fetchAssociative();

        if (!$arr) {
            $arr = $this->connection->executeQuery("SELECT Lat, Lng FROM StationCode WHERE StationCode=:AirCode",
                ['AirCode' => $arrive], [\PDO::PARAM_STR])->fetchAssociative();
        }

        if (!$dep || is_null($dep['Lat']) || is_null($dep['Lng'])) {
            $this->logger->warning("no geolocation for airport", ['airport' => $depart]);
            $hasError = true;
        }

        if (!$arr || is_null($arr['Lat']) || is_null($arr['Lng'])) {
            $this->logger->warning("no geolocation for airport", ['airport' => $arrive]);
            $hasError = true;
        }

        if (isset($hasError)) {
            return 0;
        }

        return Geo::distance($dep['Lat'], $dep['Lng'], $arr['Lat'], $arr['Lng']);
    }

    private function getRegionDataByAirCode($airCode): array
    {
        $country = $this->checkCountry('AirCode', $airCode);

        if ($country === false) {
            $country = $this->checkCountry('StationCode', $airCode);
        }

        if ($country === false) {
            $this->logger->notice("no country for RAFlight", ['aircode' => $airCode]);

            return ['', ''];
        }

        if (!empty($country['StateName'])) {
            $data = $this->checkRegion('AirCode', $airCode, true);

            if ($data === false) {
                $data = $this->checkRegion('StationCode', $airCode, true);
            }

            if (is_array($data)) {
                return [$data['Name'], $country['Name']];
            }
        }

        $data = $this->checkRegion('AirCode', $airCode, false);

        if ($data === false) {
            $data = $this->checkRegion('StationCode', $airCode, false);
        }

        if (is_array($data)) {
            return [$data['Name'], $country['Name']];
        }

        return ['', $country['Name']];
    }

    private function checkCountry(string $tableName, string $airCode)
    {
        if (!in_array($tableName, ['AirCode', 'StationCode'])) {
            return false;
        }

        return $this->connection->executeQuery("
            SELECT DISTINCT c.Name, ac.StateName FROM {$tableName} ac
	            LEFT JOIN Country c ON (ac.CountryCode=c.Code)
	            LEFT JOIN State s ON (ac.State=s.Code AND ac.StateName=s.Name)
	            WHERE {$tableName}=:AirCode;
        ", ['AirCode' => $airCode], [\PDO::PARAM_STR])->fetchAssociative();
    }

    private function checkRegion(string $tableName, string $airCode, bool $byState)
    {
        if (!in_array($tableName, ['AirCode', 'StationCode'])) {
            return false;
        }

        if ($byState) {
            return $this->connection->executeQuery("
            SELECT Name FROM Region WHERE Kind = 1 AND RegionID IN (
                SELECT RegionID FROM RegionContent WHERE SubRegionID IN (
                    SELECT RegionID FROM Region WHERE StateID IN (
                        SELECT s.StateID FROM {$tableName} ac
	                        LEFT JOIN Country c ON (ac.CountryCode=c.Code)
	                        LEFT JOIN State s ON (ac.State=s.Code AND ac.StateName=s.Name)
	                        WHERE {$tableName}=:AirCode
                    )
                )
            );
        ", ['AirCode' => $airCode], [\PDO::PARAM_STR])->fetchAssociative();
        }

        return $this->connection->executeQuery("
            SELECT Name FROM Region WHERE Kind = 1 AND RegionID IN (
                SELECT RegionID FROM RegionContent WHERE SubRegionID IN (
                    SELECT RegionID FROM Region WHERE CountryID IN (
                        SELECT c.CountryID FROM {$tableName} ac
	                        LEFT JOIN Country c ON (ac.CountryCode=c.Code)
	                        LEFT JOIN State s ON (ac.State=s.Code AND ac.StateName=s.Name)
	                        WHERE {$tableName}=:AirCode
                    )
                )
            );
        ", ['AirCode' => $airCode], [\PDO::PARAM_STR])->fetchAssociative();
    }

    private function sendNotify(array $data, string $requestId): void
    {
        $message = [
            'text' => '',
            'blocks' => [],
        ];

        foreach ($data as $item) {
            $cacheKey = strtolower('raflightstat_' . $item['provider'] . '_' . $item['carrier']);
            $this->logger->debug('RAFlightStatNotify items ' . $cacheKey, ['type' => 'online']);

            if (false !== $this->cache->get($cacheKey)) {
                $this->logger->debug('RAFlightStatNotify cache exists ' . $cacheKey, ['type' => 'online']);

                continue;
            }

            /** @var Provider $provider */
            $provider = $this->em->getRepository(Provider::class)->findOneBy(['code' => $item['provider']]);

            /** @var Provider $carrier */
            $carrier = $this->em->getRepository(Provider::class)->findOneBy(['IATACode' => $item['carrier']]);

            if (null === $carrier) {
                $airline = $this->em->getRepository(Airline::class)->findOneBy(['code' => $item['carrier']]);

                if (null !== $airline) {
                    $carrierName = $airline->getName();
                    $carrierIataCode = $airline->getCode();
                    $carrierAlliance = false;
                }
            } else {
                $carrierName = $carrier->getDisplayname();
                $carrierIataCode = $carrier->getIATACode();
                $carrierAlliance = $carrier->getAllianceid() ? $carrier->getAllianceid()->getName() : false;
            }

            if (null === $provider || empty($carrierName)) {
                $this->logger->info('RAFlightStatNotify provider not found [' . $item['provider'] . '] - [' . $item['carrier'] . ']', ['type' => 'online']);

                continue;
            }

            $isExists = false !== $this->connection->fetchOne('SELECT 1 FROM RAFlightStat WHERE Provider = ? AND Carrier = ? AND FirstSeen IS NOT NULL',
                [$provider->getCode(), $carrierIataCode],
                [\PDO::PARAM_STR, \PDO::PARAM_STR]
            );

            if ($isExists) {
                $this->logger->debug('RAFlightStatNotify exists ' . $cacheKey, ['type' => 'online']);

                continue;
            }

            if (!isset($item['depDate'])) {
                $this->logger->notice("RAFlightStatNotify no depDate, RequestId: " . $requestId);
                $depDate = 'check log with requestID above';
            } else {
                $depDate = date('m/d/Y H:i:s', strtotime($item['depDate']));
            }

            if (!isset($item['standardSegmentCOS'])) {
                $this->logger->notice("RAFlightStatNotify no standardSegmentCOS, RequestId: " . $requestId);
                $standardSegmentCOS = ['check log with requestID above'];
            } else {
                $standardSegmentCOS = $item['standardSegmentCOS'];
            }

            $message['blocks'][] = [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => implode("\n", [
                        '*Alert: RAFlight - First Online*',
                        'Provider: ' . $provider->getDisplayname() . ' [code: ' . $provider->getCode() . ']',
                        'Provider Alliance: ' . ($provider->getAllianceid() ? $provider->getAllianceid()->getName() : '[null]'),
                        'Carrier: ' . $carrierName . ' [iata code: ' . $carrierIataCode . ']',
                        'Carrier Alliance: ' . ($carrierAlliance ?: '[null]'),
                        'First Seen: ' . date('m/d/Y H:i:s'),
                        'RequestId: ' . $requestId,
                        'From Airport: ' . $item['route'][0],
                        'To Airport: ' . end($item['route']),
                        'Route: ' . implode('-', $item['route']),
                        'Departure Date: ' . $depDate,
                        'Standard Segment COS: ' . implode('-', $standardSegmentCOS),
                    ]),
                ],
            ];
            $message['blocks'][] = [
                'type' => 'divider',
            ];

            $this->cache->set($cacheKey, true, 86400 * 30);
        }

        if (!empty($message['blocks'])) {
            $this->appBot->send(Slack::CHANNEL_AW_AWARD_ALERTS, $message);
        }
    }
}
