<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\TravelSummary;

use AwardWallet\MainBundle\Entity\Restaurant as RestaurantEntity;
use AwardWallet\MainBundle\Entity\Trip as TripEntity;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\TravelSummary\Formatter\DesktopFormatter;
use AwardWallet\MainBundle\Service\TravelSummary\Formatter\MobileFormatter;
use AwardWallet\MainBundle\Service\TravelSummary\PeriodDatesHelper;
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
use Clock\ClockNative;
use Clock\ClockTest;
use Codeception\Module\JsonNormalizer;
use Duration\Duration;

/**
 * @group frontend-unit
 */
class FormatterTest extends BaseContainerTest
{
    private ?User $user;
    private string $randomString = '';
    private ?JsonNormalizer $jsonNormalizer;

    public function _before()
    {
        parent::_before();
        $this->user = new User();
        $this->randomString = StringUtils::getPseudoRandomString(8);
        $this->jsonNormalizer = $this->getModule('JsonNormalizer');

        $this->mockService(ClockNative::class, new ClockTest(Duration::fromString('2025-01-01 12:00:00')));
    }

    public function _after()
    {
        $this->user = null;
        $this->randomString = '';
        $this->jsonNormalizer = null;
        parent::_after();
    }

    public function testDesktopFormat()
    {
        foreach (['flights', 'buses', 'trains', 'cruises', 'ferries', 'hotels', 'parkingLots', 'rentals', 'restaurants', 'events'] as $category) {
            $method = 'create' . ucfirst($category);
            $this->{$method}();
        }
        $formatter = $this->container->get(DesktopFormatter::class);

        $userEntity = $this->em->getRepository(Usr::class)->find($this->user->getId());
        $result = $formatter->format($userEntity, null, PeriodDatesHelper::LAST_YEAR);

        $this->jsonNormalizer->expectJsonTemplate(__DIR__ . '/Fixtures/desktop.json', \json_encode($result));
    }

    public function testMobileFormat()
    {
        $this->createFlights();
        $formatter = $this->container->get(MobileFormatter::class);

        $userEntity = $this->em->getRepository(Usr::class)->find($this->user->getId());
        $result = $formatter->format($userEntity, null, PeriodDatesHelper::LAST_YEAR);

        $this->jsonNormalizer->expectJsonTemplate(__DIR__ . '/Fixtures/mobile.json', \json_encode($result));
    }

    private function createFlights()
    {
        $airline = new Airline('KL', 'KLM Royal Dutch Airlines', ['FSCode' => 'KL*']);
        $airCodeAmsterdam = new AirCode('AM*', 'AMS', 'Amsterdam', 52.3090, 4.7633, [
            'CountryCode' => 'NL',
            'CountryName' => 'Netherlands',
            'AirName' => 'Amsterdam Airport Schiphol',
            'TimeZoneLocation' => 'Europe/Amsterdam',
        ]);
        $airCodeGeneva = new AirCode('GV*', 'GVA', 'Geneva', 46.2296, 6.1057, [
            'CountryCode' => 'CH',
            'CountryName' => 'Switzerland',
            'AirName' => 'Geneve Airport',
            'TimeZoneLocation' => 'Europe/Zurich',
        ]);
        $geoTagAmsterdam = new GeoTag('Amsterdam Airport Schiphol, Amsterdam, NL-' . $this->randomString, [
            'Lat' => 52.3090,
            'Lng' => 4.7633,
            'TimeZoneLocation' => 'Europe/Amsterdam',
            'City' => 'Amsterdam',
            'State' => 'Noord-Holland',
            'Country' => 'Netherlands',
            'StateCode' => 'NH',
            'CountryCode' => 'NL',
        ]);
        $geoTagGeneva = new GeoTag('Geneve Airport, Geneva, CH-' . $this->randomString, [
            'Lat' => 46.2296,
            'Lng' => 6.1057,
            'TimeZoneLocation' => 'Europe/Zurich',
            'City' => 'Geneva',
            'State' => 'Genève',
            'Country' => 'Switzerland',
            'StateCode' => 'GE',
            'CountryCode' => 'CH',
        ]);

        $tripSegments[] = (new TripSegment(
            'AM*', 'AMS', new \DateTime('2024-02-11 12:25:00'),
            'GV*', 'GVA', new \DateTime('2024-02-11 13:50:00'),
            null,
            ['AirlineName' => 'KLM Royal Dutch Airlines']
        ))->setAirline($airline)
            ->setDepAirCode($airCodeAmsterdam)
            ->setArrAirCode($airCodeGeneva)
            ->setDepGeoTag($geoTagAmsterdam)
            ->setArrGeoTag($geoTagGeneva);

        $tripSegments[] = (new TripSegment(
            'GV*', 'GVA', new \DateTime('2024-02-18 07:10:00'),
            'AM*', 'AMS', new \DateTime('2024-02-18 09:00:00'),
            null,
            ['AirlineName' => 'KLM Royal Dutch Airlines']
        ))->setAirline($airline)
            ->setDepAirCode($airCodeGeneva)
            ->setArrAirCode($airCodeAmsterdam)
            ->setDepGeoTag($geoTagGeneva)
            ->setArrGeoTag($geoTagAmsterdam);

        $this->dbBuilder->makeTrip(
            new Trip(
                'SG5000-' . $this->randomString,
                $tripSegments,
                $this->user,
                ['category' => TripEntity::CATEGORY_AIR]
            )
        );
    }

    private function createBuses()
    {
        $tripSegment = new TripSegment(
            null, 'Sydney Central, 486 Pitt Street, Central Railway Station', new \DateTime('2024-02-26 09:00:00'),
            null, 'Canberra, Jolimont Centre, Bay 6/7, 65 Northbourne Ave', new \DateTime('2024-02-26 12:30:00')
        );
        $tripSegment->setDepGeoTag(
            new GeoTag('SYDNEY CENTRAL, 486 Pitt Street, Central Railway Station-' . $this->randomString, [
                'Lat' => -33.8822,
                'Lng' => 151.2052,
                'TimeZoneLocation' => 'Australia/Sydney',
                'City' => 'Sydney',
                'State' => 'New South Wales',
                'Country' => 'Australia',
                'StateCode' => 'NSW',
                'CountryCode' => 'AU',
            ])
        );
        $tripSegment->setArrGeoTag(
            new GeoTag('CANBERRA, Jolimont Centre, Bay 6 7, 65 Northbourne Ave-' . $this->randomString, [
                'Lat' => -35.2776,
                'Lng' => 149.1288,
                'TimeZoneLocation' => 'Australia/Sydney',
                'City' => 'Canberra',
                'State' => 'Australian Capital Territory',
                'Country' => 'Australia',
                'StateCode' => 'ACT',
                'CountryCode' => 'AU',
            ])
        );
        $this->dbBuilder->makeTrip(
            new Trip(
                '8490000-' . $this->randomString,
                [$tripSegment],
                $this->user,
                ['Category' => TripEntity::CATEGORY_BUS]
            )
        );
    }

    private function createTrains()
    {
        $tripSegment = new TripSegment(
            null, 'Paris Gare de Lyon', new \DateTime('2024-01-27 15:18:00'),
            null, 'Milano Centrale', new \DateTime('2024-01-27 22:07:00')
        );
        $tripSegment->setDepGeoTag(
            new GeoTag('Paris Gare de Lyon-' . $this->randomString, [
                'Lat' => 48.8443,
                'Lng' => 2.3743,
                'TimeZoneLocation' => 'Europe/Paris',
                'City' => 'Paris',
                'State' => 'Île-de-France',
                'Country' => 'France',
                'StateCode' => null,
                'CountryCode' => 'FR',
            ])
        );
        $tripSegment->setArrGeoTag(
            new GeoTag('Milano Centrale-' . $this->randomString, [
                'Lat' => 45.4839,
                'Lng' => 9.2060,
                'TimeZoneLocation' => 'Europe/Rome',
                'City' => 'Milan',
                'State' => 'Lombardy',
                'Country' => 'Italy',
                'StateCode' => null,
                'CountryCode' => 'IT',
            ])
        );
        $this->dbBuilder->makeTrip(
            (new Trip(
                'WV6000-' . $this->randomString,
                [$tripSegment],
                $this->user,
                ['Category' => TripEntity::CATEGORY_TRAIN]
            ))->setProvider(
                new Provider(
                    'ItaliaRail - Italy Train Ticket and Rail Pass Experts',
                    [
                        'Code' => $this->randomString . '-t',
                        'Kind' => 4, // Kind trains
                        'ShortName' => 'ItaliaRail',
                    ]
                )
            )
        );
    }

    private function createCruises()
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
                        'Code' => $this->randomString . '-c',
                        'Kind' => 10, // Kind cruises
                        'ShortName' => 'Carnival',
                    ]
                )
            )
        );
    }

    private function createFerries()
    {
        $tripSegment = new TripSegment(
            null, 'Victoria (Swartz Bay)', new \DateTime('2024-05-20 12:00:00'),
            null, 'Vancouver (Tsawwassen)', new \DateTime('2024-05-20 13:35:00')
        );
        $tripSegment->setDepGeoTag(
            new GeoTag('Victoria Swartz Bay-' . $this->randomString, [
                'Lat' => 48.6885,
                'Lng' => -123.4114,
                'TimeZoneLocation' => 'America/Vancouver',
                'City' => 'Sidney',
                'State' => 'British Columbia',
                'Country' => 'Canada',
                'StateCode' => 'BC',
                'CountryCode' => 'CA',
            ])
        );
        $tripSegment->setArrGeoTag(
            new GeoTag('Vancouver Tsawwassen-' . $this->randomString, [
                'Lat' => 49.0068,
                'Lng' => -123.1303,
                'TimeZoneLocation' => 'America/Vancouver',
                'City' => 'Delta',
                'State' => 'British Columbia',
                'Country' => 'Canada',
                'StateCode' => 'BC',
                'CountryCode' => 'CA',
            ])
        );

        $this->dbBuilder->makeTrip(
            new Trip(
                '710100000-' . $this->randomString,
                [$tripSegment],
                $this->user,
                ['Category' => TripEntity::CATEGORY_FERRY]
            )
        );
    }

    private function createHotels()
    {
        $this->dbBuilder->makeReservation(
            (new Reservation(
                '77820000-' . $this->randomString,
                'Geneva Marriott Hotel',
                new \DateTime('2024-03-24 16:00:00'),
                new \DateTime('2024-03-26 11:00:00'),
                $this->user,
                ['Address' => 'Chemin du Ruisseau 1, Cointrin, Geneva, 1216, Switzerland']
            ))->setGeoTag(
                new GeoTag('Chemin du Ruisseau 1, Cointrin, Geneva, 1216, Switzerland-' . $this->randomString, [
                    'Lat' => 46.2211,
                    'Lng' => 6.1033,
                    'TimeZoneLocation' => 'Europe/Zurich',
                    'City' => 'Geneva',
                    'State' => 'Genève',
                    'Country' => 'Switzerland',
                    'StateCode' => 'GE',
                    'CountryCode' => 'CH',
                ])
            )
        );
        $this->dbBuilder->makeReservation(
            (new Reservation(
                '77820000-' . $this->randomString,
                'Hilton Paris Opera',
                new \DateTime('2024-03-28 16:00:00'),
                new \DateTime('2024-04-03 11:00:00'),
                $this->user,
                ['Address' => '108 Rue Saint Lazare Paris, 75008 France']
            ))->setGeoTag(
                new GeoTag('108 Rue Saint Lazare Paris, 75008 France-' . $this->randomString, [
                    'Lat' => 48.8755,
                    'Lng' => 2.3256,
                    'TimeZoneLocation' => 'Europe/Paris',
                    'City' => 'Paris',
                    'State' => 'Île-de-France',
                    'Country' => 'France',
                    'StateCode' => null,
                    'CountryCode' => 'FR',
                ])
            )
        );
    }

    private function createParkingLots()
    {
        $this->dbBuilder->makeParking(
            (new Parking(
                '120700000-' . $this->randomString,
                null,
                new \DateTime('2024-04-23 16:30:00'),
                new \DateTime('2024-04-27 22:00:00'),
                $this->user,
                ['ParkingCompanyName' => 'The Parking Spot', 'Location' => '5200 W 47th St, Chicago, IL, 60638']
            ))->setGeoTag(
                new GeoTag('5200 W 47th St, Chicago, IL, 60638-' . $this->randomString, [
                    'Lat' => 41.8085,
                    'Lng' => -87.7537,
                    'TimeZoneLocation' => 'America/Chicago',
                    'City' => 'Chicago',
                    'State' => 'Illinois',
                    'Country' => 'United States',
                    'StateCode' => 'IL',
                    'CountryCode' => 'US',
                ])
            )
        );
    }

    private function createRentals()
    {
        $this->dbBuilder->makeRental(
            (new Rental(
                '1767-0000-00-0-' . $this->randomString,
                '780 Mcdonnell Road, San Francisco Int l Airport, San Francisco, 94128, United States',
                new \DateTime('2024-05-27 10:00:00'),
                '25500 East 78th Avenue, Denver International Airport, Denver, 80249, United States',
                new \DateTime('2024-06-10 16:00:00'),
                $this->user,
                ['RentalCompanyName' => 'Avis']
            ))->setPickupGeoTag(
                new GeoTag('780 Mcdonnell Road, San Francisco Int l Airport, San Francisco, 94128, United States-' . $this->randomString, [
                    'Lat' => 37.6191,
                    'Lng' => -122.3816,
                    'TimeZoneLocation' => 'America/Los_Angeles',
                    'City' => 'San Francisco',
                    'State' => 'California',
                    'Country' => 'United States',
                    'StateCode' => 'CA',
                    'CountryCode' => 'US',
                ])
            )->setDropoffGeoTag(
                new GeoTag('25500 East 78th Avenue, Denver International Airport, Denver, 80249, United States-' . $this->randomString, [
                    'Lat' => 39.8354,
                    'Lng' => -104.6901,
                    'TimeZoneLocation' => 'America/Denver',
                    'City' => 'Denver',
                    'State' => 'Colorado',
                    'Country' => 'United States',
                    'StateCode' => 'CO',
                    'CountryCode' => 'US',
                ])
            )
        );
    }

    private function createRestaurants()
    {
        $this->dbBuilder->makeRestaurant(
            (new Restaurant(
                '82000-' . $this->randomString,
                'Hanky Panky Cocktail Bar',
                new \DateTime('2024-04-10 19:30:00'),
                null,
                RestaurantEntity::EVENT_RESTAURANT,
                $this->user,
                ['Address' => 'Turin 52 Colonia Juárez 06600 Ciudad de México']
            ))->setGeoTag(
                new GeoTag('Turin 52 Colonia Juárez 06600 Ciudad de México-' . $this->randomString, [
                    'Lat' => 19.4268,
                    'Lng' => -99.1558,
                    'TimeZoneLocation' => 'America/Mexico_City',
                    'City' => 'Mexico City',
                    'State' => 'Ciudad de México',
                    'Country' => 'Mexico',
                    'StateCode' => 'CDMX',
                    'CountryCode' => 'MX',
                ])
            )
        );
    }

    private function createEvents()
    {
        $this->dbBuilder->makeRestaurant(
            (new Restaurant(
                'TA3E0000-' . $this->randomString,
                'The world\'s most beautiful libraries',
                new \DateTime('2024-03-20 10:00:00'),
                new \DateTime('2024-03-20 13:00:00'),
                RestaurantEntity::EVENT_EVENT,
                $this->user,
                ['Address' => 'Praça Floriano, 7 - Centro, Rio de Janeiro - RJ, 20031-050']
            ))->setGeoTag(
                new GeoTag('Praça Floriano, 7- Centro, Rio de Janeiro- RJ, 20031-050-' . $this->randomString, [
                    'Lat' => -22.9113,
                    'Lng' => -43.1758,
                    'TimeZoneLocation' => 'America/Sao_Paulo',
                    'City' => 'Rio de Janeiro',
                    'State' => 'Rio de Janeiro',
                    'Country' => 'Brazil',
                    'StateCode' => 'RJ',
                    'CountryCode' => 'BR',
                ])
            )
        );
    }
}
