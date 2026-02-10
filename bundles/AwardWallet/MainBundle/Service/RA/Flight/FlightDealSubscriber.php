<?php

namespace AwardWallet\MainBundle\Service\RA\Flight;

use AwardWallet\MainBundle\Entity\RAFlightSearchQuery;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Service\AirportFinder;
use AwardWallet\MainBundle\Service\MileValue\CalcMileValueCommand;
use AwardWallet\MainBundle\Service\MileValue\Constants;
use AwardWallet\MainBundle\Service\MileValue\MileValueService;
use AwardWallet\MainBundle\Service\MileValue\ProviderMileValueItem;
use AwardWallet\MainBundle\Service\MileValue\TripLoaderFactory;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtAssoc;

class FlightDealSubscriber
{
    public const STAFF_USER_IDS = [7, 36521, 354954];

    private Connection $conn;

    private LoggerInterface $logger;

    private AirportFinder $airportFinder;

    private TripLoaderFactory $tripLoaderFactory;

    private MileValueService $mileValueService;

    private \Memcached $memcached;

    private bool $debug;

    public function __construct(
        Connection $conn,
        LoggerFactory $loggerFactory,
        AirportFinder $airportFinder,
        TripLoaderFactory $tripLoaderFactory,
        MileValueService $mileValueService,
        \Memcached $memcached,
        bool $debug
    ) {
        $this->conn = $conn;
        $this->logger = $loggerFactory->createLogger($loggerFactory->createProcessor([
            'class' => 'FlightDealSubscriber',
        ]));
        $this->airportFinder = $airportFinder;
        $this->tripLoaderFactory = $tripLoaderFactory;
        $this->mileValueService = $mileValueService;
        $this->memcached = $memcached;
        $this->debug = $debug;
    }

    /**
     * @return int|null id of created or existing search query
     */
    public function syncByMileValue(int $mileValueId): ?int
    {
        $mileValueData = $this->conn->executeQuery('
            SELECT * FROM MileValue WHERE MileValueID = :mileValueId
        ', ['mileValueId' => $mileValueId])->fetchAssociative();

        if ($mileValueData === false) {
            $this->logger->error(sprintf('MileValue #%d not found', $mileValueId));
            $this->removeRAQueryByMileValueId($mileValueId);

            return null;
        }

        if (empty($mileValueData['TripID'])) {
            $this->logger->error(sprintf('MileValue #%d has no trip', $mileValueId));
            $this->removeRAQueryByMileValueId($mileValueId);

            return null;
        }

        if ($mileValueData['Status'] == CalcMileValueCommand::STATUS_ERROR) {
            $this->logger->info(sprintf('MileValue #%d has invalid status "%s"', $mileValueId, $mileValueData['Status']));
            $this->removeRAQueryByMileValueId($mileValueId);

            return null;
        }

        if (!in_array($mileValueData['RouteType'], [Constants::ROUTE_TYPE_ONE_WAY])) {
            $this->logger->info(sprintf('MileValue #%d has invalid route type "%s"', $mileValueId, $mileValueData['RouteType']));
            $this->removeRAQueryByMileValueId($mileValueId);

            return null;
        }

        if ($this->hasStopover($mileValueData['MileRoute'])) {
            $this->logger->info(sprintf('MileValue #%d has stopover', $mileValueId));
            $this->removeRAQueryByMileValueId($mileValueId);

            return null;
        }

        $tripLoader = $this->tripLoaderFactory->createTripLoader()
            ->filterByTripIds([$mileValueData['TripID']])
            ->filterFutured()
            ->addFilter('
                t.Hidden = 0
                AND t.Cancelled = 0
                AND t.Category = ' . Trip::CATEGORY_AIR . "
                AND mv.MileValueID = $mileValueId
            ")
            ->setLimit(1);
        $tripData = it($tripLoader->load())->first();

        if (is_null($tripData)) {
            $this->logger->info(sprintf('MileValue #%d has no valid trip', $mileValueId));
            $this->removeRAQueryByMileValueId($mileValueId);

            return null;
        }

        if (empty($tripData['NewHash'])) {
            $this->logger->info(sprintf('MileValue #%d has no hash', $mileValueId));
            $this->removeRAQueryByMileValueId($mileValueId);

            return null;
        }

        $testTrueHash = $this->debug && $mileValueData['Hash'] === 'true_hash';

        if (!$testTrueHash && $tripData['NewHash'] !== $mileValueData['Hash']) {
            $this->logger->info(sprintf('MileValue #%d has invalid hash', $mileValueId));
            $this->removeRAQueryByMileValueId($mileValueId);

            return null;
        }

        if ($tripData['ProviderID'] != 26) {
            $this->logger->info(sprintf('trip #%d has provider id "%d"', $tripData['TripID'], $tripData['ProviderID']));
            $this->removeRAQueryByMileValueId($mileValueId);

            return null;
        }

        $userId = $tripData['UserID'];
        [$fromAirport, $toAirport] = $this->getDepAndArrCodes($mileValueData['MileRoute']);

        if ($fromAirport === $toAirport) {
            $this->logger->info(sprintf('MileValue #%d has same departure and arrival airports', $mileValueId));
            $this->removeRAQueryByMileValueId($mileValueId);

            return null;
        }

        $departureDate = $tripData['DepDateLocal'];
        $arrivalDate = $tripData['ArrDateLocal'];

        // departure must be in the future no closer than 1 month
        if ($departureDate < date_create('+30 day')) {
            $this->logger->info(sprintf('trip #%d has departure date in the past or too close', $tripData['TripID']));
            $this->removeRAQueryByMileValueId($mileValueId);

            return null;
        }

        // cabin
        $classOfService = $mileValueData['ClassOfService'];

        try {
            $cabin = $this->convertMileValueClassOfServiceToRAFlightSearchQueryClass($classOfService);
        } catch (\InvalidArgumentException $e) {
            $this->logger->error(sprintf('MileValue #%d: %s', $mileValueId, $e->getMessage()));
            $this->removeRAQueryByMileValueId($mileValueId);

            return null;
        }

        // TODO: after testing and release remove staff check
        //        $isStaff = $this->isStaff($userId);
        //
        //        // only for staff
        //        if (!$isStaff || (!in_array($userId, self::STAFF_USER_IDS) && !$this->debug)) {
        //            $this->removeRAQueryByMileValueId($mileValueId);
        //
        //            return null;
        //        }

        // check milevalue
        //        if (!$this->checkMileValue(
        //            $mileValueId,
        //            $mileValueData['MileValue'],
        //            $tripData['ProviderID'],
        //            $tripData['ClassOfService'],
        //            $tripData['International']
        //        )) {
        //            return null;
        //        }

        // TotalMilesSpent % 500 = 0
        if ($mileValueData['TotalMilesSpent'] % 500 !== 0) {
            $this->logger->info(sprintf('MileValue #%d has TotalMilesSpent %% 500 != 0', $mileValueId));
            $this->removeRAQueryByMileValueId($mileValueId);

            return null;
        }

        if (!$this->checkSearchHistory($tripData['ProviderID'], $fromAirport, $toAirport, $classOfService)) {
            $this->logger->info(sprintf('route %s-%s, class %s, providerId: %d has not enough search history', $fromAirport, $toAirport, $classOfService, $tripData['ProviderID']));
            $this->removeRAQueryByMileValueId($mileValueId);

            return null;
        }

        $mileCostLimit = $this->calculateMileCostLimit('mileageplus', $classOfService, $fromAirport, $toAirport);

        if (is_null($mileCostLimit)) {
            $this->logger->info(sprintf('MileValue #%d: no mile cost limit found', $mileValueId));
            $this->removeRAQueryByMileValueId($mileValueId);

            return null;
        }

        $mileCostLimit *= $mileValueData['TravelersCount'];

        if ($mileValueData['TotalMilesSpent'] <= $mileCostLimit) {
            $this->logger->info(sprintf('MileValue #%d: TotalMilesSpent "%d" is lower than limit "%d"', $mileValueId, $mileValueData['TotalMilesSpent'], $mileCostLimit));
            $this->removeRAQueryByMileValueId($mileValueId);

            return null;
        }

        $minTravelDuration = $this->getMinTravelDuration($classOfService, $fromAirport, $toAirport);

        if (is_null($minTravelDuration)) {
            $this->logger->info(sprintf('MileValue #%d: no min travel duration found', $mileValueId));
            $this->removeRAQueryByMileValueId($mileValueId);

            return null;
        }

        $bookedDuration = round(($arrivalDate->getTimestamp() - $departureDate->getTimestamp()) / 3600, 1);
        $minTravelDuration = round($minTravelDuration / 3600, 1);
        $travelDurationThreshold = $minTravelDuration / 0.7;

        if ($bookedDuration < $travelDurationThreshold) {
            $this->logger->info(sprintf('MileValue #%d: booked duration "%0.2f" is lower than threshold "%0.2f"1', $mileValueId, $bookedDuration, $travelDurationThreshold));
            $this->removeRAQueryByMileValueId($mileValueId);

            return null;
        }

        $minStops = $this->getMinStops($classOfService, $fromAirport, $toAirport);

        if (is_null($minStops)) {
            $this->logger->info(sprintf('MileValue #%d: no min stops found', $mileValueId));
            $this->removeRAQueryByMileValueId($mileValueId);

            return null;
        }

        $bookedStops = \count($tripData['Segments']) - 1;

        if ($bookedStops <= $minStops) {
            $this->logger->info(sprintf('MileValue #%d: booked stops "%d" is lower or equal than threshold "%d"', $mileValueId, $bookedStops, $minStops));
            $this->removeRAQueryByMileValueId($mileValueId);

            return null;
        }

        $queryData = $this->conn->executeQuery('
            SELECT * FROM RAFlightSearchQuery WHERE MileValueID = :mileValueId LIMIT 1
        ', ['mileValueId' => $mileValueId])->fetchAssociative();

        if ($queryData === false) {
            $this->logger->info(sprintf('creating search query for MileValue #%d, tripId #%d', $mileValueId, $tripData['TripID']));
            $queryData = [];
        } else {
            $this->logger->info(sprintf(
                'search query #%d for MileValue #%d, tripId #%d already exists',
                $queryData['RAFlightSearchQueryID'],
                $mileValueId,
                $tripData['TripID']
            ));
        }

        $queryData['UserID'] = null;
        $queryData['MileValueID'] = $mileValueId;

        // airports
        $nearestAirports = $this->findNearestAirports($fromAirport, $toAirport);
        $fromAirports = array_unique(array_merge($nearestAirports[$fromAirport] ?? [], [$fromAirport]));
        $toAirports = array_unique(array_merge($nearestAirports[$toAirport] ?? [], [$toAirport]));
        $queryData['DepartureAirports'] = json_encode($fromAirports);
        $queryData['ArrivalAirports'] = json_encode($toAirports);
        $queryData['DepDateFrom'] = (clone $departureDate)->modify('-1 day')->format('Y-m-d');
        $queryData['DepDateTo'] = (clone $departureDate)->modify('+1 day')->format('Y-m-d');
        $queryData['FlightClass'] = $cabin;
        $queryData['Adults'] = $mileValueData['TravelersCount'];
        $queryData['SearchInterval'] = RAFlightSearchQuery::SEARCH_INTERVAL_WEEKLY;
        // temporary search only by United provider
        $queryData['Parsers'] = 'mileageplus';
        $queryData['AutoSelectParsers'] = 0;
        //        $queryData['Parsers'] = null;
        //        $queryData['AutoSelectParsers'] = 1;

        $queryData['ExcludeParsers'] = null;
        $queryData['EconomyMilesLimit'] = null;
        $queryData['PremiumEconomyMilesLimit'] = null;
        $queryData['BusinessMilesLimit'] = null;
        $queryData['FirstMilesLimit'] = null;

        //        $totalMilesSpent = $mileValueData['TotalMilesSpent'];
        //        $milesPerPerson = (int) ceil($totalMilesSpent / $mileValueData['TravelersCount']);
        //        $milesPerPerson = (int) ceil($milesPerPerson * 0.8);
        //
        if ($cabin === RAFlightSearchQuery::FLIGHT_CLASS_ECONOMY) {
            $queryData['EconomyMilesLimit'] = $mileCostLimit;
        } elseif ($cabin === RAFlightSearchQuery::FLIGHT_CLASS_PREMIUM_ECONOMY) {
            $queryData['PremiumEconomyMilesLimit'] = $mileCostLimit;
        } elseif ($cabin === RAFlightSearchQuery::FLIGHT_CLASS_BUSINESS) {
            $queryData['BusinessMilesLimit'] = $mileCostLimit;
        } elseif ($cabin === RAFlightSearchQuery::FLIGHT_CLASS_FIRST) {
            $queryData['FirstMilesLimit'] = $mileCostLimit;
        }

        // duration
        $queryData['MaxTotalDuration'] = null;
        $queryData['MaxSingleLayoverDuration'] = null;
        $queryData['MaxTotalLayoverDuration'] = null;
        $queryData['MaxStops'] = null;
        $queryData['UpdateDate'] = date('Y-m-d H:i:s');
        $queryData['DeleteDate'] = null;

        if (empty($queryData['RAFlightSearchQueryID'])) {
            $this->conn->insert('RAFlightSearchQuery', $queryData);
            $queryId = $this->conn->lastInsertId();
        } else {
            $this->conn->update('RAFlightSearchQuery', $queryData, ['RAFlightSearchQueryID' => $queryData['RAFlightSearchQueryID']]);
            $queryId = $queryData['RAFlightSearchQueryID'];
        }

        return $queryId;
    }

    private function removeRAQueryByMileValueId(int $mileValueId): void
    {
        $deleted = $this->conn->executeStatement('
            UPDATE RAFlightSearchQuery 
            SET DeleteDate = NOW() 
            WHERE MileValueID = :mileValueId AND DeleteDate IS NULL
        ', ['mileValueId' => $mileValueId]);

        if ($deleted > 0) {
            $this->logger->info(sprintf('MileValue #%d: removed query', $mileValueId));
        }
    }

    /**
     * @param string $routes like 'JFK-HND,so:19d,NRT-SEA', 'PTY-LIM', 'YYZ-YOW,lo:2h45m,YOW-YVR'
     */
    private function hasStopover(string $routes): bool
    {
        return strpos($routes, sprintf(',%s:', Constants::STOP_TYPE_STOP_OVER)) !== false;
    }

    private function getDepAndArrCodes(string $routes): array
    {
        $routesWithoutStops = array_filter(
            explode(',', $routes),
            fn (string $route) => strpos($route, ':') === false
        );
        $routesWithoutStops = array_values(array_map(
            fn (string $route) => explode('-', $route),
            $routesWithoutStops
        ));

        return [
            $routesWithoutStops[0][0],
            $routesWithoutStops[count($routesWithoutStops) - 1][1],
        ];
    }

    private function findNearestAirports(string $fromAirport, string $toAirport): ?array
    {
        $airports = $this->conn->executeQuery('
            SELECT AirCode, Lat, Lng
            FROM AirCode
            WHERE AirCode IN (?)
        ', [[$fromAirport, $toAirport]], [Connection::PARAM_STR_ARRAY])->fetchAllAssociative();

        $airports = $this->airportFinder->findNearestAirports(
            array_map(
                fn (array $airport) => [
                    'id' => $airport['AirCode'],
                    'lat' => $airport['Lat'],
                    'lng' => $airport['Lng'],
                    'filter' => ' AND Classification IN (1, 2, 3) AND Popularity > 999',
                    'radius' => 100,
                ],
                $airports
            )
        );

        return it($airports)
            ->map(fn (array $airports) => array_map(
                fn (array $airport) => $airport['airport'],
                $airports
            ))
            ->toArrayWithKeys();
    }

    private function convertMileValueClassOfServiceToRAFlightSearchQueryClass(string $classOfService): int
    {
        switch ($classOfService) {
            case Constants::CLASS_BASIC_ECONOMY:
            case Constants::CLASS_ECONOMY:
                return RAFlightSearchQuery::FLIGHT_CLASS_ECONOMY;

            case Constants::CLASS_ECONOMY_PLUS:
            case Constants::CLASS_PREMIUM_ECONOMY:
                return RAFlightSearchQuery::FLIGHT_CLASS_PREMIUM_ECONOMY;

            case Constants::CLASS_BUSINESS:
                return RAFlightSearchQuery::FLIGHT_CLASS_BUSINESS;

            case Constants::CLASS_FIRST:
                return RAFlightSearchQuery::FLIGHT_CLASS_FIRST;
        }

        throw new \InvalidArgumentException(sprintf('Unknown class of service: %s', $classOfService));
    }

    private function convertMileValueClassOfServiceToRAFlightRouteSearchVolume(string $classOfService): string
    {
        switch ($classOfService) {
            case Constants::CLASS_BASIC_ECONOMY:
            case Constants::CLASS_ECONOMY:
                return 'economy';

            case Constants::CLASS_ECONOMY_PLUS:
            case Constants::CLASS_PREMIUM_ECONOMY:
                return 'premiumEconomy';

            case Constants::CLASS_BUSINESS:
                return 'business';

            case Constants::CLASS_FIRST:
                return 'firstClass';
        }

        throw new \InvalidArgumentException(sprintf('Unknown class of service: %s', $classOfService));
    }

    /**
     * TODO: remove in future if not needed.
     */
    private function checkMileValue(
        int $mileValueId,
        float $currentMileValue,
        int $providerId,
        string $classOfService,
        ?bool $isInternational
    ): bool {
        $mvDataList = $this->mileValueService->getFlatDataListById();

        if (empty($currentMileValue)) {
            $this->logger->info('no mile value for trip');
        }

        if (!isset($mvDataList[$providerId])) {
            $this->logger->info(sprintf('no provider id "%s" in mile value list', $providerId));
        }

        if ($currentMileValue > 0 && isset($mvDataList[$providerId])) {
            /** @var ProviderMileValueItem $mvItem */
            $mvItem = $mvDataList[$providerId];
            $avgMvMap = [
                MileValueService::PRIMARY_CALC_FIELD => $mvItem->getPrimaryValue(MileValueService::PRIMARY_CALC_FIELD),
            ];

            foreach (['RegionalEconomyMileValue', 'GlobalEconomyMileValue', 'RegionalBusinessMileValue', 'GlobalBusinessMileValue'] as $key) {
                $hasAvg =
                    ((($mvAvg = $mvItem->getPrimaryValue($key)) ?? 0) > 0)
                    && !$mvItem->isNotEnoughData($key);
                $avgMvMap[$key] = [
                    'has' => $hasAvg,
                    'value' => $mvAvg,
                ];
            }

            $isEconomy = in_array($classOfService, [
                Constants::CLASS_BASIC_ECONOMY,
                Constants::CLASS_ECONOMY,
                Constants::CLASS_ECONOMY_PLUS,
                Constants::CLASS_PREMIUM_ECONOMY,
            ]);

            if ($isEconomy && $isInternational) {
                $avgMv = $avgMvMap['GlobalEconomyMileValue']['has']
                    ? $avgMvMap['GlobalEconomyMileValue']['value']
                    : $avgMvMap[MileValueService::PRIMARY_CALC_FIELD];
            } elseif ($isEconomy && !$isInternational) {
                $avgMv = $avgMvMap['RegionalEconomyMileValue']['has']
                    ? $avgMvMap['RegionalEconomyMileValue']['value']
                    : $avgMvMap[MileValueService::PRIMARY_CALC_FIELD];
            } elseif (!$isEconomy && $isInternational) {
                $avgMv = $avgMvMap['GlobalBusinessMileValue']['has']
                    ? $avgMvMap['GlobalBusinessMileValue']['value']
                    : $avgMvMap[MileValueService::PRIMARY_CALC_FIELD];
            } else {
                $avgMv = $avgMvMap['RegionalBusinessMileValue']['has']
                    ? $avgMvMap['RegionalBusinessMileValue']['value']
                    : $avgMvMap[MileValueService::PRIMARY_CALC_FIELD];
            }

            // if current mile value is higher than average by 25%
            if ($currentMileValue > $avgMv * 1.25) {
                $this->logger->info(sprintf(
                    'milevalue #%d: current MileValue "%.2f" is higher than average "%.2f"',
                    $mileValueId,
                    $currentMileValue,
                    $avgMv
                ), [
                    'providerId' => $providerId,
                    'avgMvMap' => $avgMvMap,
                ]);
            } else {
                $this->logger->info(sprintf(
                    'milevalue #%d: current MileValue "%.2f" is lower than average "%.2f"',
                    $mileValueId,
                    $currentMileValue,
                    $avgMv
                ), [
                    'providerId' => $providerId,
                    'avgMvMap' => $avgMvMap,
                ]);
                $this->removeRAQueryByMileValueId($mileValueId);

                return false;
            }
        }

        return true;
    }

    /**
     * TODO: remove in future if not needed.
     */
    private function isStaff(int $userId): bool
    {
        return (bool) $this->conn->executeQuery("
            SELECT 
                1
            FROM
                GroupUserLink gl
                JOIN SiteGroup g ON gl.SiteGroupID = g.SiteGroupID AND g.GroupName = 'staff'
            WHERE
                gl.UserID = ?
        ", [$userId])->fetchOne();
    }

    private function checkSearchHistory(int $providerId, string $fromAirport, string $toAirport, string $classOfService): bool
    {
        $convertedClassOfService = $this->convertMileValueClassOfServiceToRAFlightRouteSearchVolume($classOfService);

        return $this->conn->executeQuery('
            SELECT 1
            FROM RAFlightRouteSearchVolume
            WHERE 
                ProviderID = :providerId
                AND DepartureAirport = :fromAirport
                AND ArrivalAirport = :toAirport
                AND ClassOfService = :classOfService
                AND TimesSearched > 25
                AND Saved > 0
        ', [
            'providerId' => $providerId,
            'fromAirport' => $fromAirport,
            'toAirport' => $toAirport,
            'classOfService' => $convertedClassOfService,
        ])->fetchOne() !== false;
    }

    private function calculateMileCostLimit(string $providerCode, string $classOfService, string $fromAirport, string $toAirport): ?int
    {
        $classOfService = $this->convertMileValueClassOfServiceToRAFlightRouteSearchVolume($classOfService);
        $countryFrom = $this->detectCountryByAirportCode($fromAirport);
        $countryTo = $this->detectCountryByAirportCode($toAirport);

        if (is_null($countryFrom) || is_null($countryTo)) {
            $this->logger->info(sprintf('no country data for airports "%s" and "%s"', $fromAirport, $toAirport));

            return null;
        }

        $countryFromNormalized = preg_replace('/\s+/', '_', mb_strtolower($countryFrom));
        $countryToNormalized = preg_replace('/\s+/', '_', mb_strtolower($countryTo));
        $cacheKey = sprintf('mileCostLimit_v2_%s_%s_%s_%s', $providerCode, $classOfService, $countryFromNormalized, $countryToNormalized);
        $cacheResult = $this->memcached->get($cacheKey);

        if ($cacheResult !== false) {
            $this->logger->info(sprintf('cache hit for key "%s" = %s', $cacheKey, $cacheResult ?? 'null'));

            return $cacheResult;
        }

        /** @var array|null $result */
        $result = stmtAssoc(
            $this->conn->executeQuery("
                SELECT MileCost, COUNT(*) AS Count
                FROM RAFlight
                WHERE Provider = :providerCode
                AND StandardItineraryCOS = :classOfService
                AND FromCountry = :fromCountry
                AND ToCountry = :toCountry
                AND SearchDate BETWEEN '2024-07-01' AND '2024-08-31'
                AND FlightType >= 2
                AND AwardType = 'Saver'
                AND MileCost IS NOT NULL
                GROUP BY MileCost
                ORDER BY MileCost
            ", [
                'providerCode' => $providerCode,
                'classOfService' => $classOfService,
                'fromCountry' => $countryFrom,
                'toCountry' => $countryTo,
            ])
        )
            ->usort(fn (array $a, array $b) => $b['Count'] <=> $a['Count'])
            ->first();

        if (is_null($result)) {
            $this->logger->info(sprintf('no data for key "%s"', $cacheKey));
            $this->memcached->set($cacheKey, null, 3600 * 24);

            return null;
        }

        $mileCostLimit = (int) $result['MileCost'] / 0.8;
        $this->logger->info(sprintf('found data for key "%s" = %s, limit = %s', $cacheKey, $result['MileCost'], $mileCostLimit));
        $this->memcached->set($cacheKey, $mileCostLimit, 3600 * 24);

        return $mileCostLimit;
    }

    private function detectCountryByAirportCode(string $airportCode): ?string
    {
        $cacheKey = sprintf('raflight_countryByAirportCode_%s', $airportCode);
        $cacheResult = $this->memcached->get($cacheKey);

        if ($cacheResult !== false) {
            $this->logger->info(sprintf('cache hit for key "%s" = %s', $cacheKey, $cacheResult ?? 'null'));

            return $cacheResult;
        }

        $country = $this->conn->fetchOne('
            SELECT FromCountry
            FROM RAFlight
            WHERE FromAirport = :airportCode AND FromCountry IS NOT NULL AND FromCountry <> ""
            LIMIT 1
        ', ['airportCode' => $airportCode]);

        if (!empty($country)) {
            $this->logger->info(sprintf('found data for key "%s" = %s', $cacheKey, $country));
            $this->memcached->set($cacheKey, $country, 3600 * 24 * 30);

            return $country;
        }

        $country = $this->conn->fetchOne('
            SELECT ToCountry
            FROM RAFlight
            WHERE ToAirport = :airportCode AND FromCountry IS NOT NULL AND FromCountry <> ""
            LIMIT 1
        ', ['airportCode' => $airportCode]);

        if (!empty($country)) {
            $this->logger->info(sprintf('found data for key "%s" = %s', $cacheKey, $country));
            $this->memcached->set($cacheKey, $country, 3600 * 24 * 30);

            return $country;
        }

        return null;
    }

    private function getMinTravelDuration(string $classOfService, string $fromAirport, string $toAirport): ?int
    {
        $classOfService = $this->convertMileValueClassOfServiceToRAFlightRouteSearchVolume($classOfService);
        $startDate = date_create('-6 months');
        $cacheKey = sprintf('raflight_minTravelDuration_%s_%s_%s_%s', $classOfService, $fromAirport, $toAirport, $startDate->format('Y-m-d'));
        $cacheResult = $this->memcached->get($cacheKey);

        if ($cacheResult !== false) {
            $this->logger->info(sprintf('cache hit for key "%s" = %s', $cacheKey, $cacheResult ?? 'null'));

            return $cacheResult;
        }

        $minTravelTime = $this->conn->fetchOne('
            SELECT MIN(TravelTime) AS MinTravelTime
            FROM RAFlight 
            WHERE FromAirport = :fromAirport 
                AND ToAirport = :toAirport 
                AND StandardItineraryCOS = :classOfService
                AND SearchDate >= :startDate
        ', [
            'fromAirport' => $fromAirport,
            'toAirport' => $toAirport,
            'classOfService' => $classOfService,
            'startDate' => $startDate->format('Y-m-d'),
        ]);

        if ($minTravelTime !== false && !is_null($minTravelTime)) {
            $minTravelTime = (int) $minTravelTime * 60;
            $this->logger->info(sprintf('found data for key "%s" = %s', $cacheKey, $minTravelTime));
            $this->memcached->set($cacheKey, $minTravelTime, 3600 * 24);

            return $minTravelTime;
        }

        $lastFlights = $this->conn->executeQuery('
            SELECT TravelTime
            FROM RAFlight 
            WHERE FromAirport = :fromAirport 
                AND ToAirport = :toAirport 
                AND StandardItineraryCOS = :classOfService
            ORDER BY SearchDate DESC
            LIMIT 20
        ', [
            'fromAirport' => $fromAirport,
            'toAirport' => $toAirport,
            'classOfService' => $classOfService,
        ])->fetchAllAssociative();

        if (count($lastFlights) > 0) {
            $minDuration = (int) min(array_column($lastFlights, 'TravelTime')) * 60;
            $this->logger->info(sprintf('found data for key "%s" = %s', $cacheKey, $minDuration));
            $this->memcached->set($cacheKey, $minDuration, 3600 * 24);

            return $minDuration;
        }

        $this->logger->info(sprintf('no data for key "%s"', $cacheKey));
        $this->memcached->set($cacheKey, null, 3600 * 24);

        return null;
    }

    private function getMinStops(string $classOfService, string $fromAirport, string $toAirport): ?int
    {
        $classOfService = $this->convertMileValueClassOfServiceToRAFlightRouteSearchVolume($classOfService);
        $startDate = date_create('-6 months');
        $cacheKey = sprintf('raflight_minStops_%s_%s_%s_%s', $fromAirport, $toAirport, $classOfService, $startDate->format('Y-m-d'));
        $cacheResult = $this->memcached->get($cacheKey);

        if ($cacheResult !== false) {
            $this->logger->info(sprintf('cache hit for key "%s" = %s', $cacheKey, $cacheResult ?? 'null'));

            return $cacheResult;
        }

        $minStops = $this->conn->fetchOne('
            SELECT MIN(Stopovers + Layovers) AS MinStops
            FROM RAFlight 
            WHERE FromAirport = :fromAirport 
                AND ToAirport = :toAirport 
                AND StandardItineraryCOS = :classOfService
                AND SearchDate >= :startDate
        ', [
            'fromAirport' => $fromAirport,
            'toAirport' => $toAirport,
            'classOfService' => $classOfService,
            'startDate' => $startDate->format('Y-m-d'),
        ]);

        if ($minStops !== false && !is_null($minStops)) {
            $this->logger->info(sprintf('found data for key "%s" = %s', $cacheKey, $minStops));
            $this->memcached->set($cacheKey, $minStops, 3600 * 24);

            return (int) $minStops;
        }

        $lastFlights = $this->conn->executeQuery('
            SELECT Stopovers + Layovers AS Stops
            FROM RAFlight 
            WHERE FromAirport = :fromAirport 
                AND ToAirport = :toAirport 
                AND StandardItineraryCOS = :classOfService
            ORDER BY SearchDate DESC
            LIMIT 20
        ', [
            'fromAirport' => $fromAirport,
            'toAirport' => $toAirport,
            'classOfService' => $classOfService,
        ])->fetchAllAssociative();

        if (count($lastFlights) > 0) {
            $minStops = (int) min(array_column($lastFlights, 'Stops'));
            $this->logger->info(sprintf('found data for key "%s" = %s', $cacheKey, $minStops));
            $this->memcached->set($cacheKey, $minStops, 3600 * 24);

            return $minStops;
        }

        $this->logger->info(sprintf('no data for key "%s"', $cacheKey));
        $this->memcached->set($cacheKey, null, 3600 * 24);

        return null;
    }
}
