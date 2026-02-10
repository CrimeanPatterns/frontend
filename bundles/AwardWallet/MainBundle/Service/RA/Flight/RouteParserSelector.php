<?php

namespace AwardWallet\MainBundle\Service\RA\Flight;

use AwardWallet\MainBundle\Service\RA\Flight\DTO\ParserSelectorRequest;
use AwardWallet\MainBundle\Service\RA\Flight\DTO\ParserSelectorResponse;
use Doctrine\DBAL\Connection;

class RouteParserSelector
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param ParserSelectorRequest|ParserSelectorRequest[] $requests
     * @param array<string, int> $availableParsers key is provider code, value is provider id
     */
    public function getRouteParsers($requests, array $availableParsers): ParserSelectorResponse
    {
        if (!is_array($requests)) {
            $requests = [$requests];
        }

        $availableParsersIds = array_values($availableParsers);
        $availableParsers = array_keys($availableParsers);
        sort($availableParsers);

        $fullSearchStatQuery = $this->connection->createQueryBuilder();
        $e = $fullSearchStatQuery->expr();
        $fullSearchStatExprs = [];

        $routeSearchVolumeQuery = $this->connection->createQueryBuilder();
        $routeSearchVolumeExprs = [];

        foreach ($requests as $k => $request) {
            $routes = $request->getRoutes();
            $fromAirports = array_column($routes, 0);
            $toAirports = array_column($routes, 1);
            $fullSearchStatExprs[] = $e->and(
                $e->in('DepartureAirportCode', ':fromAirports' . $k),
                $e->in('ArrivalAirportCode', ':toAirports' . $k),
                $e->in('Period', ':periods' . $k),
                $e->in('FlightClass', ':flightClasses' . $k),
                $e->in('PassengersCount', ':passengersCounts' . $k)
            );
            $fullSearchStatQuery->setParameter('fromAirports' . $k, $fromAirports, Connection::PARAM_STR_ARRAY);
            $fullSearchStatQuery->setParameter('toAirports' . $k, $toAirports, Connection::PARAM_STR_ARRAY);
            $fullSearchStatQuery->setParameter('periods' . $k, array_map(fn (\DateTime $date) => $date->format('W'), $request->getDates()), Connection::PARAM_INT_ARRAY);
            $fullSearchStatQuery->setParameter('flightClasses' . $k, $request->getFlightClasses(), Connection::PARAM_STR_ARRAY);
            $fullSearchStatQuery->setParameter('passengersCounts' . $k, $request->getPassengersCount(), Connection::PARAM_INT_ARRAY);

            $routeSearchVolumeExprs[] = $e->and(
                $e->in('v.DepartureAirport', ':fromAirports' . $k),
                $e->in('v.ArrivalAirport', ':toAirports' . $k),
            );
            $routeSearchVolumeQuery->setParameter('fromAirports' . $k, $fromAirports, Connection::PARAM_STR_ARRAY);
            $routeSearchVolumeQuery->setParameter('toAirports' . $k, $toAirports, Connection::PARAM_STR_ARRAY);
        }

        $fullSearchStatQuery
            ->select([
                'DepartureAirportCode',
                'ArrivalAirportCode',
                'Period',
                'FlightClass',
                'PassengersCount',
            ])
            ->from('RaFlightFullSearchStat')
            ->where(
                $e->and(
                    $e->isNotNull('LastFullSearchDate'),
                    $e->gte('LastFullSearchDate', 'NOW() - INTERVAL 2 WEEK'),
                    $e->or(...$fullSearchStatExprs)
                )
            );
        $stmt = $fullSearchStatQuery->execute();
        $skipFullSearch = [];

        while ($row = $stmt->fetchAssociative()) {
            $skipFullSearch[implode('_', array_values($row))] = true;
        }

        $searchRoutes = [];

        $routeSearchVolumeQuery
            ->select([
                'p.Code',
                'v.DepartureAirport',
                'v.ArrivalAirport',
            ])
            ->from('RAFlightRouteSearchVolume', 'v')
            ->join('v', 'Provider', 'p', 'p.ProviderID = v.ProviderID')
            ->where(
                $e->and(
                    $e->in('v.ProviderID', ':availableParsersIds'),
                    $e->or(...$routeSearchVolumeExprs),
                    $e->or(
                        $e->gt('v.Saved', 0),
                        $e->gt('v.Excluded', 0)
                    )
                )
            )
            ->orderBy('p.Code')
            ->setParameter('availableParsersIds', $availableParsersIds, Connection::PARAM_INT_ARRAY);

        $stmt = $routeSearchVolumeQuery->execute();

        while ($row = $stmt->fetchAssociative()) {
            $dep = strtoupper($row['DepartureAirport']);
            $arr = strtoupper($row['ArrivalAirport']);

            if (!isset($searchRoutes[$dep][$arr])) {
                $searchRoutes[$dep][$arr] = [];
            }

            $searchRoutes[$dep][$arr][$row['Code']] = true;
        }

        $result = new ParserSelectorResponse();

        foreach ($requests as $request) {
            foreach ($request->getRoutes() as $route) {
                $fromAirport = $route[0];
                $toAirport = $route[1];

                foreach ($request->getDates() as $date) {
                    foreach ($request->getFlightClasses() as $flightClass) {
                        foreach ($request->getPassengersCount() as $passengersCount) {
                            $key = implode('_', [$fromAirport, $toAirport, $date->format('W'), $flightClass, $passengersCount]);

                            if (!isset($skipFullSearch[$key])) {
                                $result->addRoute(
                                    $fromAirport,
                                    $toAirport,
                                    $date,
                                    $flightClass,
                                    $passengersCount,
                                    $availableParsers,
                                    true
                                );
                            } else {
                                $result->addRoute(
                                    $fromAirport,
                                    $toAirport,
                                    $date,
                                    $flightClass,
                                    $passengersCount,
                                    isset($searchRoutes[$fromAirport][$toAirport])
                                        ? array_keys($searchRoutes[$fromAirport][$toAirport])
                                        : [],
                                    false
                                );
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }

    public function addSearch(
        string $depCode,
        string $arrCode,
        \DateTime $depDate,
        string $flightClass,
        int $passengersCount,
        bool $fullSearch
    ): void {
        if ($fullSearch) {
            $this->connection->executeStatement("
                INSERT INTO RaFlightFullSearchStat (DepartureAirportCode, ArrivalAirportCode, Period, FlightClass, PassengersCount, LastSearchDate, LastFullSearchDate)
                VALUES (:depCode, :arrCode, :period, :flightClass, :passengersCount, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    LastSearchDate = NOW(),
                    LastFullSearchDate = NOW()
            ", [
                'depCode' => $depCode,
                'arrCode' => $arrCode,
                'period' => $depDate->format('W'),
                'flightClass' => $flightClass,
                'passengersCount' => $passengersCount,
            ]);
        } else {
            $this->connection->executeStatement("
                INSERT INTO RaFlightFullSearchStat (DepartureAirportCode, ArrivalAirportCode, Period, FlightClass, PassengersCount, LastSearchDate, LastFullSearchDate)
                VALUES (:depCode, :arrCode, :period, :flightClass, :passengersCount, NOW(), NULL)
                ON DUPLICATE KEY UPDATE
                    LastSearchDate = NOW()
            ", [
                'depCode' => $depCode,
                'arrCode' => $arrCode,
                'period' => $depDate->format('W'),
                'flightClass' => $flightClass,
                'passengersCount' => $passengersCount,
            ]);
        }
    }
}
