<?php

namespace AwardWallet\MainBundle\Service\TravelSummary\Data;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\TravelSummary\Statistics\Distance;
use AwardWallet\MainBundle\Service\TravelSummary\Statistics\Travel;

/**
 * @NoDI
 */
class Summary implements \JsonSerializable
{
    /**
     * @var bool
     */
    protected $noData;
    /**
     * @var Item[]
     * @example [Item(key = "AA", title = "American Airlines", value = "3", payload = null), ...]
     */
    protected $airlines;
    /**
     * @var Item[]
     * @example [Item(key = "DE", value = "1", title = "Germany", payload = null), ...]
     */
    protected $countries;
    /**
     * @var Item
     * @example [Item(key => "MIA", value = "2", title = "Miami, FL", payload = Airport(...)), ...]
     */
    protected $airports;
    /**
     * @var Item[]
     */
    protected $reservations;
    /**
     * @var Totals
     */
    protected $totals;
    /**
     * @var Travel
     */
    protected $travelStatistics;
    /**
     * @var LocationStat
     */
    protected $locationStat;
    /**
     * @var Route[]
     */
    protected $routes;
    /**
     * @var Distance
     */
    protected $distance;
    /**
     * @var int year
     */
    protected $comparedTo;

    public function __construct(
        bool $noData,
        array $airlines,
        array $countries,
        array $airports,
        array $reservations,
        Totals $totals,
        Travel $travelStatistics,
        LocationStat $locationStat,
        array $routes,
        Distance $distance,
        int $comparedTo
    ) {
        $this->noData = $noData;
        $this->airlines = $airlines;
        $this->countries = $countries;
        $this->airports = $airports;
        $this->reservations = $reservations;
        $this->totals = $totals;
        $this->travelStatistics = $travelStatistics;
        $this->locationStat = $locationStat;
        $this->routes = $routes;
        $this->distance = $distance;
        $this->comparedTo = $comparedTo;
    }

    public function isNoData(): bool
    {
        return $this->noData;
    }

    public function getAirlines(): array
    {
        return $this->airlines;
    }

    public function getCountries(): array
    {
        return $this->countries;
    }

    public function getAirports(): array
    {
        return $this->airports;
    }

    public function getReservations(): array
    {
        return $this->reservations;
    }

    public function getTotals(): Totals
    {
        return $this->totals;
    }

    public function getTravelStatistics(): Travel
    {
        return $this->travelStatistics;
    }

    public function getLocationStat(): LocationStat
    {
        return $this->locationStat;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function getDistance(): Distance
    {
        return $this->distance;
    }

    public function getComparedTo(): int
    {
        return $this->comparedTo;
    }

    public function jsonSerialize()
    {
        return [
            'noData' => $this->noData,
            'airlines' => $this->airlines,
            'countries' => $this->countries,
            'airports' => $this->airports,
            'reservations' => $this->reservations,
            'totals' => $this->totals,
            'travelStatistics' => $this->travelStatistics,
            'locationStat' => $this->locationStat,
            'routes' => $this->routes,
            'distance' => $this->distance,
            'comparedTo' => $this->comparedTo,
        ];
    }
}
