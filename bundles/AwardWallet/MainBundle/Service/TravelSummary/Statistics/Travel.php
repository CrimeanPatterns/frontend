<?php

namespace AwardWallet\MainBundle\Service\TravelSummary\Statistics;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Entity\Parking;
use AwardWallet\MainBundle\Entity\Rental;
use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\MainBundle\Entity\Restaurant;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Service\MileValue\LongHaulDetector;
use AwardWallet\MainBundle\Service\TravelSummary\Data\FlightStat;
use AwardWallet\MainBundle\Service\TravelSummary\Data\Reservation as ReservationModel;
use AwardWallet\MainBundle\Service\TravelSummary\Data\Trip as TripModel;

/**
 * A class used to calculate reservation statistics.
 *
 * @NoDI()
 */
class Travel implements \JsonSerializable
{
    /**
     * @var TripModel[] flights only
     */
    private array $flights;
    /**
     * @var ReservationModel[] hotel reservations
     */
    private array $hotels;
    /**
     * @var ReservationModel[] parking lots
     */
    private array $parkingLots;
    /**
     * @var TripModel[] car rental reservations
     */
    private array $rentals;
    /**
     * @var ReservationModel[] restaurants and all types of "Event"
     */
    private array $restaurants;
    /**
     * @var TripModel[] buses, trains, cruises, etc
     */
    private array $trips;
    private LongHaulDetector $longHaulDetector;

    public function __construct(
        array $flights,
        array $hotels,
        array $parkingLots,
        array $rentals,
        array $restaurants,
        array $trips,
        LongHaulDetector $longHaulDetector
    ) {
        $this->flights = $flights;
        $this->hotels = $hotels;
        $this->parkingLots = $parkingLots;
        $this->rentals = $rentals;
        $this->restaurants = $restaurants;
        $this->trips = $trips;
        $this->longHaulDetector = $longHaulDetector;
    }

    /**
     * Calculates data with flight statistics.
     */
    public function getFlightStats(): FlightStat
    {
        $flights = 0;
        $longHaulFlights = 0;

        foreach ($this->flights as $flight) {
            $flights++;

            if ($flight->getDeparture()->getAirCode() !== null && $flight->getArrival()->getAirCode() !== null) {
                $longHaulFlights += (int) $this->longHaulDetector->isLongHaulRoutes([
                    ['DepCode' => $flight->getDeparture()->getAirCode(), 'ArrCode' => $flight->getArrival()->getAirCode()],
                ]);
            }
        }

        return new FlightStat($flights, $longHaulFlights, $flights - $longHaulFlights);
    }

    public function getHotelNights(): int
    {
        $nights = 0;

        foreach ($this->hotels as $hotel) {
            $nights += Reservation::getNightCount($hotel->getStartDate(), $hotel->getEndDate());
        }

        return $nights;
    }

    public function getParkingDays(): int
    {
        $days = 0;

        foreach ($this->parkingLots as $parkingLot) {
            $days += Parking::getDayCount($parkingLot->getStartDate(), $parkingLot->getEndDate());
        }

        return $days;
    }

    public function getRentalCarDays(): int
    {
        $days = 0;

        foreach ($this->rentals as $rental) {
            $days += Rental::getDayCount($rental->getStartDate(), $rental->getEndDate());
        }

        return $days;
    }

    public function getEventsAttended(): int
    {
        $events = [];
        $this->collectReservations($events);

        return count($events);
    }

    public function getRestaurantReservations(): int
    {
        $reservations = [];
        $this->collectReservations($reservations, Restaurant::EVENT_RESTAURANT);

        return count($reservations);
    }

    public function getCruisesDays(): int
    {
        $days = 0;

        foreach ($this->trips as $trip) {
            $categoryName = strtolower(Trip::CATEGORY_NAMES[Trip::CATEGORY_CRUISE]);

            if ($trip->getDeparture()->getCategory() !== $categoryName) {
                continue;
            }

            $days += Trip::getDaysCount($trip->getStartDate(), $trip->getEndDate());
        }

        return $days;
    }

    public function getTotalBuses(): int
    {
        $buses = [];
        $this->collectTrips($buses, Trip::CATEGORY_BUS);

        return count($buses);
    }

    public function getTotalFerries(): int
    {
        $ferries = [];
        $this->collectTrips($ferries, Trip::CATEGORY_FERRY);

        return count($ferries);
    }

    public function getTotalTrains(): int
    {
        $trains = [];
        $this->collectTrips($trains, Trip::CATEGORY_TRAIN);

        return count($trains);
    }

    public function jsonSerialize(): array
    {
        $flightStats = $this->getFlightStats();

        return [
            'totalFlights' => [
                'value' => $flightStats->getTotalFlights(),
            ],
            'longHaulFlights' => [
                'value' => $flightStats->getLongHaulFlights(),
                'percentage' => $flightStats->getLongHaulPercentage(),
            ],
            'shortHaulFlights' => [
                'value' => $flightStats->getShortHaulFlights(),
                'percentage' => $flightStats->getShortHaulPercentage(),
            ],
            'hotelNights' => $this->getHotelNights(),
            'rentalCarDays' => $this->getRentalCarDays(),
            'parkingDays' => $this->getParkingDays(),
            'eventsAttended' => $this->getEventsAttended(),
            'restaurantReservations' => $this->getRestaurantReservations(),
            'cruisesDays' => $this->getCruisesDays(),
            'totalBuses' => $this->getTotalBuses(),
            'totalFerries' => $this->getTotalFerries(),
            'totalTrains' => $this->getTotalTrains(),
        ];
    }

    /**
     * Collects a list of reservation.
     *
     * @param array $data array of all events
     * @param int|null $eventType event type. If nothing is passed, collects all reservations except restaurants
     */
    private function collectReservations(array &$data, ?int $eventType = null): void
    {
        foreach ($this->restaurants as $restaurant) {
            $categoryName = strtolower(Restaurant::EVENT_TYPE_NAMES[Restaurant::EVENT_RESTAURANT]);

            if ($eventType === Restaurant::EVENT_RESTAURANT && $restaurant->getMarker()->getCategory() !== $categoryName) {
                continue;
            } elseif ($eventType === null && $restaurant->getMarker()->getCategory() === $categoryName) {
                continue;
            }

            $data[] = $restaurant->getSegmentId();
        }
    }

    /**
     * Collects a list of unique trip ids (or trip segment ids).
     *
     * @param array $data array of trip segments
     * @param int $categoryId reservation category id
     */
    private function collectTrips(array &$data, int $categoryId): void
    {
        foreach ($this->trips as $trip) {
            $categoryName = strtolower(Trip::CATEGORY_NAMES[$categoryId]);

            if ($trip->getDeparture()->getCategory() !== $categoryName) {
                continue;
            }

            $data[] = $trip->getSegmentId();
        }
    }
}
