<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\TravelSummary;

use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Parking as ParkingEntity;
use AwardWallet\MainBundle\Entity\Rental as RentalEntity;
use AwardWallet\MainBundle\Entity\Reservation as ReservationEntity;
use AwardWallet\MainBundle\Entity\Restaurant as RestaurantEntity;
use AwardWallet\MainBundle\Entity\Trip as TripEntity;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\DateTimeInterval\Formatter;
use AwardWallet\MainBundle\Service\TravelSummary\Data\Marker;
use AwardWallet\MainBundle\Service\TravelSummary\Data\Reservation as ReservationModel;
use AwardWallet\MainBundle\Service\TravelSummary\Data\Trip as TripModel;
use AwardWallet\MainBundle\Service\TravelSummary\DataProvider\Flight as FlightProvider;
use AwardWallet\MainBundle\Service\TravelSummary\DataProvider\Hotel as HotelProvider;
use AwardWallet\MainBundle\Service\TravelSummary\DataProvider\Parking as ParkingProvider;
use AwardWallet\MainBundle\Service\TravelSummary\DataProvider\Rental as RentalProvider;
use AwardWallet\MainBundle\Service\TravelSummary\DataProvider\Restaurant as RestaurantProvider;
use AwardWallet\MainBundle\Service\TravelSummary\DataProvider\Trip as TripProvider;
use AwardWallet\Tests\Modules\DbBuilder\AirCode;
use AwardWallet\Tests\Modules\DbBuilder\Airline;
use AwardWallet\Tests\Modules\DbBuilder\GeoTag;
use AwardWallet\Tests\Modules\DbBuilder\Parking;
use AwardWallet\Tests\Modules\DbBuilder\Provider;
use AwardWallet\Tests\Modules\DbBuilder\Rental;
use AwardWallet\Tests\Modules\DbBuilder\Reservation;
use AwardWallet\Tests\Modules\DbBuilder\Restaurant;
use AwardWallet\Tests\Modules\DbBuilder\Trip;
use AwardWallet\Tests\Modules\DbBuilder\TripSegment;
use AwardWallet\Tests\Modules\DbBuilder\User;
use AwardWallet\Tests\Unit\BaseContainerTest;

/**
 * @group frontend-unit
 */
class DataProviderTest extends BaseContainerTest
{
    /**
     * Sets of properties in classes related to reservations.
     */
    private const PROPERTIES_LIST = [
        'Reservation' => ['title', 'startDate', 'endDate', 'city', 'country', 'countryCode', 'marker', 'prefix'],
        'Trip' => ['title', 'airlineCode', 'startDate', 'endDate', 'departure', 'arrival', 'prefix'],
        'Marker' => ['city', 'country', 'stateCode', 'countryCode', 'latitude', 'longitude', 'timeZone', 'category', 'airCode', 'locationName'],
    ];

    private ?User $user;
    private string $randomString = '';

    public function _before()
    {
        parent::_before();
        $this->user = new User();
        $this->randomString = StringUtils::getPseudoRandomString(8);
    }

    public function _after()
    {
        $this->user = null;
        $this->randomString = '';
        parent::_after();
    }

    public function testGetBuses()
    {
        $tripSegment = new TripSegment(
            null, 'Berlin central bus station', new \DateTime('2024-03-04 15:00:00'),
            null, 'Prague (Central Bus Station Florenc)', new \DateTime('2024-03-04 19:40:00')
        );
        $tripSegment->setDepGeoTag(
            new GeoTag(
                'Masurenallee 4-6, 14057 Berlin-' . $this->randomString,
                [
                    'Lat' => 52.5071,
                    'Lng' => 13.2768,
                    'TimeZoneLocation' => 'Europe/Berlin',
                    'City' => 'Berlin',
                    'State' => 'Berlin',
                    'Country' => 'Germany',
                    'StateCode' => 'BE',
                    'CountryCode' => 'DE',
                ]
            )
        );
        $tripSegment->setArrGeoTag(
            new GeoTag(
                'Praha, ÚAN Florenc Křižíkova 2110 2b, 186 00 Praha-' . $this->randomString,
                [
                    'Lat' => 50.0896,
                    'Lng' => 14.4391,
                    'TimeZoneLocation' => 'Europe/Prague',
                    'City' => 'Prague',
                    'State' => 'Hlavní město Praha',
                    'Country' => 'Czechia',
                    'StateCode' => null,
                    'CountryCode' => 'CZ',
                ]
            )
        );
        $this->dbBuilder->makeTrip(
            new Trip(
                '310 620 0000-' . $this->randomString,
                [$tripSegment],
                $this->user,
                ['Category' => TripEntity::CATEGORY_BUS]
            )
        );

        $dataProvider = new TripProvider($this->em);
        $userEntity = $this->em->getRepository(Usr::class)->find($this->user->getId());
        $result = $dataProvider->getData(new Owner($userEntity), new \DateTime('2024-01-01'), new \DateTime('2024-03-31'));

        $expected = self::getReservationsArray()['Bus'];
        $actual = $this->toArray($result[0], self::PROPERTIES_LIST['Trip']);
        $this->assertEquals($expected, $actual);
    }

    public function testGetFlights()
    {
        $tripSegment = new TripSegment(
            'AM*', 'AMS', new \DateTime('2024-02-11 12:25:00'),
            'GV*', 'GVA', new \DateTime('2024-02-11 13:50:00'),
            null,
            ['AirlineName' => 'KLM Royal Dutch Airlines']
        );
        $tripSegment->setAirline(new Airline('KL', 'KLM Royal Dutch Airlines', ['FSCode' => 'KL*']))
            ->setDepAirCode(new AirCode('AM*', 'AMS', 'Amsterdam', 52.3090, 4.7633, [
                'CountryCode' => 'NL',
                'CountryName' => 'Netherlands',
                'TimeZoneLocation' => 'Europe/Amsterdam',
            ]))
            ->setArrAirCode(new AirCode('GV*', 'GVA', 'Geneva', 46.2296, 6.1057, [
                'CountryCode' => 'CH',
                'CountryName' => 'Switzerland',
                'TimeZoneLocation' => 'Europe/Zurich',
            ]));
        $tripSegment->setDepGeoTag(
            new GeoTag(
                'Amsterdam Airport Schiphol, Amsterdam, NL-' . $this->randomString,
                [
                    'Lat' => 52.3090,
                    'Lng' => 4.7633,
                    'TimeZoneLocation' => 'Europe/Amsterdam',
                    'City' => 'Amsterdam',
                    'State' => 'Noord-Holland',
                    'Country' => 'Netherlands',
                    'StateCode' => 'NH',
                    'CountryCode' => 'NL',
                ]
            )
        );
        $tripSegment->setArrGeoTag(
            new GeoTag(
                'Geneve Airport, Geneva, CH-' . $this->randomString,
                [
                    'Lat' => 46.2296,
                    'Lng' => 6.1057,
                    'TimeZoneLocation' => 'Europe/Zurich',
                    'City' => 'Geneva',
                    'State' => 'Genève',
                    'Country' => 'Switzerland',
                    'StateCode' => 'GE',
                    'CountryCode' => 'CH',
                ]
            )
        );
        $this->dbBuilder->makeTrip(
            new Trip(
                'SG5000-' . $this->randomString,
                [$tripSegment],
                $this->user,
                ['Category' => TripEntity::CATEGORY_AIR]
            )
        );

        $dataProvider = new FlightProvider($this->em);
        $userEntity = $this->em->getRepository(Usr::class)->find($this->user->getId());
        $result = $dataProvider->getData(new Owner($userEntity), new \DateTime('2024-01-01'), new \DateTime('2024-03-31'));

        $expected = self::getReservationsArray()['Flight'];
        $actual = $this->toArray($result[0], self::PROPERTIES_LIST['Trip']);
        $this->assertEquals($expected, $actual);
    }

    public function testGetTrains()
    {
        $tripSegment = new TripSegment(
            null, 'Amsterdam Centraal', new \DateTime('2024-01-13 16:45:00'),
            null, 'London St Pancras Int\'l', new \DateTime('2024-01-13 19:47:00')
        );
        $tripSegment->setDepGeoTag(
            new GeoTag(
                'Amsterdam Centraal-' . $this->randomString,
                [
                    'Lat' => 52.3791,
                    'Lng' => 4.9002,
                    'TimeZoneLocation' => 'Europe/Amsterdam',
                    'City' => 'Amsterdam',
                    'State' => 'Noord-Holland',
                    'Country' => 'Netherlands',
                    'StateCode' => 'NH',
                    'CountryCode' => 'NL',
                ]
            )
        );
        $tripSegment->setArrGeoTag(
            new GeoTag(
                'London St Pancras Int\'l-' . $this->randomString,
                [
                    'Lat' => 51.5311,
                    'Lng' => -0.1258,
                    'TimeZoneLocation' => 'Europe/London',
                    'City' => 'Greater London',
                    'State' => 'England',
                    'Country' => 'United Kingdom',
                    'StateCode' => null,
                    'CountryCode' => 'GB',
                ]
            )
        );
        $this->dbBuilder->makeTrip(
            new Trip(
                'NVT000-' . $this->randomString,
                [$tripSegment],
                $this->user,
                ['Category' => TripEntity::CATEGORY_TRAIN]
            )
        );

        $dataProvider = new TripProvider($this->em);
        $userEntity = $this->em->getRepository(Usr::class)->find($this->user->getId());
        $result = $dataProvider->getData(new Owner($userEntity), new \DateTime('2024-01-01'), new \DateTime('2024-03-31'));

        $expected = self::getReservationsArray()['Train'];
        $actual = $this->toArray($result[0], self::PROPERTIES_LIST['Trip']);
        $this->assertEquals($expected, $actual);
    }

    public function testGetCruises()
    {
        $tripSegments[] = (new TripSegment(
            null, 'Miami, FL', new \DateTime('2024-01-31 15:30:00'),
            null, 'Cozumel, Mexico', new \DateTime('2024-02-02 08:00:00')
        ))->setDepGeoTag(
            new GeoTag(
                'Miami, FL-' . $this->randomString,
                [
                    'Lat' => 25.7616,
                    'Lng' => -80.1917,
                    'TimeZoneLocation' => 'America/New_York',
                    'City' => 'Miami',
                    'State' => 'Florida',
                    'Country' => 'United States',
                    'StateCode' => 'FL',
                    'CountryCode' => 'US',
                ]
            )
        )->setArrGeoTag(
            new GeoTag(
                'COZUMEL, MEXICO-' . $this->randomString,
                [
                    'Lat' => 20.4229,
                    'Lng' => -86.9223,
                    'TimeZoneLocation' => 'America/Cancun',
                    'City' => null,
                    'State' => 'Quintana Roo',
                    'Country' => 'Mexico',
                    'StateCode' => 'Q.R.',
                    'CountryCode' => 'MX',
                ]
            )
        );
        $tripSegments[] = (new TripSegment(
            null, 'Cozumel, Mexico', new \DateTime('2024-02-02 16:00:00'),
            null, 'Belize', new \DateTime('2024-02-03 08:00:00')
        ))->setDepGeoTag(
            new GeoTag(
                'COZUMEL, MEXICO-' . $this->randomString,
                [
                    'Lat' => 20.4229,
                    'Lng' => -86.9223,
                    'TimeZoneLocation' => 'America/Cancun',
                    'City' => null,
                    'State' => 'Quintana Roo',
                    'Country' => 'Mexico',
                    'StateCode' => 'Q.R.',
                    'CountryCode' => 'MX',
                ]
            )
        )->setArrGeoTag(
            new GeoTag(
                'Belize-' . $this->randomString,
                [
                    'Lat' => 17.1898,
                    'Lng' => -88.4976,
                    'TimeZoneLocation' => 'America/Belize',
                    'City' => null,
                    'State' => null,
                    'Country' => 'Belize',
                    'StateCode' => null,
                    'CountryCode' => 'BZ',
                ]
            )
        );
        $tripSegments[] = (new TripSegment(
            null, 'Belize', new \DateTime('2024-02-03 17:00:00'),
            null, 'Costa Maya, Mexico', new \DateTime('2024-02-04 07:00:00')
        ))->setDepGeoTag(
            new GeoTag(
                'Belize-' . $this->randomString,
                [
                    'Lat' => 17.1898,
                    'Lng' => -88.4976,
                    'TimeZoneLocation' => 'America/Belize',
                    'City' => null,
                    'State' => null,
                    'Country' => 'Belize',
                    'StateCode' => null,
                    'CountryCode' => 'BZ',
                ]
            )
        )->setArrGeoTag(
            new GeoTag(
                'Costa Maya, Mexico-' . $this->randomString,
                [
                    'Lat' => 18.7143,
                    'Lng' => -87.7092,
                    'TimeZoneLocation' => 'America/Cancun',
                    'City' => 'Othón P. Blanco',
                    'State' => 'Quintana Roo',
                    'Country' => 'Mexico',
                    'StateCode' => 'Q.R.',
                    'CountryCode' => 'MX',
                ]
            )
        );
        $tripSegments[] = (new TripSegment(
            null, 'Costa Maya, Mexico', new \DateTime('2024-02-04 16:00:00'),
            null, 'Miami, FL', new \DateTime('2024-02-06 08:00:00')
        ))->setDepGeoTag(
            new GeoTag(
                'Costa Maya, Mexico-' . $this->randomString,
                [
                    'Lat' => 18.7143,
                    'Lng' => -87.7092,
                    'TimeZoneLocation' => 'America/Cancun',
                    'City' => 'Othón P. Blanco',
                    'State' => 'Quintana Roo',
                    'Country' => 'Mexico',
                    'StateCode' => 'Q.R.',
                    'CountryCode' => 'MX',
                ]
            )
        )->setArrGeoTag(
            new GeoTag(
                'Miami, FL-' . $this->randomString,
                [
                    'Lat' => 25.7616,
                    'Lng' => -80.1917,
                    'TimeZoneLocation' => 'America/New_York',
                    'City' => 'Miami',
                    'State' => 'Florida',
                    'Country' => 'United States',
                    'StateCode' => 'FL',
                    'CountryCode' => 'US',
                ]
            )
        );
        $this->dbBuilder->makeTrip(
            (new Trip(
                'R9K000-' . $this->randomString,
                $tripSegments,
                $this->user,
                ['Category' => TripEntity::CATEGORY_CRUISE]
            ))->setProvider(
                new Provider(
                    'Carnival Cruise Line',
                    [
                        'Code' => $this->randomString,
                        'Kind' => 10, // Kind cruises
                        'ShortName' => 'Carnival',
                    ]
                )
            )
        );

        $dataProvider = new TripProvider($this->em);
        $userEntity = $this->em->getRepository(Usr::class)->find($this->user->getId());
        $result = $dataProvider->getData(new Owner($userEntity), new \DateTime('2024-01-01'), new \DateTime('2024-03-31'));

        $expected = self::getReservationsArray()['Cruise'];
        $actual = [];

        foreach ($result as $trip) {
            $actual[] = $this->toArray($trip, self::PROPERTIES_LIST['Trip']);
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetFerries()
    {
        $tripSegment = new TripSegment(
            null, 'Villa San Giovanni', new \DateTime('2024-03-16 10:15:00'),
            null, 'Messina: Caronte & Tourist', new \DateTime('2024-03-16 10:35:00')
        );
        $tripSegment->setDepGeoTag(
            new GeoTag(
                'Villa San Giovanni-' . $this->randomString,
                [
                    'Lat' => 38.2244,
                    'Lng' => 15.6366,
                    'TimeZoneLocation' => 'Europe/Rome',
                    'City' => 'Villa San Giovanni',
                    'State' => 'Calabria',
                    'Country' => 'Italy',
                    'StateCode' => null,
                    'CountryCode' => 'IT',
                ]
            )
        );
        $tripSegment->setArrGeoTag(
            new GeoTag(
                'Messina Caronte Tourist-' . $this->randomString,
                [
                    'Lat' => 38.2003,
                    'Lng' => 15.5533,
                    'TimeZoneLocation' => 'Europe/Rome',
                    'City' => 'Messina',
                    'State' => 'Sicily',
                    'Country' => 'Italy',
                    'StateCode' => null,
                    'CountryCode' => 'IT',
                ]
            )
        );
        $this->dbBuilder->makeTrip(
            new Trip(
                'GR23000000000-' . $this->randomString,
                [$tripSegment],
                $this->user,
                ['Category' => TripEntity::CATEGORY_FERRY]
            )
        );

        $dataProvider = new TripProvider($this->em);
        $userEntity = $this->em->getRepository(Usr::class)->find($this->user->getId());
        $result = $dataProvider->getData(new Owner($userEntity), new \DateTime('2024-01-01'), new \DateTime('2024-03-31'));

        $expected = self::getReservationsArray()['Ferry'];
        $actual = $this->toArray($result[0], self::PROPERTIES_LIST['Trip']);
        $this->assertEquals($expected, $actual);
    }

    public function testGetTransfers()
    {
        $tripSegment = new TripSegment(
            null, 'Pousada Alfama', new \DateTime('2024-02-09 10:30:00'),
            'LIS', 'Humberto Delgado Airport', new \DateTime('2024-02-09 10:53:00')
        );
        $tripSegment->setDepGeoTag(
            new GeoTag(
                'R. São Tomé 76, 1100-561 Lisboa, Portugal-' . $this->randomString,
                [
                    'Lat' => 38.7127,
                    'Lng' => -9.1307,
                    'TimeZoneLocation' => 'Europe/Lisbon',
                    'City' => 'Lisbon',
                    'State' => 'Lisboa',
                    'Country' => 'Portugal',
                    'StateCode' => null,
                    'CountryCode' => 'PT',
                ]
            )
        );
        $tripSegment->setArrGeoTag(
            new GeoTag(
                'LIS-' . $this->randomString,
                [
                    'Lat' => 38.7700,
                    'Lng' => -9.1281,
                    'TimeZoneLocation' => 'Europe/Lisbon',
                    'City' => 'Lisbon',
                    'State' => 'Lisboa',
                    'Country' => 'Portugal',
                    'StateCode' => null,
                    'CountryCode' => 'PT',
                ]
            )
        );
        $this->dbBuilder->makeTrip(
            (new Trip(
                '65300000-' . $this->randomString,
                [$tripSegment],
                $this->user,
                ['Category' => TripEntity::CATEGORY_TRANSFER]
            ))->setProvider(
                new Provider(
                    'Booking.com Hotel Reservations Worldwide',
                    [
                        'Code' => $this->randomString,
                        'Kind' => 2, // Kind hotels
                        'ShortName' => 'Booking.com',
                    ]
                )
            )
        );

        $dataProvider = new TripProvider($this->em);
        $userEntity = $this->em->getRepository(Usr::class)->find($this->user->getId());
        $result = $dataProvider->getData(new Owner($userEntity), new \DateTime('2024-01-01'), new \DateTime('2024-03-31'));

        $expected = self::getReservationsArray()['Transfer'];
        $actual = $this->toArray($result[0], self::PROPERTIES_LIST['Trip']);
        $this->assertEquals($expected, $actual);
    }

    public function testGetHotels()
    {
        $this->dbBuilder->makeReservation(
            (new Reservation(
                '95010000-' . $this->randomString,
                'Hampton Inn & Suites Flagstaff East',
                new \DateTime('2024-03-12 15:00:00'),
                new \DateTime('2024-03-14 11:00:00'),
                $this->user
            ))->setGeoTag(
                new GeoTag(
                    '990 N Country Club Drive Flagstaff, Arizona, 86004 US-' . $this->randomString,
                    [
                        'Lat' => 35.2153,
                        'Lng' => -111.5817,
                        'TimeZoneLocation' => 'America/Phoenix',
                        'City' => 'Flagstaff',
                        'State' => 'Arizona',
                        'Country' => 'United States',
                        'StateCode' => 'AZ',
                        'CountryCode' => 'US',
                    ]
                )
            )
        );

        $dataProvider = new HotelProvider($this->em, $this->container->get(Formatter::class));
        $userEntity = $this->em->getRepository(Usr::class)->find($this->user->getId());
        $result = $dataProvider->getData(new Owner($userEntity), new \DateTime('2024-01-01'), new \DateTime('2024-03-31'));

        $expected = self::getReservationsArray()['Hotel'];
        $actual = $this->toArray($result[0], self::PROPERTIES_LIST['Reservation']);
        $this->assertEquals($expected, $actual);
    }

    public function testGetParkingLots()
    {
        $this->dbBuilder->makeParking(
            (new Parking(
                '0SGAA-' . $this->randomString,
                null,
                new \DateTime('2024-01-14 11:00:00'),
                new \DateTime('2024-01-18 17:00:00'),
                $this->user,
                ['ParkingCompanyName' => 'Park \'N Fly']
            ))->setGeoTag(
                new GeoTag(
                    '82 98th Avenue Oakland CA, 94603-' . $this->randomString,
                    [
                        'Lat' => 37.7277,
                        'Lng' => -122.2001,
                        'TimeZoneLocation' => 'America/Los_Angeles',
                        'City' => 'Oakland',
                        'State' => 'California',
                        'Country' => 'United States',
                        'StateCode' => 'CA',
                        'CountryCode' => 'US',
                    ]
                )
            )
        );

        $dataProvider = new ParkingProvider($this->em, $this->container->get(Formatter::class));
        $userEntity = $this->em->getRepository(Usr::class)->find($this->user->getId());
        $result = $dataProvider->getData(new Owner($userEntity), new \DateTime('2024-01-01'), new \DateTime('2024-03-31'));

        $expected = self::getReservationsArray()['Parking'];
        $actual = $this->toArray($result[0], self::PROPERTIES_LIST['Reservation']);
        $this->assertEquals($expected, $actual);
    }

    public function testGetRentals()
    {
        $this->dbBuilder->makeRental(
            (new Rental(
                '1165000000-' . $this->randomString,
                'Aeroport De Marseille Provence 13700 Marignane',
                new \DateTime('2024-02-06 11:00:00'),
                'Nice Cote D Azur Airport, Car Rental Center T2 Av D.daurat 06281 Nice',
                new \DateTime('2024-02-13 11:00:00'),
                $this->user,
                ['RentalCompanyName' => 'Europcar']
            ))->setPickupGeoTag(
                new GeoTag(
                    'Aeroport De Marseille Provence 13700 Marignane-' . $this->randomString,
                    [
                        'Lat' => 43.4384,
                        'Lng' => 5.2118,
                        'TimeZoneLocation' => 'Europe/Paris',
                        'City' => 'Marseille',
                        'State' => 'Provence-Alpes-Côte d\'Azur',
                        'Country' => 'France',
                        'StateCode' => null,
                        'CountryCode' => 'FR',
                    ]
                )
            )->setDropoffGeoTag(
                new GeoTag(
                    'Nice Cote D Azur Airport, Car Rental Center T2 Av D.daurat 06281 Nice-' . $this->randomString,
                    [
                        'Lat' => 43.6605,
                        'Lng' => 7.2007,
                        'TimeZoneLocation' => 'Europe/Paris',
                        'City' => 'Nice',
                        'State' => 'Provence-Alpes-Côte d\'Azur',
                        'Country' => 'France',
                        'StateCode' => null,
                        'CountryCode' => 'FR',
                    ]
                )
            )
        );

        $dataProvider = new RentalProvider($this->em, $this->container->get(Formatter::class));
        $userEntity = $this->em->getRepository(Usr::class)->find($this->user->getId());
        $result = $dataProvider->getData(new Owner($userEntity), new \DateTime('2024-01-01'), new \DateTime('2024-03-31'));

        $expected = self::getReservationsArray()['Rental'];
        $actual = $this->toArray($result[0], self::PROPERTIES_LIST['Trip']);
        $this->assertEquals($expected, $actual);
    }

    public function testGetRestaurants()
    {
        $this->dbBuilder->makeRestaurant(
            (new Restaurant(
                '45000-' . $this->randomString,
                '1837 Bar & Brasserie at Guinness Storehouse',
                new \DateTime('2024-01-19 14:00:00'),
                null,
                \AwardWallet\MainBundle\Entity\Restaurant::EVENT_RESTAURANT,
                $this->user
            ))->setGeoTag(
                new GeoTag(
                    'Saint James\'s Gate, Dublin, Co Dublin D08 VF8H-' . $this->randomString,
                    [
                        'Lat' => 53.3433,
                        'Lng' => -6.2895,
                        'TimeZoneLocation' => 'Europe/Dublin',
                        'City' => 'Dublin',
                        'State' => 'County Dublin',
                        'Country' => 'Ireland',
                        'StateCode' => 'D',
                        'CountryCode' => 'IE',
                    ]
                )
            )
        );

        $dataProvider = new RestaurantProvider($this->em, $this->container->get(Formatter::class));
        $userEntity = $this->em->getRepository(Usr::class)->find($this->user->getId());
        $result = $dataProvider->getData(new Owner($userEntity), new \DateTime('2024-01-01'), new \DateTime('2024-03-31'));

        $expected = self::getReservationsArray()['Restaurant'];
        $actual = $this->toArray($result[0], self::PROPERTIES_LIST['Reservation']);
        $this->assertEquals($expected, $actual);
    }

    private static function getReservationsArray(): array
    {
        return [
            'Bus' => [
                'title' => '',
                'airlineCode' => null,
                'startDate' => '2024-03-04 15:00:00',
                'endDate' => '2024-03-04 19:40:00',
                'departure' => [
                    'city' => 'Berlin',
                    'country' => 'Germany',
                    'stateCode' => 'BE',
                    'countryCode' => 'DE',
                    'latitude' => 52.5071,
                    'longitude' => 13.2768,
                    'timeZone' => 'Europe/Berlin',
                    'category' => 'bus',
                    'airCode' => null,
                    'locationName' => 'Berlin central bus station',
                ],
                'arrival' => [
                    'city' => 'Prague',
                    'country' => 'Czechia',
                    'stateCode' => null,
                    'countryCode' => 'CZ',
                    'latitude' => 50.0896,
                    'longitude' => 14.4391,
                    'timeZone' => 'Europe/Prague',
                    'category' => 'bus',
                    'airCode' => null,
                    'locationName' => 'Prague (Central Bus Station Florenc)',
                ],
                'prefix' => TripEntity::SEGMENT_MAP,
            ],
            'Flight' => [
                'title' => 'KLM Royal Dutch Airlines',
                'airlineCode' => 'KL',
                'startDate' => '2024-02-11 12:25:00',
                'endDate' => '2024-02-11 13:50:00',
                'departure' => [
                    'city' => 'Amsterdam',
                    'country' => null,
                    'stateCode' => null,
                    'countryCode' => null,
                    'latitude' => 52.3090,
                    'longitude' => 4.7633,
                    'timeZone' => 'Europe/Amsterdam',
                    'category' => 'air',
                    'airCode' => 'AM*',
                    'locationName' => null,
                ],
                'arrival' => [
                    'city' => null,
                    'country' => 'Switzerland',
                    'stateCode' => null,
                    'countryCode' => 'CH',
                    'latitude' => 46.2296,
                    'longitude' => 6.1057,
                    'timeZone' => 'Europe/Zurich',
                    'category' => 'air',
                    'airCode' => 'GV*',
                    'locationName' => null,
                ],
                'prefix' => TripEntity::SEGMENT_MAP,
            ],
            'Train' => [
                'title' => '',
                'airlineCode' => null,
                'startDate' => '2024-01-13 16:45:00',
                'endDate' => '2024-01-13 19:47:00',
                'departure' => [
                    'city' => 'Amsterdam',
                    'country' => 'Netherlands',
                    'stateCode' => 'NH',
                    'countryCode' => 'NL',
                    'latitude' => 52.3791,
                    'longitude' => 4.9002,
                    'timeZone' => 'Europe/Amsterdam',
                    'category' => 'train',
                    'airCode' => null,
                    'locationName' => 'Amsterdam Centraal',
                ],
                'arrival' => [
                    'city' => 'Greater London',
                    'country' => 'United Kingdom',
                    'stateCode' => null,
                    'countryCode' => 'GB',
                    'latitude' => 51.5311,
                    'longitude' => -0.1258,
                    'timeZone' => 'Europe/London',
                    'category' => 'train',
                    'airCode' => null,
                    'locationName' => 'London St Pancras Int\'l',
                ],
                'prefix' => TripEntity::SEGMENT_MAP,
            ],
            'Cruise' => [
                [
                    'title' => 'Carnival',
                    'airlineCode' => null,
                    'startDate' => '2024-01-31 15:30:00',
                    'endDate' => '2024-02-02 08:00:00',
                    'departure' => [
                        'city' => 'Miami',
                        'country' => 'United States',
                        'stateCode' => 'FL',
                        'countryCode' => 'US',
                        'latitude' => 25.7616,
                        'longitude' => -80.1917,
                        'timeZone' => 'America/New_York',
                        'category' => 'cruise',
                        'airCode' => null,
                        'locationName' => 'Miami, FL',
                    ],
                    'arrival' => [
                        'city' => null,
                        'country' => 'Mexico',
                        'stateCode' => 'Q.R.',
                        'countryCode' => 'MX',
                        'latitude' => 20.4229,
                        'longitude' => -86.9223,
                        'timeZone' => 'America/Cancun',
                        'category' => 'cruise',
                        'airCode' => null,
                        'locationName' => 'Cozumel, Mexico',
                    ],
                    'prefix' => TripEntity::SEGMENT_MAP,
                ],
                [
                    'title' => 'Carnival',
                    'airlineCode' => null,
                    'startDate' => '2024-02-02 16:00:00',
                    'endDate' => '2024-02-03 08:00:00',
                    'departure' => [
                        'city' => null,
                        'country' => 'Mexico',
                        'stateCode' => 'Q.R.',
                        'countryCode' => 'MX',
                        'latitude' => 20.4229,
                        'longitude' => -86.9223,
                        'timeZone' => 'America/Cancun',
                        'category' => 'cruise',
                        'airCode' => null,
                        'locationName' => 'Cozumel, Mexico',
                    ],
                    'arrival' => [
                        'city' => null,
                        'country' => 'Belize',
                        'stateCode' => null,
                        'countryCode' => 'BZ',
                        'latitude' => 17.1898,
                        'longitude' => -88.4976,
                        'timeZone' => 'America/Belize',
                        'category' => 'cruise',
                        'airCode' => null,
                        'locationName' => 'Belize',
                    ],
                    'prefix' => TripEntity::SEGMENT_MAP,
                ],
                [
                    'title' => 'Carnival',
                    'airlineCode' => null,
                    'startDate' => '2024-02-03 17:00:00',
                    'endDate' => '2024-02-04 07:00:00',
                    'departure' => [
                        'city' => null,
                        'country' => 'Belize',
                        'stateCode' => null,
                        'countryCode' => 'BZ',
                        'latitude' => 17.1898,
                        'longitude' => -88.4976,
                        'timeZone' => 'America/Belize',
                        'category' => 'cruise',
                        'airCode' => null,
                        'locationName' => 'Belize',
                    ],
                    'arrival' => [
                        'city' => 'Othón P. Blanco',
                        'country' => 'Mexico',
                        'stateCode' => 'Q.R.',
                        'countryCode' => 'MX',
                        'latitude' => 18.7143,
                        'longitude' => -87.7092,
                        'timeZone' => 'America/Cancun',
                        'category' => 'cruise',
                        'airCode' => null,
                        'locationName' => 'Costa Maya, Mexico',
                    ],
                    'prefix' => TripEntity::SEGMENT_MAP,
                ],
                [
                    'title' => 'Carnival',
                    'airlineCode' => null,
                    'startDate' => '2024-02-04 16:00:00',
                    'endDate' => '2024-02-06 08:00:00',
                    'departure' => [
                        'city' => 'Othón P. Blanco',
                        'country' => 'Mexico',
                        'stateCode' => 'Q.R.',
                        'countryCode' => 'MX',
                        'latitude' => 18.7143,
                        'longitude' => -87.7092,
                        'timeZone' => 'America/Cancun',
                        'category' => 'cruise',
                        'airCode' => null,
                        'locationName' => 'Costa Maya, Mexico',
                    ],
                    'arrival' => [
                        'city' => 'Miami',
                        'country' => 'United States',
                        'stateCode' => 'FL',
                        'countryCode' => 'US',
                        'latitude' => 25.7616,
                        'longitude' => -80.1917,
                        'timeZone' => 'America/New_York',
                        'category' => 'cruise',
                        'airCode' => null,
                        'locationName' => 'Miami, FL',
                    ],
                    'prefix' => TripEntity::SEGMENT_MAP,
                ],
            ],
            'Ferry' => [
                'title' => '',
                'airlineCode' => null,
                'startDate' => '2024-03-16 10:15:00',
                'endDate' => '2024-03-16 10:35:00',
                'departure' => [
                    'city' => 'Villa San Giovanni',
                    'country' => 'Italy',
                    'stateCode' => null,
                    'countryCode' => 'IT',
                    'latitude' => 38.2244,
                    'longitude' => 15.6366,
                    'timeZone' => 'Europe/Rome',
                    'category' => 'ferry',
                    'airCode' => null,
                    'locationName' => 'Villa San Giovanni',
                ],
                'arrival' => [
                    'city' => 'Messina',
                    'country' => 'Italy',
                    'stateCode' => null,
                    'countryCode' => 'IT',
                    'latitude' => 38.2003,
                    'longitude' => 15.5533,
                    'timeZone' => 'Europe/Rome',
                    'category' => 'ferry',
                    'airCode' => null,
                    'locationName' => 'Messina: Caronte & Tourist',
                ],
                'prefix' => TripEntity::SEGMENT_MAP,
            ],
            'Transfer' => [
                'title' => 'Booking.com',
                'airlineCode' => null,
                'startDate' => '2024-02-09 10:30:00',
                'endDate' => '2024-02-09 10:53:00',
                'departure' => [
                    'city' => 'Lisbon',
                    'country' => 'Portugal',
                    'stateCode' => null,
                    'countryCode' => 'PT',
                    'latitude' => 38.7127,
                    'longitude' => -9.1307,
                    'timeZone' => 'Europe/Lisbon',
                    'category' => 'transfer',
                    'airCode' => null,
                    'locationName' => 'Pousada Alfama',
                ],
                'arrival' => [
                    'city' => 'Lisbon',
                    'country' => 'Portugal',
                    'stateCode' => null,
                    'countryCode' => 'PT',
                    'latitude' => 38.7700,
                    'longitude' => -9.1281,
                    'timeZone' => 'Europe/Lisbon',
                    'category' => 'transfer',
                    'airCode' => 'LIS',
                    'locationName' => 'Humberto Delgado Airport',
                ],
                'prefix' => TripEntity::SEGMENT_MAP,
            ],
            'Hotel' => [
                'title' => 'Hampton Inn & Suites Flagstaff East',
                'startDate' => '2024-03-12 15:00:00',
                'endDate' => '2024-03-14 11:00:00',
                'marker' => [
                    'city' => 'Flagstaff',
                    'country' => 'United States',
                    'stateCode' => 'AZ',
                    'countryCode' => 'US',
                    'latitude' => 35.2153,
                    'longitude' => -111.5817,
                    'timeZone' => 'America/Phoenix',
                    'category' => 'hotel',
                    'airCode' => null,
                    'locationName' => null,
                ],
                'prefix' => ReservationEntity::SEGMENT_MAP_START,
            ],
            'Parking' => [
                'title' => 'Park \'N Fly',
                'startDate' => '2024-01-14 11:00:00',
                'endDate' => '2024-01-18 17:00:00',
                'marker' => [
                    'city' => 'Oakland',
                    'country' => 'United States',
                    'stateCode' => 'CA',
                    'countryCode' => 'US',
                    'latitude' => 37.7277,
                    'longitude' => -122.2001,
                    'timeZone' => 'America/Los_Angeles',
                    'category' => 'parking',
                    'airCode' => null,
                    'locationName' => null,
                ],
                'prefix' => ParkingEntity::SEGMENT_MAP_START,
            ],
            'Rental' => [
                'title' => 'Europcar',
                'airlineCode' => null,
                'startDate' => '2024-02-06 11:00:00',
                'endDate' => '2024-02-13 11:00:00',
                'departure' => [
                    'city' => 'Marseille',
                    'country' => 'France',
                    'stateCode' => null,
                    'countryCode' => 'FR',
                    'latitude' => 43.4384,
                    'longitude' => 5.2118,
                    'timeZone' => 'Europe/Paris',
                    'category' => 'rental',
                    'airCode' => null,
                    'locationName' => 'Aeroport De Marseille Provence 13700 Marignane',
                ],
                'arrival' => [
                    'city' => 'Nice',
                    'country' => 'France',
                    'stateCode' => null,
                    'countryCode' => 'FR',
                    'latitude' => 43.6605,
                    'longitude' => 7.2007,
                    'timeZone' => 'Europe/Paris',
                    'category' => 'rental',
                    'airCode' => null,
                    'locationName' => 'Nice Cote D Azur Airport, Car Rental Center T2 Av D.daurat 06281 Nice',
                ],
                'prefix' => RentalEntity::SEGMENT_MAP_START,
            ],
            'Restaurant' => [
                'title' => '1837 Bar & Brasserie at Guinness Storehouse',
                'startDate' => '2024-01-19 14:00:00',
                'endDate' => null,
                'marker' => [
                    'city' => 'Dublin',
                    'country' => 'Ireland',
                    'stateCode' => 'D',
                    'countryCode' => 'IE',
                    'latitude' => 53.3433,
                    'longitude' => -6.2895,
                    'timeZone' => 'Europe/Dublin',
                    'category' => 'restaurant',
                    'airCode' => null,
                    'locationName' => null,
                ],
                'prefix' => RestaurantEntity::SEGMENT_MAP,
            ],
        ];
    }

    /**
     * Converts recursively a reservation object into an associative array for later validation.
     *
     * @param ReservationModel|TripModel|Marker $reservation an object that is converted into an array
     * @param array $properties the set of properties from which the data is taken
     */
    private function toArray($reservation, $properties): array
    {
        $result = [];

        foreach ($properties as $property) {
            $method = 'get' . ucfirst($property);

            if (!method_exists($reservation, $method)) {
                continue;
            }

            if (in_array($property, ['marker', 'departure', 'arrival'])) {
                $result[$property] = $this->toArray($reservation->$method(), self::PROPERTIES_LIST['Marker']);
            } else {
                $value = $reservation->$method();

                if (in_array($property, ['startDate', 'endDate']) && $value !== null) {
                    /** @var \DateTime $value */
                    $result[$property] = $value->format('Y-m-d H:i:s');
                } else {
                    $result[$property] = $value;
                }
            }
        }

        return $result;
    }
}
