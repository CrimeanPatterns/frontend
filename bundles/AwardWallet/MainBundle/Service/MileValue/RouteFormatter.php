<?php

namespace AwardWallet\MainBundle\Service\MileValue;

use AwardWallet\Common\Airport\AirportTime;
use AwardWallet\MainBundle\Service\AirportCity;
use AwardWallet\MainBundle\Service\MileValue\PriceSource\ResultRoute;
use AwardWallet\MainBundle\Service\MileValue\PriceSource\SearchRoute;

class RouteFormatter
{
    private AirportTime $airportTime;

    private AirportCity $airportCity;

    public function __construct(
        AirportTime $airportTime,
        AirportCity $airportCity
    ) {
        $this->airportTime = $airportTime;
        $this->airportCity = $airportCity;
    }

    /**
     * @param ResultRoute[] $foundRoutes
     * @param SearchRoute[] $searchRoutes
     */
    public function format(array $foundRoutes, array $searchRoutes, string $routeType)
    {
        $stops = array_unique(array_reduce($searchRoutes, function (array $carry, SearchRoute $route): array {
            return array_merge($carry, [$route->depCode, $route->arrCode]);
        }, []));

        $lastCity = null;
        $lastArrival = null;

        if ($routeType === Constants::ROUTE_TYPE_ROUND_TRIP) {
            $cityStopType = "rt";
        } else {
            $cityStopType = "so";
        }

        return array_reduce($foundRoutes, function (string $carry, ResultRoute $route) use ($stops, &$lastCity, &$lastArrival, $cityStopType): string {
            if ($carry !== "") {
                if ($lastCity !== null) {
                    if (in_array($lastCity, $stops)) {
                        $stopType = $cityStopType;
                    } else {
                        $stopType = "lo";
                    }
                    $carry .= ",{$stopType}:" . TimeDiff::format($this->airportTime->convertToGmt($route->depDate, $route->depCode) - $lastArrival);
                }
                $carry .= ",";
            }

            $lastCity = $this->airportCity->findCity($route->arrCode);
            $lastArrival = $this->airportTime->convertToGmt($route->arrDate, $route->arrCode);

            return "{$carry}{$route->depCode}-{$route->arrCode}";
        }, '');
    }
}
