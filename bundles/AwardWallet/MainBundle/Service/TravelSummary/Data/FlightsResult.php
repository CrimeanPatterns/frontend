<?php

namespace AwardWallet\MainBundle\Service\TravelSummary\Data;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class FlightsResult
{
    /**
     * @var Item[] airports with geographical coordinates, including flight segments
     */
    private array $airports;
    /**
     * @var Item[] airlines with flight statistics
     */
    private array $airlines;
    /**
     * @var Route[] flight routes
     */
    private array $routes;

    public function __construct(array $airports, array $airlines, array $routes)
    {
        $this->airports = $airports;
        $this->airlines = $airlines;
        $this->routes = $routes;
    }

    public function getAirports(): array
    {
        return $this->airports;
    }

    public function getAirlines(): array
    {
        return $this->airlines;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }
}
