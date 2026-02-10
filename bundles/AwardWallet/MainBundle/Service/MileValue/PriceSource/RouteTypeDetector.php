<?php

namespace AwardWallet\MainBundle\Service\MileValue\PriceSource;

use AwardWallet\MainBundle\Service\MileValue\Constants;

class RouteTypeDetector
{
    /**
     * @param SearchRoute[] $routes
     * @return string - one of Constants::ROUTE_TYPES
     */
    public static function detect(array $routes): string
    {
        if (count($routes) === 1) {
            return Constants::ROUTE_TYPE_ONE_WAY;
        }

        if (count($routes) === 2 && $routes[count($routes) - 1]->arrCode === $routes[0]->depCode) {
            return Constants::ROUTE_TYPE_ROUND_TRIP;
        }

        return Constants::ROUTE_TYPE_MULTI_CITY;
    }
}
