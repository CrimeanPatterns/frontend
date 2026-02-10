<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\RA\Flight;

use AwardWallet\MainBundle\Entity\Trip as EntityTrip;
use AwardWallet\MainBundle\Service\AirportFinder;
use AwardWallet\MainBundle\Service\LogProcessor;
use AwardWallet\MainBundle\Service\MileValue\CalcMileValueCommand;
use AwardWallet\MainBundle\Service\MileValue\Constants;
use AwardWallet\MainBundle\Service\MileValue\MileValueService;
use AwardWallet\MainBundle\Service\MileValue\TripLoaderFactory;
use AwardWallet\MainBundle\Service\RA\Flight\FlightDealSubscriber;
use AwardWallet\MainBundle\Service\RA\Flight\LoggerFactory;
use AwardWallet\Tests\Modules\DbBuilder\GeoTag;
use AwardWallet\Tests\Modules\DbBuilder\MileValue;
use AwardWallet\Tests\Modules\DbBuilder\Provider;
use AwardWallet\Tests\Modules\DbBuilder\RAFlight;
use AwardWallet\Tests\Modules\DbBuilder\RAFlightRouteSearchVolume;
use AwardWallet\Tests\Modules\DbBuilder\Trip;
use AwardWallet\Tests\Modules\DbBuilder\TripSegment;
use AwardWallet\Tests\Modules\DbBuilder\User;
use AwardWallet\Tests\Unit\BaseContainerTest;
use Codeception\Stub\Expected;
use Codeception\Stub\StubMarshaler;
use Psr\Log\Test\TestLogger;

/**
 * @group frontend-unit
 */
class FlightDealSubscriberTest extends BaseContainerTest
{
    private ?TestLogger $logger = null;

    public function _before()
    {
        parent::_before();

        $this->logger = new TestLogger();
        $this->db->executeQuery("DELETE FROM RAFlight WHERE FromAirport = 'PEE'");
        $this->db->executeQuery("DELETE FROM RAFlightRouteSearchVolume WHERE DepartureAirport = 'PEE'");
    }

    public function _after()
    {
        $this->logger = null;

        parent::_after();
    }

    public function testNoMileValue()
    {
        $this->getFlightDealSubscriber(Expected::never())->syncByMileValue(0);
        $this->assertLogsContainsError('MileValue #0 not found');
    }

    public function testNoTrip()
    {
        $this->dbBuilder->makeMileValue(
            $mileValue = $this->makeMileValue($this->makeProvider(), null)
        );

        $this->getFlightDealSubscriber(Expected::never())->syncByMileValue($mileValue->getId());
        $this->assertLogsContainsError('has no trip');
        $this->db->dontSeeInDatabase('RAFlightSearchQuery', ['MileValueID' => $mileValue->getId()]);
    }

    /**
     * @dataProvider mileValueInvalidStatusProvider
     */
    public function testMileValueInvalidStatus(string $status)
    {
        $this->dbBuilder->makeMileValue(
            $mileValue = $this->makeMileValue(
                $this->makeProvider(),
                $this->makeTrip(),
                ['Status' => $status]
            )
        );

        $this->getFlightDealSubscriber(Expected::never())->syncByMileValue($mileValue->getId());
        $this->assertLogsContainsInfo('has invalid status');
        $this->db->dontSeeInDatabase('RAFlightSearchQuery', ['MileValueID' => $mileValue->getId()]);
    }

    public function mileValueInvalidStatusProvider(): array
    {
        return [
            [CalcMileValueCommand::STATUS_ERROR],
        ];
    }

    /**
     * @dataProvider mileValueInvalidRouteTypeProvider
     */
    public function testMileValueInvalidRouteType(string $routeType)
    {
        $this->dbBuilder->makeMileValue(
            $mileValue = $this->makeMileValue(
                $this->makeProvider(),
                $this->makeTrip(),
                [
                    'Status' => CalcMileValueCommand::STATUS_NEW,
                    'RouteType' => $routeType,
                ]
            )
        );

        $this->getFlightDealSubscriber(Expected::never())->syncByMileValue($mileValue->getId());
        $this->assertLogsContainsInfo('has invalid route type');
        $this->db->dontSeeInDatabase('RAFlightSearchQuery', ['MileValueID' => $mileValue->getId()]);
    }

    public function mileValueInvalidRouteTypeProvider(): array
    {
        return [
            [Constants::ROUTE_TYPE_MULTI_CITY],
            [Constants::ROUTE_TYPE_ROUND_TRIP],
        ];
    }

    public function testStopover()
    {
        $this->dbBuilder->makeMileValue(
            $mileValue = $this->makeMileValue(
                $this->makeProvider(),
                $this->makeTrip(),
                [
                    'Status' => CalcMileValueCommand::STATUS_GOOD,
                    'RouteType' => Constants::ROUTE_TYPE_ONE_WAY,
                    'MileRoute' => 'JFK-HND,so:19d,NRT-SEA',
                ]
            )
        );

        $this->getFlightDealSubscriber(Expected::never())->syncByMileValue($mileValue->getId());
        $this->assertLogsContainsInfo('has stopover');
        $this->db->dontSeeInDatabase('RAFlightSearchQuery', ['MileValueID' => $mileValue->getId()]);
    }

    /**
     * @dataProvider invalidTripProvider
     */
    public function testInvalidTrip(array $tripFields)
    {
        $this->dbBuilder->makeMileValue(
            $mileValue = $this->makeMileValue(
                $this->makeProvider(),
                $this->makeTrip($tripFields),
                [
                    'Status' => CalcMileValueCommand::STATUS_GOOD,
                    'RouteType' => Constants::ROUTE_TYPE_ONE_WAY,
                ]
            )
        );

        $this->getFlightDealSubscriber(Expected::never())->syncByMileValue($mileValue->getId());
        $this->assertLogsContainsInfo('has no valid trip');
        $this->db->dontSeeInDatabase('RAFlightSearchQuery', ['MileValueID' => $mileValue->getId()]);
    }

    public function invalidTripProvider(): array
    {
        return [
            'cancelled' => [['Cancelled' => 1]],
            'hidden' => [['Hidden' => 1]],
            'category' => [['Category' => EntityTrip::CATEGORY_BUS]],
        ];
    }

    public function testMileValueInvalidHash()
    {
        $trip = $this->makeTrip();

        $this->dbBuilder->makeMileValue(
            $mileValue = $this->makeMileValue(
                $this->makeProvider(),
                $trip,
                [
                    'Status' => CalcMileValueCommand::STATUS_GOOD,
                    'RouteType' => Constants::ROUTE_TYPE_ONE_WAY,
                    'Hash' => 'invalid',
                ]
            )
        );

        $this->getFlightDealSubscriber(Expected::never())->syncByMileValue($mileValue->getId());
        $this->assertLogsContainsInfo('has invalid hash');
        $this->db->dontSeeInDatabase('RAFlightSearchQuery', ['MileValueID' => $mileValue->getId()]);
    }

    public function testInvalidProvider()
    {
        $provider = $this->makeProvider(['Code' => 'invalid_provider']);
        $trip = $this->makeTrip([], '+32 day', null, $provider);

        $this->dbBuilder->makeMileValue(
            $mileValue = $this->makeMileValue(
                $provider,
                $trip,
                [
                    'Status' => CalcMileValueCommand::STATUS_GOOD,
                    'RouteType' => Constants::ROUTE_TYPE_ONE_WAY,
                    'Hash' => 'true_hash',
                ]
            )
        );

        $this->getFlightDealSubscriber(Expected::never())->syncByMileValue($mileValue->getId());
        $this->assertLogsContainsInfo('has provider id "' . $provider->getId() . '"');
        $this->db->dontSeeInDatabase('RAFlightSearchQuery', ['MileValueID' => $mileValue->getId()]);
    }

    public function testTripDepDateTooClose()
    {
        $trip = $this->makeTrip([], '+1 day');

        $this->dbBuilder->makeMileValue(
            $mileValue = $this->makeMileValue(
                $this->makeProvider(),
                $trip,
                [
                    'Status' => CalcMileValueCommand::STATUS_GOOD,
                    'RouteType' => Constants::ROUTE_TYPE_ONE_WAY,
                    'Hash' => 'true_hash',
                ]
            )
        );

        $this->getFlightDealSubscriber(Expected::never())->syncByMileValue($mileValue->getId());
        $this->assertLogsContainsInfo('departure date in the past or too close');
        $this->db->dontSeeInDatabase('RAFlightSearchQuery', ['MileValueID' => $mileValue->getId()]);
    }

    public function testTotalMilesSpentNotMultipleOf500()
    {
        $trip = $this->makeTrip();
        $this->dbBuilder->makeMileValue(
            $mileValue = $this->makeMileValue(
                $this->makeProvider(),
                $trip,
                [
                    'Status' => CalcMileValueCommand::STATUS_GOOD,
                    'RouteType' => Constants::ROUTE_TYPE_ONE_WAY,
                    'Hash' => 'true_hash',
                    'TotalMilesSpent' => 160001,
                ]
            )
        );

        $this->getFlightDealSubscriber(Expected::never())->syncByMileValue($mileValue->getId());
        $this->assertLogsContainsInfo('has TotalMilesSpent % 500 != 0');
        $this->db->dontSeeInDatabase('RAFlightSearchQuery', ['MileValueID' => $mileValue->getId()]);
    }

    public function testCheckSearchHistory()
    {
        $trip = $this->makeTrip();
        $this->dbBuilder->makeMileValue(
            $mileValue = $this->makeMileValue(
                $this->makeProvider(),
                $trip,
                [
                    'Status' => CalcMileValueCommand::STATUS_GOOD,
                    'RouteType' => Constants::ROUTE_TYPE_ONE_WAY,
                    'Hash' => 'true_hash',
                    'TotalMilesSpent' => 160000,
                ]
            )
        );

        $this->getFlightDealSubscriber(Expected::never())->syncByMileValue($mileValue->getId());
        $this->assertLogsContainsInfo('route PEE-DME, class Business, providerId: 26 has not enough search history');
        $this->db->dontSeeInDatabase('RAFlightSearchQuery', ['MileValueID' => $mileValue->getId()]);
    }

    public function testNoMileCostLimit()
    {
        $trip = $this->makeTrip();
        $this->dbBuilder->makeMileValue(
            $mileValue = $this->makeMileValue(
                $provider = $this->makeProvider(),
                $trip,
                [
                    'Status' => CalcMileValueCommand::STATUS_GOOD,
                    'RouteType' => Constants::ROUTE_TYPE_ONE_WAY,
                    'Hash' => 'true_hash',
                    'TotalMilesSpent' => 160000,
                ]
            )
        );

        // add history
        $this->dbBuilder->makeRAFlightRouteSearchVolume($this->makeRAFlightRouteSearchVolume(
            'PEE',
            'DME',
            $provider->getId(),
            26,
        ));

        $this->getFlightDealSubscriber(Expected::never())->syncByMileValue($mileValue->getId());
        $this->assertLogsContainsInfo('no mile cost limit found');
        $this->db->dontSeeInDatabase('RAFlightSearchQuery', ['MileValueID' => $mileValue->getId()]);
    }

    public function testTotalMilesSpentLowerThanLimit()
    {
        $trip = $this->makeTrip();
        $this->dbBuilder->makeMileValue(
            $mileValue = $this->makeMileValue(
                $provider = $this->makeProvider(),
                $trip,
                [
                    'Status' => CalcMileValueCommand::STATUS_GOOD,
                    'RouteType' => Constants::ROUTE_TYPE_ONE_WAY,
                    'Hash' => 'true_hash',
                    'TotalMilesSpent' => 90000,
                ]
            )
        );

        // add info for MileCostLimit
        $this->dbBuilder->makeRAFlight($this->makeRaFlight());
        // add history
        $this->dbBuilder->makeRAFlightRouteSearchVolume($this->makeRAFlightRouteSearchVolume(
            'PEE',
            'DME',
            $provider->getId(),
            26,
        ));

        $this->getFlightDealSubscriber(Expected::never())->syncByMileValue($mileValue->getId());
        $this->assertLogsContainsInfo('TotalMilesSpent "90000" is lower than limit "100000"');
        $this->db->dontSeeInDatabase('RAFlightSearchQuery', ['MileValueID' => $mileValue->getId()]);
    }

    public function testBookedDurationLowerThanThreshold()
    {
        $trip = $this->makeTrip();
        $this->dbBuilder->makeMileValue(
            $mileValue = $this->makeMileValue(
                $provider = $this->makeProvider(),
                $trip,
                [
                    'Status' => CalcMileValueCommand::STATUS_GOOD,
                    'RouteType' => Constants::ROUTE_TYPE_ONE_WAY,
                    'Hash' => 'true_hash',
                    'TotalMilesSpent' => 160000,
                ]
            )
        );

        // add info for MileCostLimit
        $this->dbBuilder->makeRAFlight($this->makeRaFlight('mileageplus', [
            'SearchDate' => '2024-07-01',
        ]));
        // add info for booked duration
        $this->dbBuilder->makeRAFlight($this->makeRaFlight('mileageplus', [
            // 3 hours
            'TravelTime' => 180,
            'SearchDate' => date_create('-5 days')->format('Y-m-d'),
        ]));
        // add history
        $this->dbBuilder->makeRAFlightRouteSearchVolume($this->makeRAFlightRouteSearchVolume(
            'PEE',
            'DME',
            $provider->getId(),
            26,
        ));

        $this->getFlightDealSubscriber(Expected::never())->syncByMileValue($mileValue->getId());
        $this->assertLogsContainsInfo('booked duration "4.00" is lower than threshold "4.29"');
        $this->db->dontSeeInDatabase('RAFlightSearchQuery', ['MileValueID' => $mileValue->getId()]);
    }

    public function testBookedStopsLowerOrEqualThanThreshold()
    {
        $depDate = '+32 day';
        $this->dbBuilder->makeProvider($provider = $this->makeProvider());
        $trip = new Trip('xx1', [
            (new TripSegment(
                'PEE', 'PEE', date_create($depDate),
                'DME', 'DME', date_create($depDate)->modify('+2 hour'),
                null,
                [
                    'CabinClass' => 'Business',
                ]
            ))
                ->setDepGeoTag(new GeoTag('PEE'))
                ->setArrGeoTag(new GeoTag('DME')),

            (new TripSegment(
                'DME', 'DME', date_create($depDate)->modify('+4 hour'),
                'LED', 'LED', date_create($depDate)->modify('+6 hour'),
                null,
                [
                    'CabinClass' => 'Business',
                ]
            ))
                ->setDepGeoTag(new GeoTag('DME'))
                ->setArrGeoTag(new GeoTag('LED')),
        ], new User(), [
            'SpentAwardsProviderID' => $provider->getId(),
            'SpentAwards' => 50000,
        ]);
        $this->dbBuilder->makeMileValue(
            $mileValue = new MileValue($provider, $trip, [
                'Route' => 'PEE-DME,lo:2h,DME-LED',
                'MileRoute' => 'PEE-DME,lo:2h,DME-LED',
                'CashRoute' => 'PEE-DME,lo:2h,DME-LED',
                'International' => 0,
                'BookingClasses' => '',
                'CabinClass' => 'Business',
                'ClassOfService' => 'Business',
                'DepDate' => date_create($depDate)->format('Y-m-d'),
                'MileDuration' => 2,
                'CashDuration' => 2,
                'TotalMilesSpent' => 190000,
                'TotalTaxesSpent' => 100,
                'AlternativeCost' => 300,
                'MileValue' => 0.8,
                'TravelersCount' => 1,
                'Status' => CalcMileValueCommand::STATUS_GOOD,
                'RouteType' => Constants::ROUTE_TYPE_ONE_WAY,
                'Hash' => 'true_hash',
            ])
        );
        // add info for MileCostLimit
        $this->dbBuilder->makeRAFlight($this->makeRaFlight('mileageplus', [
            'SearchDate' => '2024-07-01',
        ]));
        // add info for booked stops
        $this->dbBuilder->makeRAFlight(
            new RAFlight('mileageplus', [
                'StandardItineraryCOS' => 'business',
                'FromAirport' => 'PEE',
                'FromCountry' => 'Russia',
                'ToAirport' => 'LED',
                'ToCountry' => 'Russia',
                'MileCost' => 80000,
                'DepartureDate' => date_create('-5 days')->format('Y-m-d'),
                'ArrivalDate' => date_create('-4 days')->format('Y-m-d'),
                'TravelTime' => 180,
                'SearchDate' => date_create('-15 days')->format('Y-m-d'),
                'Layovers' => 1,
            ])
        );
        // add history
        $this->dbBuilder->makeRAFlightRouteSearchVolume($this->makeRAFlightRouteSearchVolume(
            'PEE',
            'LED',
            $provider->getId(),
            26,
        ));

        $this->getFlightDealSubscriber(Expected::never())->syncByMileValue($mileValue->getId());
        $this->assertLogsContainsInfo('booked stops "1" is lower or equal than threshold "1"');
        $this->db->dontSeeInDatabase('RAFlightSearchQuery', ['MileValueID' => $mileValue->getId()]);
    }

    public function testCreateSearchQuery()
    {
        $depDate = '+32 day';
        $this->dbBuilder->makeProvider($provider = $this->makeProvider());
        $trip = new Trip('xx1', [
            (new TripSegment(
                'PEE', 'PEE', date_create($depDate),
                'DME', 'DME', date_create($depDate)->modify('+2 hour'),
                null,
                [
                    'CabinClass' => 'Business',
                ]
            ))
                ->setDepGeoTag(new GeoTag('PEE'))
                ->setArrGeoTag(new GeoTag('DME')),

            (new TripSegment(
                'DME', 'DME', date_create($depDate)->modify('+4 hour'),
                'LED', 'LED', date_create($depDate)->modify('+6 hour'),
                null,
                [
                    'CabinClass' => 'Business',
                ]
            ))
                ->setDepGeoTag(new GeoTag('DME'))
                ->setArrGeoTag(new GeoTag('LED')),
        ], new User(), [
            'SpentAwardsProviderID' => $provider->getId(),
            'SpentAwards' => 50000,
        ]);
        $this->dbBuilder->makeMileValue(
            $mileValue = new MileValue($provider, $trip, [
                'Route' => 'PEE-DME,lo:2h,DME-LED',
                'MileRoute' => 'PEE-DME,lo:2h,DME-LED',
                'CashRoute' => 'PEE-DME,lo:2h,DME-LED',
                'International' => 0,
                'BookingClasses' => '',
                'CabinClass' => 'Business',
                'ClassOfService' => 'Business',
                'DepDate' => date_create($depDate)->format('Y-m-d'),
                'MileDuration' => 2,
                'CashDuration' => 2,
                'TotalMilesSpent' => 190000,
                'TotalTaxesSpent' => 100,
                'AlternativeCost' => 300,
                'MileValue' => 0.8,
                'TravelersCount' => 1,
                'Status' => CalcMileValueCommand::STATUS_GOOD,
                'RouteType' => Constants::ROUTE_TYPE_ONE_WAY,
                'Hash' => 'true_hash',
            ])
        );
        // add info for MileCostLimit
        $this->dbBuilder->makeRAFlight($this->makeRaFlight('mileageplus', [
            'SearchDate' => '2024-07-01',
        ]));
        // add info for booked stops
        $this->dbBuilder->makeRAFlight(
            new RAFlight('mileageplus', [
                'StandardItineraryCOS' => 'business',
                'FromAirport' => 'PEE',
                'FromCountry' => 'Russia',
                'ToAirport' => 'LED',
                'ToCountry' => 'Russia',
                'MileCost' => 80000,
                'DepartureDate' => date_create('-5 days')->format('Y-m-d'),
                'ArrivalDate' => date_create('-4 days')->format('Y-m-d'),
                'TravelTime' => 180,
                'SearchDate' => date_create('-15 days')->format('Y-m-d'),
                'Layovers' => 0,
            ])
        );
        // add history
        $this->dbBuilder->makeRAFlightRouteSearchVolume($this->makeRAFlightRouteSearchVolume(
            'PEE',
            'LED',
            $provider->getId(),
            26,
        ));

        $this->getFlightDealSubscriber(Expected::once([]))->syncByMileValue($mileValue->getId());
        $this->assertLogsContainsInfo('creating search query for MileValue #' . $mileValue->getId());
        $this->db->seeInDatabase('RAFlightSearchQuery', [
            'MileValueID' => $mileValue->getId(),
            'AutoSelectParsers' => 0,
            'BusinessMilesLimit' => (int) ceil(80000 / 0.8),
        ]);
    }

    private function getFlightDealSubscriber(StubMarshaler $airportFinderStub): FlightDealSubscriber
    {
        return new FlightDealSubscriber(
            $this->em->getConnection(),
            $this->makeEmpty(LoggerFactory::class, [
                'createProcessor' => $this->makeEmpty(LogProcessor::class),
                'createLogger' => $this->logger,
            ]),
            $this->makeEmpty(AirportFinder::class, [
                'findNearestAirports' => $airportFinderStub,
            ]),
            $this->container->get(TripLoaderFactory::class),
            $this->makeEmpty(MileValueService::class),
            $this->container->get(\Memcached::class),
            true
        );
    }

    private function assertLogsContainsError(string $message): void
    {
        $this->assertTrue($this->logger->hasErrorThatContains($message), json_encode($this->logger->records));
    }

    private function assertLogsContainsInfo(string $message): void
    {
        $this->assertTrue($this->logger->hasInfoThatContains($message), json_encode($this->logger->records));
    }

    private function makeMileValue(?Provider $provider, ?Trip $trip, array $fields = []): MileValue
    {
        return new MileValue($provider, $trip, array_merge([
            'Route' => 'PEE-DME',
            'International' => 0,
            'MileRoute' => 'PEE-DME',
            'CashRoute' => 'PEE-DME',
            'BookingClasses' => '',
            'CabinClass' => 'Business',
            'ClassOfService' => 'Business',
            'DepDate' => date_create('+5 days')->format('Y-m-d'),
            'MileDuration' => 2,
            'CashDuration' => 2,
            'TotalMilesSpent' => 160000,
            'TotalTaxesSpent' => 100,
            'AlternativeCost' => 300,
            'MileValue' => 0.8,
            'TravelersCount' => 1,
        ], $fields));
    }

    private function makeTrip(
        array $fields = [],
        string $depDate = '+32 day',
        ?User $user = null,
        ?Provider $provider = null
    ): Trip {
        if (is_null($provider)) {
            $provider = $this->makeProvider();
        }

        $this->dbBuilder->makeProvider($provider);

        return new Trip('xx1', [
            (new TripSegment(
                'PEE', 'PEE', date_create($depDate),
                'DME', 'DME', date_create($depDate)->modify('+2 hour'),
                null,
                [
                    'CabinClass' => 'Business',
                ]
            ))->setDepGeoTag(new GeoTag('PEE'))
                ->setArrGeoTag(new GeoTag('DME')),
        ], $user ?? new User(), array_merge([
            'SpentAwardsProviderID' => $provider->getId(),
            'SpentAwards' => 50000,
        ], $fields));
    }

    private function makeProvider(array $fields = []): Provider
    {
        return new Provider(null, array_merge(['Code' => 'mileageplus'], $fields));
    }

    private function makeRaFlight(string $providerCode = 'mileageplus', array $fields = []): RAFlight
    {
        return new RAFlight($providerCode, array_merge([
            'StandardItineraryCOS' => 'business',
            'FromAirport' => 'PEE',
            'FromCountry' => 'Russia',
            'ToAirport' => 'DME',
            'ToCountry' => 'Russia',
            'MileCost' => 80000,
            'DepartureDate' => date_create('-5 days')->format('Y-m-d'),
            'ArrivalDate' => date_create('-4 days')->format('Y-m-d'),
            'TravelTime' => 10000,
            'SearchDate' => '2024-07-02',
        ], $fields));
    }

    private function makeRAFlightRouteSearchVolume(
        string $fromAirport,
        string $toAirport,
        int $providerId,
        int $timesSearched = 200,
        string $classOfService = Constants::CLASS_BUSINESS,
        int $saved = 1
    ): RAFlightRouteSearchVolume {
        return new RAFlightRouteSearchVolume($fromAirport, $toAirport, $saved, 0, [
            'TimesSearched' => $timesSearched,
            'ProviderID' => $providerId,
            'ClassOfService' => $classOfService,
        ]);
    }
}
