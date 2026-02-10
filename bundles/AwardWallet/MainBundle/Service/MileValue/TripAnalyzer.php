<?php

namespace AwardWallet\MainBundle\Service\MileValue;

use AwardWallet\MainBundle\Globals\Geo;
use Psr\Log\LoggerInterface;

class TripAnalyzer
{
    private LoggerInterface $logger;
    private CabinClassMapper $cabinClassMapper;

    public function __construct(
        LoggerInterface $logger,
        CabinClassMapper $cabinClassMapper
    ) {
        $this->logger = $logger;
        $this->cabinClassMapper = $cabinClassMapper;
    }

    public function analyzeTripSegments(array $segments, int $tripId, bool $withDistance = true): array
    {
        $flightTime = 0;
        $layovers = [];
        $lastSegment = null;
        $routes = [];
        $startSegmentIndex = 0;
        $stopoverIndexes = [];
        $returnSegmentIndex = null;

        $distancesByClass = [];

        foreach ($segments as $segmentIndex => $segment) {
            $flightTime += $segment['ArrDateGmt'] - $segment['DepDateGmt'];

            if ($lastSegment !== null) {
                $layoverTime = $segment['DepDateGmt'] - $lastSegment['ArrDateGmt'];

                if ($layoverTime > 86400) {
                    // stopover
                    $routes[] = [
                        'DepCode' => $segments[$startSegmentIndex]['DepCityCode'],
                        'DepDate' => $segments[$startSegmentIndex]['DepDate'],
                        'ArrCode' => $lastSegment['ArrCityCode'],
                    ];
                    $startSegmentIndex = $segmentIndex;
                    $stopoverIndexes[] = $segmentIndex;
                } else {
                    $layovers[] = $layoverTime;
                }
            }

            $lastSegment = $segment;

            if ($withDistance) {
                $segment['Distance'] = Geo::distance($segment['DepLat'], $segment['DepLng'], $segment['ArrLat'], $segment['ArrLng']);
                $classOfService = $this->cabinClassMapper->cabinClassToClassOfService($segment['CabinClass'], $segment['OperatingAirlineCode'], $tripId) ?? 'unknown';
                $distancesByClass[$classOfService] = ($distancesByClass[$classOfService] ?? 0) + $segment['Distance'];
            }
        }

        $layoversTime = array_sum($layovers);
        $isRoundTrip = $segments[0]['DepCityCode'] === $segments[count($segments) - 1]['ArrCityCode'] && count($segments) > 1;

        if (count($routes) > 0) {
            // multi-city or round-trip
            // last, open route
            $routes[] = [
                'DepCode' => $segments[$startSegmentIndex]['DepCityCode'],
                'DepDate' => $segments[$startSegmentIndex]['DepDate'],
                'ArrCode' => $lastSegment['ArrCityCode'],
            ];
            $type = Constants::ROUTE_TYPE_MULTI_CITY;

            if ($isRoundTrip && count($routes) === 2) {
                $type = Constants::ROUTE_TYPE_ROUND_TRIP;
                $returnSegmentIndex = $startSegmentIndex;
            }
        } else {
            // round trip ?
            $returnDate = null;

            if ($isRoundTrip) {
                $maxLayover = max($layovers);
                $layoversTime -= $maxLayover;
                $returnSegmentIndex = array_search($maxLayover, $layovers) + 1;
                $returnSegment = $segments[$returnSegmentIndex];
                $type = Constants::ROUTE_TYPE_ROUND_TRIP;
                $routes[] = [
                    'DepCode' => $segments[0]['DepCityCode'],
                    'DepDate' => $segments[0]['DepDate'],
                    'ArrCode' => $returnSegment['DepCityCode'],
                ];
                $routes[] = [
                    'DepCode' => $returnSegment['DepCityCode'],
                    'DepDate' => $returnSegment['DepDate'],
                    'ArrCode' => $segments[0]['DepCityCode'],
                ];
            } else {
                $type = Constants::ROUTE_TYPE_ONE_WAY;
                $routes[] = [
                    'DepCode' => $segments[0]['DepCityCode'],
                    'DepDate' => $segments[0]['DepDate'],
                    'ArrCode' => $lastSegment['ArrCityCode'],
                ];
            }
        }

        // calc mile routes
        if ($type === Constants::ROUTE_TYPE_ROUND_TRIP) {
            $stopoverIndexes = [];
        }
        $mileRoutes = [];

        foreach ($segments as $segmentIndex => $segment) {
            if ($segmentIndex > 0) {
                if (in_array($segmentIndex, $stopoverIndexes)) {
                    $stopType = Constants::STOP_TYPE_STOP_OVER;
                } elseif ($segmentIndex === $returnSegmentIndex) {
                    $stopType = Constants::STOP_TYPE_RETURN;
                } else {
                    $stopType = Constants::STOP_TYPE_LAYOVER;
                }
                $this->logger->info("stopover $stopType from " . date("Y-m-d H:i", $segments[$segmentIndex - 1]["ArrDateGmt"]) . " to " . date("Y-m-d H:i", $segments[$segmentIndex]["DepDateGmt"]), ["TripID" => $tripId]);
                $mileRoutes[] = $stopType . ':' . TimeDiff::format($segments[$segmentIndex]["DepDateGmt"] - $segments[$segmentIndex - 1]["ArrDateGmt"]);
            }
            $mileRoutes[] = $segment['DepCode'] . '-' . $segment['ArrCode'];
        }

        $returnDate = null;

        if ($type === Constants::ROUTE_TYPE_ROUND_TRIP) {
            $returnDate = $routes[1]['DepDate'];
        }

        if ($type === Constants::ROUTE_TYPE_MULTI_CITY && count($stopoverIndexes) > 0) {
            $returnDate = $segments[$stopoverIndexes[count($stopoverIndexes) - 1]]["DepDate"];
        }

        return [
            round(($flightTime + $layoversTime) / 3600, 1),
            $type,
            $routes,
            implode(",", $mileRoutes),
            $withDistance ? $this->findClass($distancesByClass) : null,
            $returnDate,
        ];
    }

    public function fetchStops(string $routes): array
    {
        $stops = [];
        $routes = explode(',', $routes);
        $routeCodes = $routes[0] ?? null;

        foreach ($routes as $route) {
            if (false !== strpos($route, ':')) {
                $duration = str_replace(
                    ['d', 'h', 'm'],
                    ['day ', 'hour ', 'minute '],
                    substr($route, 3)
                );
                $stops[$routeCodes] = [
                    'type' => substr($route, 0, 2),
                    'duration' => $duration,
                    'code' => explode('-', $routeCodes)[1],
                ];
            }
            $routeCodes = $route;
        }

        return $stops;
    }

    private function findClass(array $distancesByClass): ?string
    {
        $totalDistance = array_sum($distancesByClass);
        unset($distancesByClass["unknown"]);

        if (count($distancesByClass) === 0) {
            return null;
        }

        arsort($distancesByClass);
        $class = array_keys($distancesByClass)[0];
        $distance = $distancesByClass[$class];

        if ($totalDistance === 0) {
            return null;
        }

        if (($distance / $totalDistance) < 0.75) {
            return null;
        }

        return $class;
    }
}
