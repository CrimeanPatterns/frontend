<?php

namespace AwardWallet\MainBundle\Service\MileValue;

use AwardWallet\Common\CurrencyConverter\CurrencyConverter;
use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Psr\Log\LoggerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtAssoc;

/**
 * @NoDI
 */
class TripLoader
{
    private const VERSION_HASH_PREFIX = 'v3';

    private const EXCLUDED_PROVIDERS = ['maxmilhas'];

    private QueryBuilder $builder;

    private CurrencyConverter $currencyConverter;

    private TripAnalyzer $tripAnalyzer;

    private LongHaulDetector $longHaulDetector;

    private ?LoggerInterface $logger;

    private ?int $limit = null;

    private array $providerLowcosterList = [];

    private $onHasUndefinedClassOfService;

    public function __construct(
        QueryBuilder $builder,
        CurrencyConverter $currencyConverter,
        TripAnalyzer $tripAnalyzer,
        LongHaulDetector $longHaulDetector,
        ?LoggerInterface $logger = null
    ) {
        $this->builder = $builder;
        $this->currencyConverter = $currencyConverter;
        $this->tripAnalyzer = $tripAnalyzer;
        $this->longHaulDetector = $longHaulDetector;
        $this->logger = $logger;
    }

    /**
     * @param int[] $tripIds
     */
    public function filterByTripIds(array $tripIds): self
    {
        $this->log(sprintf('filter by trip ids: %s', implode(', ', $tripIds)));
        $this->builder
            ->andWhere('t.TripID IN (:tripIds)')
            ->setParameter('tripIds', $tripIds, Connection::PARAM_INT_ARRAY);

        return $this;
    }

    public function filterByStartMileValueId(int $startMileValueId): self
    {
        $this->log(sprintf('filter by start mile value id: %d', $startMileValueId));
        $this->builder
            ->andWhere('mv.MileValueID >= :startMileValueId')
            ->setParameter('startMileValueId', $startMileValueId, \PDO::PARAM_INT);

        return $this;
    }

    public function filterByCreatedOrReservatedAfter(\DateTime $dateTime): self
    {
        $this->log(sprintf('filter by created or reservated after: %s', $dateTime->format('Y-m-d H:i:s')));
        $this->builder
            ->andWhere('t.CreateDate >= :date OR t.ReservationDate >= :date')
            ->setParameter('date', $dateTime->format('Y-m-d H:i:s'), \PDO::PARAM_STR);

        return $this;
    }

    public function filterWithStatusError(): self
    {
        $this->log('filter with status error');
        $this->builder
            ->andWhere('mv.Status = :errorStatus')
            ->setParameter('errorStatus', CalcMileValueCommand::STATUS_ERROR, \PDO::PARAM_STR);

        return $this;
    }

    public function filterWithStatusNew(): self
    {
        $this->log('filter with status new');
        $this->builder
            ->andWhere('mv.Status = :newStatus')
            ->setParameter('newStatus', CalcMileValueCommand::STATUS_NEW, \PDO::PARAM_STR);

        return $this;
    }

    public function filterByProviderCode(string $providerCode): self
    {
        $this->log(sprintf('filter by provider code: %s', $providerCode));
        $this->builder
            ->andWhere('p.Code = :providerCode')
            ->setParameter('providerCode', $providerCode, \PDO::PARAM_STR);

        return $this;
    }

    public function filterFutured(): self
    {
        $this->log('filter futured');
        $this->builder
            ->andWhere('ts.DepDate > NOW()');

        return $this;
    }

    public function filterWithoutMileValue(): self
    {
        $this->log('filter without mile value');
        $this->builder
            ->andWhere('mv.MileValueID IS NULL');

        return $this;
    }

    public function filterWithMileValue(): self
    {
        $this->log('filter with mile value');
        $this->builder
            ->andWhere('mv.MileValueID IS NOT NULL');

        return $this;
    }

    public function filterParsed(): self
    {
        $this->log('filter parsed');
        $this->builder
            ->andWhere('t.Parsed = 1');

        return $this;
    }

    public function filterWithAlternativeCost(): self
    {
        $this->log('filter with alternative cost');
        $this->builder
            ->andWhere('mv.AlternativeCost IS NOT NULL');

        return $this;
    }

    public function addFilter(string $filter): self
    {
        $this->log(sprintf('add filter: %s', $filter));
        $this->builder
            ->andWhere($filter);

        return $this;
    }

    /**
     * @param int|null $limit Maximum number of trips to load
     */
    public function setLimit(?int $limit): self
    {
        $this->log(sprintf('limited to %d trips', $limit ?? INF));
        $this->limit = $limit;

        return $this;
    }

    /**
     * @param int[] $providerLowcosterList provider IDs of lowcosters
     */
    public function setProviderLowcosterList(array $providerLowcosterList): self
    {
        $this->log(sprintf('set provider lowcoster list: %s', implode(', ', $providerLowcosterList)));
        $this->providerLowcosterList = $providerLowcosterList;

        return $this;
    }

    public function setOnHasUndefinedClassOfService(?callable $onHasUndefinedClassOfService): self
    {
        $this->onHasUndefinedClassOfService = $onHasUndefinedClassOfService;

        return $this;
    }

    public function load(): iterable
    {
        $this->builder
            ->select("
                t.TripID,
                t.RecordLocator,
                p.ProviderID,
                t.UserID,
                u.Login AS UserLogin,
                ua.Alias AS UserAgentAlias,
                t.TravelerNames,
                mv.TravelersCount,
                COALESCE(t.SpentAwards, SUM(ABS(h.Miles))) AS SpentAwards,
                t.SpentAwards AS TripSpentAwards,
                SUM(ABS(h.Miles)) AS HistoryMiles,
                t.Cost,
                t.Discount,
                mv.MileValueID,
                mv.AlternativeCost,
                mv.Status,
                mv.MileRoute AS OldMileRoute,
                mv.DepDate AS OldDepDate,
                mv.ClassOfService AS OldClassOfService,
                mv.IgnoredChanges,
                GREATEST(t.CreateDate, COALESCE(t.ReservationDate, t.CreateDate)) AS BookingDate,
                COALESCE(t.Total, 0) AS Total,
                COALESCE(t.CurrencyCode, 'USD') AS CurrencyCode,
                mv.TotalSpentInLocalCurrency,
                mv.LocalCurrency,
                mv.TotalTaxesSpent,
                mv.DataSourceStates,
                mv.MileValue,
    
                CASE WHEN da.CityCode <> '' THEN da.CityCode ELSE ts.DepCode END AS DepCityCode,
                ts.DepCode,
                ts.ScheduledDepDate AS DepDate,
                CASE WHEN aa.CityCode <> '' THEN aa.CityCode ELSE ts.ArrCode END AS ArrCityCode,
                ts.ArrCode,
                ts.ScheduledArrDate AS ArrDate,
                ts.BookingClass,
                ts.FlightNumber,
                ts.OperatingAirlineFlightNumber,
                COALESCE(ts.CabinClass, t.CabinClass, '') AS CabinClass,
                a.Code AS OperatingAirlineCode,
                mv.Hash,
    
                dgt.TimeZoneLocation AS DepLocation,
                agt.TimeZoneLocation AS ArrLocation,
                
                da.CountryCode AS DepCountryCode,
                aa.CountryCode AS ArrCountryCode,
                
                da.Lat AS DepLat,
                da.Lng AS DepLng,
                aa.Lat AS ArrLat,
                aa.Lng AS ArrLng
            ")
            ->from('
                TripSegment ts
                JOIN Trip t ON ts.TripID = t.TripID
                JOIN Provider p ON t.SpentAwardsProviderID = p.ProviderID
                JOIN Usr u ON t.UserID = u.UserID
                LEFT JOIN UserAgent ua ON t.UserAgentID = ua.UserAgentID
                
                JOIN AirCode da ON ts.DepCode = da.AirCode
                JOIN AirCode aa ON ts.ArrCode = aa.AirCode
    
                JOIN GeoTag dgt ON ts.DepGeoTagID = dgt.GeoTagID
                JOIN GeoTag agt ON ts.ArrGeoTagID = agt.GeoTagID
                
                LEFT JOIN MileValue mv ON t.TripID = mv.TripID
                
                LEFT JOIN Airline a ON COALESCE(ts.AirlineID, ts.OperatingAirlineID, t.AirlineID) = a.AirlineID
    
                LEFT JOIN HistoryToTripLink htl ON t.TripID = htl.TripID
                LEFT JOIN AccountHistory h ON htl.HistoryID = h.UUID
            ')
            ->andWhere('
                t.SpentAwardsProviderID IS NOT NULL
                AND ts.DepCode IS NOT NULL
                AND ts.ArrCode IS NOT NULL
                AND ts.Hidden = 0
                AND p.Code NOT IN (:excludedProviders)
                AND COALESCE(t.SpentAwards, h.Miles) IS NOT NULL
            ')
            ->groupBy('
                t.TripID,
                t.RecordLocator,
                p.ProviderID,
                t.UserID,
                u.Login,
                ua.Alias,
                t.TravelerNames,
                t.SpentAwards,
                t.Cost,
                t.Discount,
                mv.MileValueID,
                mv.AlternativeCost,
                mv.Status,
                Total,
                CurrencyCode,
                mv.TotalSpentInLocalCurrency,
                mv.LocalCurrency,
                mv.TotalTaxesSpent,
                mv.DataSourceStates,
    
                DepCityCode,
                ts.DepCode,
                ts.ScheduledDepDate,
                ArrCityCode,
                ts.ArrCode,
                ts.ScheduledArrDate,
                ts.BookingClass,
                CabinClass,
                OperatingAirlineCode,
                mv.Hash,
    
                DepLocation,
                ArrLocation,
                
                DepCountryCode,
                ArrCountryCode,
                
                DepLat,
                DepLng,
                ArrLat,
                ArrLng,
                ts.FlightNumber,
                ts.OperatingAirlineFlightNumber
            ')
            ->setParameter('excludedProviders', self::EXCLUDED_PROVIDERS, Connection::PARAM_STR_ARRAY);

        $this->log('sql: ' . $this->builder->getSQL());

        return stmtAssoc($this->builder->execute())
            ->map(function (array $segment) {
                $segment['SpentAwards'] = filterBalance($segment['SpentAwards'], true);

                return $segment;
            })
            ->filter(fn (array $segment) => $segment['SpentAwards'] > 0)
            ->map(function (array $segment) {
                $segment['DepDateLocal'] = $this->createDateTime($segment['DepDate'], $segment['DepLocation']);
                $segment['DepDateGmt'] = $segment['DepDateLocal']->getTimestamp();
                $segment['ArrDateLocal'] = $this->createDateTime($segment['ArrDate'], $segment['ArrLocation']);
                $segment['ArrDateGmt'] = $segment['ArrDateLocal']->getTimestamp();

                return $segment;
            })
            ->usort(function (array $segmentA, array $segmentB) {
                return [
                    $segmentA['TripID'],
                    $segmentA['DepDateGmt'],
                ] <=> [
                    $segmentB['TripID'],
                    $segmentB['DepDateGmt'],
                ];
            })
            ->groupAdjacentBy(function (array $segmentA, array $segmentB) {
                return $segmentA['TripID'] <=> $segmentB['TripID'];
            })
            ->reindexByPropertyPath('[0][TripID]')
            ->map(function (array $segments) {
                $firstSegment = $segments[0];
                $trip = [
                    'TripID' => $firstSegment['TripID'],
                    'RecordLocator' => $firstSegment['RecordLocator'],
                    'UserID' => $firstSegment['UserID'],
                    'UserLogin' => $firstSegment['UserLogin'],
                    'UserAgentAlias' => $firstSegment['UserAgentAlias'],
                    'ProviderID' => $firstSegment['ProviderID'],
                    'TravelerNames' => $firstSegment['TravelerNames'],
                    'TravelersCount' => $firstSegment['TravelersCount'],
                    'SpentAwards' => $firstSegment['SpentAwards'],
                    'TripSpentAwards' => $firstSegment['TripSpentAwards'],
                    'HistoryMiles' => $firstSegment['HistoryMiles'],
                    'Hash' => $firstSegment['Hash'],
                    'MileValue' => $firstSegment['MileValue'],
                    'Cost' => $firstSegment['Cost'],
                    'AlternativeCost' => $firstSegment['AlternativeCost'],
                    'MileValueID' => $firstSegment['MileValueID'],
                    'Status' => $firstSegment['Status'],
                    'Discount' => $firstSegment['Discount'],
                    'Total' => $firstSegment['Total'],
                    'TripTotal' => $firstSegment['Total'],
                    'TotalSpentInLocalCurrency' => $firstSegment['TotalSpentInLocalCurrency'],
                    'LocalCurrency' => $firstSegment['LocalCurrency'],
                    'TotalTaxesSpent' => $firstSegment['TotalTaxesSpent'],
                    'CurrencyCode' => $firstSegment['CurrencyCode'],
                    'OldDepDate' => $firstSegment['OldDepDate'],
                    'OldMileRoute' => $firstSegment['OldMileRoute'],
                    'OldClassOfService' => $firstSegment['OldClassOfService'],
                    'IgnoredChanges' => $firstSegment['IgnoredChanges'],
                    'BookingDate' => $firstSegment['BookingDate'],
                    'DataSourceStates' => $firstSegment['DataSourceStates'],
                    'Segments' => array_values(array_map(function (array $segment) {
                        return array_intersect_key($segment, [
                            'DepCode' => true,
                            'DepCityCode' => true,
                            'ArrCode' => true,
                            'ArrCityCode' => true,
                            'DepDate' => true,
                            'ArrDate' => true,
                            'DepDateGmt' => true,
                            'DepDateLocal' => true,
                            'ArrDateGmt' => true,
                            'ArrDateLocal' => true,
                            'BookingClass' => true,
                            'CabinClass' => true,
                            'OperatingAirlineCode' => true,
                            'DepCountryCode' => true,
                            'ArrCountryCode' => true,
                            'DepLat' => true,
                            'DepLng' => true,
                            'ArrLat' => true,
                            'ArrLng' => true,
                            'FlightNumber' => true,
                            'OperatingAirlineFlightNumber' => true,
                        ]);
                    }, $segments)),
                ];

                if ($trip['CurrencyCode'] !== 'USD') {
                    if ($trip['TotalSpentInLocalCurrency'] === $trip['Total'] && $trip['LocalCurrency'] === $trip['CurrencyCode'] && !is_null($trip['TotalTaxesSpent'])) {
                        // do repeatedly not convert price to USD, to prevent total changed caused by currency exchange rates fluctuations
                        $trip['Total'] = $trip['TotalTaxesSpent'];
                    } else {
                        $trip['Total'] = $this->currencyConverter->convertToUsd($trip['Total'], $trip['CurrencyCode']);
                    }
                }

                $trip['HasUndefinedClassOfService'] = it($trip['Segments'])
                    ->any(function (array $segment) {
                        $classOfService = $segment['CabinClass'];

                        // assume that all spirit airlines flights are basic economy
                        if (empty($classOfService) && in_array($segment['OperatingAirlineCode'], Constants::ASSUME_BASIC_ECONOMY)) {
                            $classOfService = Constants::CLASS_BASIC_ECONOMY;
                        }

                        return empty($classOfService);
                    });

                if ($trip['HasUndefinedClassOfService']) {
                    return $trip;
                }

                $firstSegment = $trip['Segments'][0];
                $trip['DepDateGmt'] = $firstSegment['DepDateGmt'];
                $trip['DepDateLocal'] = $firstSegment['DepDateLocal'];
                $trip['DepDate'] = $firstSegment['DepDate'];
                $trip['ArrDateLocal'] = $trip['Segments'][count($trip['Segments']) - 1]['ArrDateLocal'];
                [
                    $trip['Duration'],
                    $trip['RouteType'],
                    $trip['Routes'],
                    $trip['MileRoute'],
                    $trip['ClassOfService'],
                    $trip['ReturnDate']
                ] = $this->tripAnalyzer->analyzeTripSegments($trip['Segments'], $trip['TripID']);
                $trip['Route'] = implode(',', array_map(function (array $route) {
                    return $route['DepCode'] . '-' . $route['ArrCode'];
                }, $trip['Routes']));

                $bookingClasses = array_map(function (array $segment) {
                    return $segment['BookingClass'];
                }, $trip['Segments']);
                $bookingClasses = array_unique($bookingClasses);
                sort($bookingClasses);
                $trip['BookingClasses'] = implode(',', $bookingClasses);

                $operatingAirlineCodes = array_map(function (array $segment) {
                    return (string) $segment['OperatingAirlineCode'];
                }, $trip['Segments']);

                if (array_unique($operatingAirlineCodes) === ['']) {
                    $trip['OperatingAirlineCodes'] = null;
                } else {
                    $trip['OperatingAirlineCodes'] = implode(',', $operatingAirlineCodes);
                }

                $trip['International'] = $this->longHaulDetector->isLongHaulRoutes($trip['Routes']);
                $trip['Passengers'] = substr_count($trip['TravelerNames'], ',') + 1;
                $trip['Lowcoster'] = in_array($trip['ProviderID'], $this->providerLowcosterList);

                if (!is_null($trip['ClassOfService'])) {
                    $trip['NewHash'] = $this->getHash(
                        $trip['DepDate'],
                        $trip['Route'],
                        $trip['RouteType'],
                        $trip['BookingClasses'],
                        $trip['ClassOfService'],
                        $trip['ReturnDate'],
                        $trip['Passengers'],
                        $trip['CurrencyCode'],
                        $trip['MileRoute']
                    );
                }

                if (!is_null($trip['TripSpentAwards'])) {
                    $trip['MilesSource'] = Constants::MILE_SOURCE_TRIP;
                }

                if (is_null($trip['TripSpentAwards']) && !is_null($trip['HistoryMiles'])) {
                    $trip['MilesSource'] = Constants::MILE_SOURCE_ACCOUNT_HISTORY;
                }

                return $trip;
            })
            // skip rows with failed currency conversion
            ->filter(function (array $trip) {
                if ($trip['HasUndefinedClassOfService']) {
                    if ($this->onHasUndefinedClassOfService) {
                        ($this->onHasUndefinedClassOfService)($trip);
                    }

                    return false;
                }

                //                if (!isset($trip['NewHash'])) {
                //                    return false;
                //                }

                $hasInternational = !is_null($trip['International']);

                if (!$hasInternational) {
                    $this->log(sprintf('skipping trip %d because we could not detect is it international or not', $trip['TripID']));

                    return false;
                }

                return !is_null($trip['Total']);
            })
            ->slice(0, $this->limit ?? INF);
    }

    /**
     * @param string $depDate scheduled departure date of the first segment, example: 2011-12-25 22:20:00
     * @param string $route flight routes separated by commas (city codes, airport codes in their absence)
     *  example: "JFK-LAX,LAX-SFO"
     * @param string $routeType MC, RT, OW. \AwardWallet\MainBundle\Service\MileValue\Constants::ROUTE_TYPES
     * @param string $bookingClasses Unique sorted by segments service classes separated by commas.
     *  example: "X,XN,YN"
     * @param string $classOfService The class that dominates >= 75% of the total number of distances
     * @param string $returnDate scheduled return date of the last segment of round trip or before stopover, example: 2011-12-25 22:20:00
     * @param int $passengersCount number of passengers
     * @param string $currencyCode currency code
     * @param string $mileRoute flight routes (airport codes) with information about layover and stopover
     *  example: "PTY-LIM", "JFK-HND,so:19d,NRT-SEA", "YYZ-YOW,lo:2h45m,YOW-YVR", "LGA-ATL,lo:1h25m,ATL-LIR,rt:3d,LIR-ATL,lo:2h8m,ATL-LGA"
     */
    public function getHash(
        string $depDate,
        string $route,
        string $routeType,
        string $bookingClasses,
        string $classOfService,
        ?string $returnDate,
        int $passengersCount,
        string $currencyCode,
        string $mileRoute
    ): string {
        return md5(
            self::VERSION_HASH_PREFIX
            . $depDate
            . $route
            . $routeType
            . $bookingClasses
            . $classOfService
            . $returnDate ?? ''
            . $passengersCount
            . $currencyCode
            . $mileRoute
        );
    }

    private function log(string $msg, array $context = []): void
    {
        if (!$this->logger) {
            return;
        }

        $this->logger->info($msg, $context);
    }

    private function createDateTime(string $date, string $location): \DateTime
    {
        return new \DateTime($date, $this->getTimeZone($location));
    }

    private function getTimeZone(string $tzName): \DateTimeZone
    {
        try {
            return new \DateTimeZone($tzName);
        } catch (\Exception $e) {
            return new \DateTimeZone('UTC');
        }
    }
}
