<?php

namespace AwardWallet\MainBundle\Service\TravelSummary\Statistics;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Globals\Geo;
use AwardWallet\MainBundle\Service\TravelSummary\Data\DistanceResult;
use AwardWallet\MainBundle\Service\TravelSummary\Data\Trip as TripModel;

/**
 * A class used to calculate the distance of all trips.
 *
 * @NoDI()
 */
class Distance
{
    /**
     * @var TripModel[] flights only
     */
    private array $flights;
    /**
     * @var TripModel[] car rental reservations
     */
    private array $rentals;
    /**
     * @var TripModel[] buses, trains, cruises, etc
     */
    private array $trips;

    public function __construct(
        array $flights,
        array $rentals,
        array $trips
    ) {
        $this->flights = $flights;
        $this->rentals = $rentals;
        $this->trips = $trips;
    }

    public function getData(): DistanceResult
    {
        $distance = 0;

        foreach (array_merge($this->flights, $this->rentals, $this->trips) as $trip) {
            $dep = $trip->getDeparture();
            $arr = $trip->getArrival();

            if (
                $trip->getDeparture()->getCategory() === 'rental'
                && ($dep->getLatitude() . ',' . $dep->getLongitude() === $arr->getLatitude() . ',' . $arr->getLongitude())
            ) {
                continue;
            }

            $distance += Geo::distance($dep->getLatitude(), $dep->getLongitude(), $arr->getLatitude(), $arr->getLongitude());
        }

        return new DistanceResult($distance, round($distance / 26000, 1));
    }
}
