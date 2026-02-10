<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\RA\Flight;

use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\Service\AppBot\AppBot;
use AwardWallet\MainBundle\Service\LogProcessor;
use AwardWallet\MainBundle\Service\MileValue\CalcMileValueCommand;
use AwardWallet\MainBundle\Service\MileValue\Constants;
use AwardWallet\MainBundle\Service\RA\Flight\EmailFormatter;
use AwardWallet\MainBundle\Service\RA\Flight\FlightDealSubscriber;
use AwardWallet\MainBundle\Service\RA\Flight\LoggerFactory;
use AwardWallet\MainBundle\Service\RA\Flight\RequestProgressTracker;
use AwardWallet\MainBundle\Service\RA\Flight\SendSearchResultsNotificationCommand;
use AwardWallet\Tests\Modules\DbBuilder\Currency;
use AwardWallet\Tests\Modules\DbBuilder\GeoTag;
use AwardWallet\Tests\Modules\DbBuilder\MileValue;
use AwardWallet\Tests\Modules\DbBuilder\Provider;
use AwardWallet\Tests\Modules\DbBuilder\RAFlight;
use AwardWallet\Tests\Modules\DbBuilder\RAFlightRouteSearchVolume;
use AwardWallet\Tests\Modules\DbBuilder\RAFlightSearchQuery;
use AwardWallet\Tests\Modules\DbBuilder\RAFlightSearchRequest;
use AwardWallet\Tests\Modules\DbBuilder\RAFlightSearchResponse;
use AwardWallet\Tests\Modules\DbBuilder\RAFlightSearchRoute;
use AwardWallet\Tests\Modules\DbBuilder\RAFlightSearchRouteSegment;
use AwardWallet\Tests\Modules\DbBuilder\Trip;
use AwardWallet\Tests\Modules\DbBuilder\TripSegment;
use AwardWallet\Tests\Modules\DbBuilder\User;
use AwardWallet\Tests\Unit\CommandTester;
use Codeception\Stub\Expected;
use Codeception\Stub\StubMarshaler;
use Psr\Log\LoggerInterface;

/**
 * @group frontend-unit
 */
class SendSearchResultsNotificationCommandTest extends CommandTester
{
    /**
     * @var SendSearchResultsNotificationCommand
     */
    protected $command;

    public function testNoSend()
    {
        $this->runCommand(Expected::never());
        $this->logContains('done, processed 0 queries');
    }

    public function testDeletedQuery()
    {
        $this->dbBuilder->makeRAFlightSearchRoute(
            $route = new RAFlightSearchRoute(
                'JFK',
                'LAX',
                $query = new RAFlightSearchQuery(
                    ['JFK'],
                    ['LAX'],
                    date_create('+10 days'),
                    date_create('+20 days'),
                    new User(),
                    null,
                    [],
                    [
                        'DeleteDate' => date('Y-m-d H:i:s'),
                    ]
                ),
                [
                    new RAFlightSearchRouteSegment(
                        date_create('+10 days'),
                        'JFK',
                        date_create('+10 days'),
                        'LAX',
                    ),
                ],
            )
        );
        $this->dbBuilder->makeRAFlightSearchResponse(
            new RAFlightSearchResponse(
                new RAFlightSearchRequest(
                    date_create(),
                    date_create(),
                    'xxx',
                    $query
                ),
                $route
            )
        );
        $this->runCommand(Expected::never());
        $this->logContains(sprintf('query is deleted, id: %d', $query->getId()));
        $this->logContains('done, processed 0 queries');
    }

    public function testArchived()
    {
        $this->dbBuilder->makeRAFlightSearchRoute(
            $route = new RAFlightSearchRoute(
                'JFK',
                'LAX',
                $query = new RAFlightSearchQuery(
                    ['JFK'],
                    ['LAX'],
                    date_create('+10 days'),
                    date_create('+20 days'),
                    new User()
                ),
                [
                    new RAFlightSearchRouteSegment(
                        date_create('+10 days'),
                        'JFK',
                        date_create('+10 days'),
                        'LAX',
                    ),
                ],
                [
                    'Archived' => 1,
                ]
            )
        );
        $this->dbBuilder->makeRAFlightSearchResponse(
            new RAFlightSearchResponse(
                new RAFlightSearchRequest(
                    date_create(),
                    date_create(),
                    'xxx',
                    $query
                ),
                $route
            )
        );
        $this->runCommand(Expected::never());
        $this->logContains(sprintf('no routes found for query %d', $query->getId()));
        $this->logContains('done, processed 0 queries');
    }

    public function testSendToSlack()
    {
        $this->dbBuilder->makeRAFlightSearchRoute(
            $route = new RAFlightSearchRoute(
                'JFK',
                'LAX',
                $query = new RAFlightSearchQuery(
                    ['JFK'],
                    ['LAX'],
                    date_create('+10 days'),
                    date_create('+20 days'),
                    new User()
                ),
                [
                    new RAFlightSearchRouteSegment(
                        date_create('+10 days'),
                        'JFK',
                        date_create('+10 days'),
                        'LAX',
                    ),
                ]
            )
        );
        $this->dbBuilder->makeRAFlightSearchResponse(
            new RAFlightSearchResponse(
                new RAFlightSearchRequest(
                    date_create(),
                    date_create(),
                    'xxx',
                    $query
                ),
                $route
            )
        );
        $this->runCommand(Expected::once());
        $this->logContains('done, processed 1 queries');
    }

    public function testFlagged()
    {
        $this->dbBuilder->makeRAFlightSearchRoute(
            $route = new RAFlightSearchRoute(
                'JFK',
                'LAX',
                $query = new RAFlightSearchQuery(
                    ['JFK'],
                    ['LAX'],
                    date_create('+10 days'),
                    date_create('+20 days'),
                    new User()
                ),
                [
                    new RAFlightSearchRouteSegment(
                        date_create('+10 days'),
                        'JFK',
                        date_create('+10 days'),
                        'LAX',
                    ),
                ],
                [
                    'Flag' => 1,
                ]
            )
        );
        $this->dbBuilder->makeRAFlightSearchResponse(
            new RAFlightSearchResponse(
                new RAFlightSearchRequest(
                    date_create(),
                    date_create(),
                    'xxx',
                    $query
                ),
                $route
            )
        );
        $this->runCommand(Expected::once());
        $this->logContains(sprintf('query %d contains flagged routes', $query->getId()));
        $this->logContains('done, processed 1 queries');
    }

    public function testEmail()
    {
        $this->dbBuilder->makeCurrency($currency = new Currency('miles', null, null, [
            'Plural' => 'mile|miles',
        ]));
        $this->dbBuilder->makeProvider($provider = new Provider(null, [
            'AwardChangePolicy' => 'Test award change policy',
            'Code' => 'mileageplus',
            'Kind' => PROVIDER_KIND_AIRLINE,
            'Currency' => $currency->getId(),
        ]));
        $this->dbBuilder->makeRAFlightSearchQuery(
            $query = new RAFlightSearchQuery(
                ['JFK'],
                ['LAX'],
                date_create('+40 days'),
                date_create('+50 days'),
                $user = new User(),
                new MileValue(
                    $provider,
                    new Trip('F100500', [
                        (new TripSegment(
                            'JFK', 'JFK', date_create('+40 days')->setTime(13, 30),
                            'ORD', 'ORD', date_create('+40 days')->setTime(15, 30),
                            null,
                            [
                                'FlightNumber' => 'SU123',
                                'CabinClass' => 'Business',
                            ]
                        ))->setDepGeoTag(new GeoTag('JFK'))
                            ->setArrGeoTag(new GeoTag('ORD')),
                        (new TripSegment(
                            'ORD', 'ORD', date_create('+40 days')->setTime(17, 10),
                            'LAX', 'LAX', date_create('+40 days')->setTime(20, 15),
                            null,
                            [
                                'FlightNumber' => 'SU456',
                                'CabinClass' => 'Business',
                            ]
                        ))->setDepGeoTag(new GeoTag('ORD'))
                            ->setArrGeoTag(new GeoTag('LAX')),
                    ], $user, [
                        'SpentAwardsProviderID' => $provider->getId(),
                        'SpentAwards' => 160000,
                        'Total' => 100,
                        'CurrencyCode' => 'USD',
                    ]),
                    [
                        'Route' => 'JFK-ORD,ORD-LAX',
                        'RouteType' => Constants::ROUTE_TYPE_ONE_WAY,
                        'International' => 0,
                        'MileRoute' => 'JFK-ORD,lo:1h40m,ORD-LAX',
                        'CashRoute' => 'JFK-ORD,lo:1h40m,ORD-LAX',
                        'BookingClasses' => '',
                        'CabinClass' => 'Business',
                        'ClassOfService' => 'Business',
                        'DepDate' => date_create('+40 days')->setTime(13, 30)->format('Y-m-d H:i:s'),
                        'MileDuration' => 4,
                        'CashDuration' => 4,
                        'TotalMilesSpent' => 160000,
                        'TotalTaxesSpent' => 100,
                        'AlternativeCost' => 200,
                        'MileValue' => 0.8,
                        'TravelersCount' => 1,
                        'Hash' => 'true_hash',
                        'Status' => CalcMileValueCommand::STATUS_GOOD,
                    ]
                ),
                [
                    $route = new RAFlightSearchRoute(
                        'JFK',
                        'LAX',
                        null,
                        [
                            new RAFlightSearchRouteSegment(
                                date_create('+40 days')->setTime(15, 00),
                                'JFK',
                                date_create('+40 days')->setTime(18, 00),
                                'LAX',
                            ),
                        ],
                        [
                            'MileCostProgram' => $provider->getFields()['Code'],
                            'MileCost' => 150000,
                            'Taxes' => 50,
                            'Currency' => 'USD',
                            'Tickets' => 1,
                            'FlightDurationSeconds' => 3600 * 3,
                            'LayoverDurationSeconds' => 0,
                        ]
                    ),
                ]
            )
        );
        $this->dbBuilder->makeRAFlightRouteSearchVolume(
            new RAFlightRouteSearchVolume(
                'JFK',
                'LAX',
                150,
                0,
                [
                    'TimesSearched' => 200,
                    'ProviderID' => $provider->getId(),
                    'ClassOfService' => 'business',
                ]
            )
        );
        $this->dbBuilder->makeRAFlight(
            new RAFlight('mileageplus', [
                'StandardItineraryCOS' => 'business',
                'FromAirport' => 'JFK',
                'FromCountry' => 'United States',
                'ToAirport' => 'LAX',
                'ToCountry' => 'United States',
                'MileCost' => 80000,
                'DepartureDate' => date_create('-5 days')->format('Y-m-d'),
                'ArrivalDate' => date_create('-4 days')->format('Y-m-d'),
                'TravelTime' => 60,
                'SearchDate' => '2024-07-02',
            ]),
        );
        $this->dbBuilder->makeRAFlight(
            new RAFlight('mileageplus', [
                'StandardItineraryCOS' => 'business',
                'FromAirport' => 'JFK',
                'FromCountry' => 'United States',
                'ToAirport' => 'LAX',
                'ToCountry' => 'United States',
                'MileCost' => 80000,
                'DepartureDate' => date_create('-5 days')->format('Y-m-d'),
                'ArrivalDate' => date_create('-4 days')->format('Y-m-d'),
                'TravelTime' => 60,
                'SearchDate' => date_create('-20 days')->format('Y-m-d'),
            ]),
        );
        $this->dbBuilder->makeRAFlightSearchResponse(
            new RAFlightSearchResponse(
                new RAFlightSearchRequest(
                    date_create(),
                    date_create(),
                    'xxx',
                    $query
                ),
                $route
            )
        );

        $this->runCommand(null, Expected::once());
    }

    private function runCommand(?StubMarshaler $slackMock, ?StubMarshaler $emailMock = null)
    {
        $this->cleanCommand();
        $this->command = new SendSearchResultsNotificationCommand(
            $this->container->get(RequestProgressTracker::class),
            $this->em->getConnection(),
            $this->em,
            $this->makeEmpty(LoggerFactory::class, [
                'createLogger' => $this->container->get(LoggerInterface::class),
                'createProcessor' => new LogProcessor(),
            ]),
            'https://awardwallet.com',
            $this->makeEmpty(AppBot::class, [
                'send' => $slackMock ?? Expected::never(),
            ]),
            $this->makeEmpty(Mailer::class, [
                'send' => $emailMock ?? Expected::never(),
            ]),
            $this->container->get(FlightDealSubscriber::class),
            $this->container->get(EmailFormatter::class)
        );
        $this->initCommand($this->command);
        $this->clearLogs();
        $this->executeCommand();
    }
}
