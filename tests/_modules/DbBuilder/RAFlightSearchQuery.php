<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

use AwardWallet\MainBundle\Entity\RAFlightSearchQuery as BaseRAFlightSearchQuery;

class RAFlightSearchQuery extends AbstractDbEntity
{
    private ?User $user;

    private ?MileValue $mileValue;

    /**
     * @var RAFlightSearchRoute[]
     */
    private array $routes;

    /**
     * @param RAFlightSearchRoute[] $routes
     */
    public function __construct(
        array $departureAirports,
        array $arrivalAirports,
        \DateTime $depDateFrom,
        \DateTime $depDateTo,
        ?User $user = null,
        ?MileValue $mileValue = null,
        array $routes = [],
        array $fields = []
    ) {
        parent::__construct(array_merge([
            'FlightClass' => BaseRAFlightSearchQuery::FLIGHT_CLASS_ECONOMY,
            'SearchInterval' => BaseRAFlightSearchQuery::SEARCH_INTERVAL_DAILY,
        ], $fields, [
            'DepartureAirports' => json_encode($departureAirports),
            'ArrivalAirports' => json_encode($arrivalAirports),
            'DepDateFrom' => $depDateFrom->format('Y-m-d'),
            'DepDateTo' => $depDateTo->format('Y-m-d'),
        ]));

        $this->user = $user;
        $this->mileValue = $mileValue;
        $this->routes = $routes;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getMileValue(): ?MileValue
    {
        return $this->mileValue;
    }

    public function setMileValue(?MileValue $mileValue): self
    {
        $this->mileValue = $mileValue;

        return $this;
    }

    /**
     * @return RAFlightSearchRoute[]
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * @param RAFlightSearchRoute[] $routes
     */
    public function setRoutes(array $routes): self
    {
        $this->routes = $routes;

        return $this;
    }
}
