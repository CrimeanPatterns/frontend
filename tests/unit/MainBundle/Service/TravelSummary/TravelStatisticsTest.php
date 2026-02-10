<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\TravelSummary;

use AwardWallet\MainBundle\Entity\Parking as ParkingEntity;
use AwardWallet\MainBundle\Entity\Rental as RentalEntity;
use AwardWallet\MainBundle\Entity\Reservation as ReservationEntity;
use AwardWallet\MainBundle\Entity\Restaurant as RestaurantEntity;
use AwardWallet\MainBundle\Entity\Trip as TripEntity;
use AwardWallet\MainBundle\Service\MileValue\LongHaulDetector;
use AwardWallet\MainBundle\Service\TravelSummary\Data\Marker;
use AwardWallet\MainBundle\Service\TravelSummary\Data\Reservation as ReservationModel;
use AwardWallet\MainBundle\Service\TravelSummary\Data\Trip as TripModel;
use AwardWallet\MainBundle\Service\TravelSummary\Statistics\Travel;
use AwardWallet\Tests\Unit\BaseContainerTest;

/**
 * @group frontend-unit
 */
class TravelStatisticsTest extends BaseContainerTest
{
    private const CATEGORY_AIR = 'air';
    private const CATEGORY_BUS = 'bus';
    private const CATEGORY_CRUISE = 'cruise';
    private const CATEGORY_EVENT = 'event';
    private const CATEGORY_FERRY = 'ferry';
    private const CATEGORY_HOTEL = 'hotel';
    private const CATEGORY_PARKING = 'parking';
    private const CATEGORY_RAVE = 'rave';
    private const CATEGORY_RENTAL = 'rental';
    private const CATEGORY_RESTAURANT = 'restaurant';
    private const CATEGORY_SHOW = 'show';
    private const CATEGORY_TRAIN = 'train';

    public function testHotelNightsCount()
    {
        $hotels[] = new ReservationModel(
            1,
            'Hampton Inn & Suites Flagstaff East',
            new \DateTime('2024-03-12 15:00:00'),
            new \DateTime('2024-03-14 11:00:00'),
            new Marker(35.2153, -111.5817, 'America/Phoenix', self::CATEGORY_HOTEL),
            ReservationEntity::SEGMENT_MAP_START
        );
        $hotels[] = new ReservationModel(
            2,
            'Sofitel Casablanca Tour Blanche',
            new \DateTime('2024-11-30 10:00:00'),
            new \DateTime('2024-12-04 11:00:00'),
            new Marker(33.5975, -7.6170, 'Africa/Casablanca', self::CATEGORY_HOTEL),
            ReservationEntity::SEGMENT_MAP_START
        );

        $helper = new Travel([], $hotels, [], [], [], [], $this->container->get(LongHaulDetector::class));
        $this->assertEquals(self::getStatistics()['hotelNights'], $helper->getHotelNights());
    }

    public function testRentalCarDaysCount()
    {
        $rentals[] = new TripModel(
            3,
            'National Car Rental',
            new \DateTime('2024-01-03 07:00:00'),
            new \DateTime('2024-01-08 14:00:00'),
            new Marker(36.0600, -115.1679, 'America/Los_Angeles', self::CATEGORY_RENTAL),
            new Marker(33.9549, -118.3791, 'America/Los_Angeles', self::CATEGORY_RENTAL),
            RentalEntity::SEGMENT_MAP_START
        );
        $rentals[] = new TripModel(
            4,
            'Europcar',
            new \DateTime('2024-02-06 11:00:00'),
            new \DateTime('2024-02-13 11:00:00'),
            new Marker(43.4384, 5.2118, 'Europe/Paris', self::CATEGORY_RENTAL),
            new Marker(43.6605, 7.2007, 'Europe/Paris', self::CATEGORY_RENTAL),
            RentalEntity::SEGMENT_MAP_START
        );

        $helper = new Travel([], [], [], $rentals, [], [], $this->container->get(LongHaulDetector::class));
        $this->assertEquals(self::getStatistics()['rentalCarDays'], $helper->getRentalCarDays());
    }

    public function testParkingDaysCount()
    {
        $parkingLots[] = new ReservationModel(
            5,
            'Park \'N Fly',
            new \DateTime('2024-01-14 11:00:00'),
            new \DateTime('2024-01-18 17:00:00'),
            new Marker(37.7277, -122.2001, 'America/Los_Angeles', self::CATEGORY_PARKING),
            ParkingEntity::SEGMENT_MAP_START,
        );
        $parkingLots[] = new ReservationModel(
            6,
            'PreFlight Parking',
            new \DateTime('2024-06-04 04:00:00'),
            new \DateTime('2024-06-10 02:00:00'),
            new Marker(41.9536, -87.8837, 'America/Chicago', self::CATEGORY_PARKING),
            ParkingEntity::SEGMENT_MAP_START,
        );

        $helper = new Travel([], [], $parkingLots, [], [], [], $this->container->get(LongHaulDetector::class));
        $this->assertEquals(self::getStatistics()['parkingDays'], $helper->getParkingDays());
    }

    public function testCruisesDaysCount()
    {
        $cruises[] = (new TripModel(
            7,
            'Royal Caribbean Cruise Line',
            new \DateTime('2024-03-31 15:30:00'),
            new \DateTime('2024-04-02 08:00:00'),
            new Marker(25.7616, -80.1917, 'America/New_York', self::CATEGORY_CRUISE),
            new Marker(20.4229, -86.9223, 'America/Cancun', self::CATEGORY_CRUISE),
            TripEntity::SEGMENT_MAP
        ))->setId(1);
        $cruises[] = (new TripModel(
            8,
            'Royal Caribbean Cruise Line',
            new \DateTime('2024-04-02 16:00:00'),
            new \DateTime('2024-04-03 08:30:00'),
            new Marker(20.4229, -86.9223, 'America/Cancun', self::CATEGORY_CRUISE),
            new Marker(17.1898, -88.4976, 'America/Belize', self::CATEGORY_CRUISE),
            TripEntity::SEGMENT_MAP
        ))->setId(1);
        $cruises[] = (new TripModel(
            9,
            'Royal Caribbean Cruise Line',
            new \DateTime('2024-04-03 17:00:00'),
            new \DateTime('2024-04-04 07:00:00'),
            new Marker(17.1898, -88.4976, 'America/Belize', self::CATEGORY_CRUISE),
            new Marker(18.7143, -87.7092, 'America/Cancun', self::CATEGORY_CRUISE),
            TripEntity::SEGMENT_MAP
        ))->setId(1);
        $cruises[] = (new TripModel(
            10,
            'Royal Caribbean Cruise Line',
            new \DateTime('2024-04-04 16:00:00'),
            new \DateTime('2024-04-06 08:00:00'),
            new Marker(18.7143, -87.7092, 'America/Cancun', self::CATEGORY_CRUISE),
            new Marker(25.7616, -80.1917, 'America/New_York', self::CATEGORY_CRUISE),
            TripEntity::SEGMENT_MAP
        ))->setId(1);

        $helper = new Travel([], [], [], [], [], $cruises, $this->container->get(LongHaulDetector::class));
        $this->assertEquals(self::getStatistics()['cruisesDays'], $helper->getCruisesDays());
    }

    public function testFerriesTakenCount()
    {
        $ferries[] = (new TripModel(
            11,
            'Caronte & Tourist',
            new \DateTime('2024-09-16 10:15:00'),
            new \DateTime('2024-09-16 10:35:00'),
            new Marker(38.2244, 15.6366, 'Europe/Rome', self::CATEGORY_FERRY),
            new Marker(38.1937, 15.5542, 'Europe/Rome', self::CATEGORY_FERRY),
            TripEntity::SEGMENT_MAP
        ))->setId(2);
        $ferries[] = (new TripModel(
            22,
            'Direct Ferries',
            new \DateTime('2024-08-05 18:40:00'),
            new \DateTime('2024-08-05 19:20:00'),
            new Marker(40.6278, 14.4869, 'Europe/Rome', self::CATEGORY_FERRY),
            new Marker(40.6263, 14.3764, 'Europe/Rome', self::CATEGORY_FERRY),
            TripEntity::SEGMENT_MAP
        ))->setId(5);

        $helper = new Travel([], [], [], [], [], $ferries, $this->container->get(LongHaulDetector::class));
        $this->assertEquals(self::getStatistics()['ferriesTaken'], $helper->getTotalFerries());
    }

    public function testBusRidesCount()
    {
        $buses[] = (new TripModel(
            12,
            'Grey Hound Bus',
            new \DateTime('2024-06-19 12:50:00'),
            new \DateTime('2024-06-19 15:30:00'),
            new Marker(57.4808, -4.2253, 'Europe/London', self::CATEGORY_BUS),
            new Marker(56.3949, -3.4308, 'Europe/London', self::CATEGORY_BUS),
            TripEntity::SEGMENT_MAP
        ))->setId(3);
        $buses[] = (new TripModel(
            13,
            'Grey Hound Bus',
            new \DateTime('2024-06-19 15:40:00'),
            new \DateTime('2024-06-19 17:00:00'),
            new Marker(56.3949, -3.4308, 'Europe/London', self::CATEGORY_BUS),
            new Marker(55.9554, -3.1913, 'Europe/London', self::CATEGORY_BUS),
            TripEntity::SEGMENT_MAP
        ))->setId(3);
        $buses[] = (new TripModel(
            14,
            'Grey Hound Bus',
            new \DateTime('2024-06-19 17:10:00'),
            new \DateTime('2024-06-19 19:30:00'),
            new Marker(55.9554, -3.1913, 'Europe/London', self::CATEGORY_BUS),
            new Marker(55.8589, -4.2593, 'Europe/London', self::CATEGORY_BUS),
            TripEntity::SEGMENT_MAP
        ))->setId(3);

        $helper = new Travel([], [], [], [], [], $buses, $this->container->get(LongHaulDetector::class));
        $this->assertEquals(self::getStatistics()['busRides'], $helper->getTotalBuses());
    }

    public function testRestaurantReservationsCount()
    {
        $restaurants[] = new ReservationModel(
            15,
            '1837 Bar & Brasserie at Guinness Storehouse',
            new \DateTime('2024-01-19 14:00:00'),
            null,
            new Marker(53.3433, -6.2895, 'Europe/Dublin', self::CATEGORY_RESTAURANT),
            RestaurantEntity::SEGMENT_MAP
        );
        // This reservation should not be included in the statistics!
        $restaurants[] = new ReservationModel(
            16,
            'Miami Ultra',
            new \DateTime('2024-03-24 13:00:00'),
            new \DateTime('2024-03-26 23:59:00'),
            new Marker(25.7752, -80.1861, 'America/New_York', self::CATEGORY_EVENT),
            RestaurantEntity::SEGMENT_MAP
        );

        $helper = new Travel([], [], [], [], $restaurants, [], $this->container->get(LongHaulDetector::class));
        $this->assertEquals(self::getStatistics()['restaurantReservations'], $helper->getRestaurantReservations());
    }

    public function testTrainRidesTakenCount()
    {
        $trains[] = (new TripModel(
            17,
            'Amtrak',
            new \DateTime('2024-06-14 10:30:00'),
            new \DateTime('2024-06-14 13:19:00'),
            new Marker(47.8084, -1.8283, 'Europe/Paris', self::CATEGORY_TRAIN),
            new Marker(48.8408, 2.3200, 'Europe/Paris', self::CATEGORY_TRAIN),
            TripEntity::SEGMENT_MAP
        ))->setId(4);
        $trains[] = (new TripModel(
            18,
            'Amtrak',
            new \DateTime('2024-06-15 10:50:00'),
            new \DateTime('2024-06-15 13:06:00'),
            new Marker(48.8408, 2.3200, 'Europe/Paris', self::CATEGORY_TRAIN),
            new Marker(47.8084, -1.8283, 'Europe/Paris', self::CATEGORY_TRAIN),
            TripEntity::SEGMENT_MAP
        ))->setId(4);

        $helper = new Travel([], [], [], [], [], $trains, $this->container->get(LongHaulDetector::class));
        $this->assertEquals(self::getStatistics()['trainRidesTaken'], $helper->getTotalTrains());
    }

    public function testEventsAttendedCount()
    {
        // This reservation should not be included in the statistics!
        $events[] = new ReservationModel(
            19,
            '1837 Bar & Brasserie at Guinness Storehouse',
            new \DateTime('2024-01-19 14:00:00'),
            null,
            new Marker(53.3433, -6.2895, 'Europe/Dublin', self::CATEGORY_RESTAURANT),
            RestaurantEntity::SEGMENT_MAP
        );
        $events[] = new ReservationModel(
            20,
            'I FEEL',
            new \DateTime('2024-06-16 11:00:00'),
            new \DateTime('2024-06-18 17:00:00'),
            new Marker(41.8399, -75.4079, 'America/New_York', self::CATEGORY_RAVE),
            RestaurantEntity::SEGMENT_MAP
        );
        $events[] = new ReservationModel(
            21,
            'AUDIEN',
            new \DateTime('2024-09-29 11:00:00'),
            null,
            new Marker(43.6457, -79.4048, 'America/Toronto', self::CATEGORY_SHOW),
            RestaurantEntity::SEGMENT_MAP
        );

        $helper = new Travel([], [], [], [], $events, [], $this->container->get(LongHaulDetector::class));
        $this->assertEquals(self::getStatistics()['eventsAttended'], $helper->getEventsAttended());
    }

    public function testFlightsCount()
    {
        $flights[] = (new TripModel(
            23,
            'Alaska Airlines',
            new \DateTime('2024-02-07 19:55:00'),
            new \DateTime('2024-02-07 22:41:00'),
            (new Marker(33.9433, -118.4082, 'America/Los_Angeles', self::CATEGORY_AIR))->setAirCode('LAX'),
            (new Marker(47.4438, -122.3017, 'America/Los_Angeles', self::CATEGORY_AIR))->setAirCode('SEA'),
            TripEntity::SEGMENT_MAP
        ))->setId(6);
        $flights[] = (new TripModel(
            24,
            'KLM',
            new \DateTime('2024-02-10 14:50:00'),
            new \DateTime('2024-02-11 09:45:00'),
            (new Marker(47.4438, -122.3017, 'America/Los_Angeles', self::CATEGORY_AIR))->setAirCode('SEA'),
            (new Marker(52.3090, 4.7633, 'Europe/Amsterdam', self::CATEGORY_AIR))->setAirCode('AMS'),
            TripEntity::SEGMENT_MAP
        ))->setId(7);
        $flights[] = (new TripModel(
            25,
            'KLM',
            new \DateTime('2024-02-11 12:25:00'),
            new \DateTime('2024-02-11 13:50:00'),
            (new Marker(52.3090, 4.7633, 'Europe/Amsterdam', self::CATEGORY_AIR))->setAirCode('AMS'),
            (new Marker(46.2296, 6.1057, 'Europe/Zurich', self::CATEGORY_AIR))->setAirCode('GVA'),
            TripEntity::SEGMENT_MAP
        ))->setId(7);
        $flights[] = (new TripModel(
            26,
            'KLM',
            new \DateTime('2024-02-18 07:10:00'),
            new \DateTime('2024-02-18 09:00:00'),
            (new Marker(46.2296, 6.1057, 'Europe/Zurich', self::CATEGORY_AIR))->setAirCode('GVA'),
            (new Marker(52.3090, 4.7633, 'Europe/Amsterdam', self::CATEGORY_AIR))->setAirCode('AMS'),
            TripEntity::SEGMENT_MAP
        ))->setId(7);

        $helper = new Travel($flights, [], [], [], [], [], $this->container->get(LongHaulDetector::class));
        $flightStats = $helper->getFlightStats();
        $this->assertEquals(self::getStatistics()['flightStats'], [
            'totalFlights' => $flightStats->getTotalFlights(),
            'longHaulFlights' => $flightStats->getLongHaulFlights(),
            'shortHaulFlights' => $flightStats->getShortHaulFlights(),
            'longHaulPercentage' => $flightStats->getLongHaulPercentage(),
            'shortHaulPercentage' => $flightStats->getShortHaulPercentage(),
        ]);
    }

    private static function getStatistics(): array
    {
        return [
            'hotelNights' => 6,
            'rentalCarDays' => 12,
            'parkingDays' => 10,
            'cruisesDays' => 6,
            'ferriesTaken' => 2,
            'busRides' => 3,
            'restaurantReservations' => 1,
            'trainRidesTaken' => 2,
            'eventsAttended' => 2,
            'flightStats' => [
                'totalFlights' => 4,
                'longHaulFlights' => 1,
                'shortHaulFlights' => 3,
                'longHaulPercentage' => 25,
                'shortHaulPercentage' => 75,
            ],
        ];
    }
}
