<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Service\RA\Flight;

use AwardWallet\MainBundle\Entity\RAFlightSearchQuery;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\RA\Flight\Duration;
use AwardWallet\Tests\FunctionalSymfony\Traits\StaffUser;
use AwardWallet\Tests\Modules\AutoVerifyMocksTrait;
use Codeception\Example;

/**
 * @group frontend-functional
 * @group xxx
 */
class CallbackControllerCest extends AbstractCest
{
    use AutoVerifyMocksTrait;
    use StaffUser;

    public function testInvalidCredentials(\TestSymfonyGuy $I): void
    {
        $I->send('POST', $this->router->generate('aw_ra_flight_search_result'));
        $I->seeResponseCodeIs(403);
    }

    public function testInvalidData(\TestSymfonyGuy $I): void
    {
        $this->sendCallbackRequest($I);
        $I->seeResponseCodeIs(400);
        $I->assertTrue($this->logger->hasErrorThatContains('invalid data, not an array'));
    }

    public function testInvalidRequestId(\TestSymfonyGuy $I): void
    {
        $data = $this->apiLax2Jfk();
        unset($data['response'][0]['requestId']);

        $this->sendCallbackRequest($I, $data);
        $I->seeResponseCodeIs(200);
        $I->assertTrue($this->logger->hasInfoThatContains('Invalid request id'));
    }

    public function testInvalidRequestDate(\TestSymfonyGuy $I): void
    {
        $data = $this->apiLax2Jfk();
        unset($data['response'][0]['requestDate']);
        $this->sendCallbackRequest($I, $data);
        $I->seeResponseCodeIs(200);
        $I->assertTrue($this->logger->hasInfoThatContains('Invalid request date'));
    }

    public function testInvalidState(\TestSymfonyGuy $I): void
    {
        $data = $this->apiLax2Jfk();
        $data['response'][0]['state'] = 0;
        $this->sendCallbackRequest($I, $data);
        $I->seeResponseCodeIs(200);
        $I->assertTrue($this->logger->hasInfoThatContains('flight search data has not been successfully retrieved'));
    }

    public function testInvalidUserData(\TestSymfonyGuy $I): void
    {
        $data = $this->apiLax2Jfk();
        $data['response'][0]['userData'] = 'invalid';
        $this->sendCallbackRequest($I, $data);
        $I->seeResponseCodeIs(200);
        $I->assertTrue($this->logger->hasInfoThatContains('Invalid user data'));
    }

    public function testInvalidId(\TestSymfonyGuy $I): void
    {
        $data = $this->apiLax2Jfk();
        $data['response'][0]['userData'] = json_encode([]);
        $this->sendCallbackRequest($I, $data);
        $I->seeResponseCodeIs(200);
        $I->assertTrue($this->logger->hasInfoThatContains('Invalid user data, id or parser is not set'));
    }

    public function testInvalidParser(\TestSymfonyGuy $I): void
    {
        $data = $this->apiLax2Jfk();
        $data['response'][0]['userData'] = json_encode(['id' => 1]);
        $this->sendCallbackRequest($I, $data);
        $I->seeResponseCodeIs(200);
        $I->assertTrue($this->logger->hasInfoThatContains('Invalid user data, id or parser is not set'));
    }

    public function testQueryNotFound(\TestSymfonyGuy $I): void
    {
        $data = $this->apiLax2Jfk();
        $data['response'][0]['userData'] = json_encode(['id' => 999999, 'parser' => 'test']);
        $this->sendCallbackRequest($I, $data);
        $I->seeResponseCodeIs(200);
        $I->assertTrue($this->logger->hasInfoThatContains('query not found'));
    }

    public function testNotEqualParser(\TestSymfonyGuy $I): void
    {
        $queryId = $this->addSearchQuery($I, [
            'Parsers' => 'qwerty',
        ]);
        $data = $this->apiLax2Jfk($queryId);
        $data['response'][0]['userData'] = json_encode(['id' => $queryId, 'parser' => 'test']);
        $this->sendCallbackRequest($I, $data);
        $I->seeResponseCodeIs(200);
        $I->assertTrue($this->logger->hasInfoThatContains('invalid parser "test"'));
    }

    public function testInvalidRoutesData(\TestSymfonyGuy $I): void
    {
        $queryId = $this->addSearchQuery($I);
        $data = $this->apiLax2Jfk($queryId);
        $data['response'][0]['routes'] = [];
        $this->sendCallbackRequest($I, $data);
        $I->seeResponseCodeIs(200);
        $I->assertTrue($this->logger->hasInfoThatContains('Invalid routes data'));
    }

    public function testInvalidSegmentsData(\TestSymfonyGuy $I): void
    {
        $queryId = $this->addSearchQuery($I);
        $data = $this->apiLax2Jfk($queryId);
        $data['response'][0]['routes'][0]['segments'] = [];
        $this->sendCallbackRequest($I, $data);
        $I->seeResponseCodeIs(200);
        $I->assertTrue($this->logger->hasInfoThatContains('Invalid segments data'));
    }

    public function testInvalidSegmentAirport(\TestSymfonyGuy $I): void
    {
        $queryId = $this->addSearchQuery($I);
        $data = $this->apiLax2Jfk($queryId);
        $data['response'][0]['routes'][0]['segments'][0]['departure']['airport'] = null;
        $this->sendCallbackRequest($I, $data);
        $I->seeResponseCodeIs(200);
        $I->assertTrue($this->logger->hasInfoThatContains('Invalid segment airport'));
    }

    public function testInvalidSegmentDate(\TestSymfonyGuy $I): void
    {
        $queryId = $this->addSearchQuery($I);
        $data = $this->apiLax2Jfk($queryId);
        $data['response'][0]['routes'][0]['segments'][0]['departure']['dateTime'] = null;
        $this->sendCallbackRequest($I, $data);
        $I->seeResponseCodeIs(200);
        $I->assertTrue($this->logger->hasInfoThatContains('Invalid segment date'));
    }

    public function testInvalidSegmentCabin(\TestSymfonyGuy $I): void
    {
        $queryId = $this->addSearchQuery($I);
        $data = $this->apiLax2Jfk($queryId);
        $data['response'][0]['routes'][0]['segments'][0]['cabin'] = null;
        $this->sendCallbackRequest($I, $data);
        $I->seeResponseCodeIs(200);
        $I->assertTrue($this->logger->hasInfoThatContains('Invalid segment cabin'));
    }

    public function testInvalidSegmentCabin2(\TestSymfonyGuy $I): void
    {
        $queryId = $this->addSearchQuery($I);
        $data = $this->apiLax2Jfk($queryId);
        $data['response'][0]['routes'][0]['segments'][0]['cabin'] = 'xxx';
        $this->sendCallbackRequest($I, $data);
        $I->seeResponseCodeIs(200);
        $I->assertTrue($this->logger->hasInfoThatContains('Invalid cabin "xxx"'));
    }

    public function testInvalidSegmentFlightDuration(\TestSymfonyGuy $I): void
    {
        $queryId = $this->addSearchQuery($I);
        $data = $this->apiLax2Jfk(
            $queryId,
            null,
            null
        );
        $this->sendCallbackRequest($I, $data);
        $I->seeResponseCodeIs(200);
        $I->assertTrue($this->logger->hasInfoThatContains('Invalid segment flight duration'));
    }

    public function testInvalidFormatSegmentFlightDuration(\TestSymfonyGuy $I): void
    {
        $queryId = $this->addSearchQuery($I);
        $data = $this->apiLax2Jfk(
            $queryId,
            null,
            '1h 30m'
        );
        $this->sendCallbackRequest($I, $data);
        $I->seeResponseCodeIs(200);
        $I->assertTrue($this->logger->hasInfoThatContains('Invalid format of segment flight duration'));
    }

    public function testInvalidFormatSegmentLayoverDuration(\TestSymfonyGuy $I): void
    {
        $queryId = $this->addSearchQuery($I);
        $data = $this->apiLax2Jfk(
            $queryId,
            null,
            '1:30',
            '1h 30m'
        );
        $this->sendCallbackRequest($I, $data);
        $I->seeResponseCodeIs(200);
        $I->assertTrue($this->logger->hasInfoThatContains('Invalid format of segment layover duration'));
    }

    public function testEmptyMileCost(\TestSymfonyGuy $I): void
    {
        $queryId = $this->addSearchQuery($I);
        $data = $this->apiLax2Jfk($queryId, null);
        $this->sendCallbackRequest($I, $data);
        $I->seeResponseCodeIs(200);
        $I->assertTrue($this->logger->hasInfoThatContains('Invalid mile cost'));
    }

    public function testUnmatchDepartureAirports(\TestSymfonyGuy $I): void
    {
        $queryId = $this->addSearchQuery($I);
        $data = $this->apiLax2Jfk($queryId);
        $data['response'][0]['routes'][0]['segments'][0]['departure']['airport'] = 'PEE';
        $this->sendCallbackRequest($I, $data);
        $I->seeResponseCodeIs(200);
        $I->assertTrue($this->logger->hasInfoThatContains('Departure airport does not match'));
    }

    public function testUnmatchArrivalAirports(\TestSymfonyGuy $I): void
    {
        $queryId = $this->addSearchQuery($I);
        $data = $this->apiLax2Jfk($queryId);
        $data['response'][0]['routes'][0]['segments'][0]['arrival']['airport'] = 'PEE';
        $this->sendCallbackRequest($I, $data);
        $I->seeResponseCodeIs(200);
        $I->assertTrue($this->logger->hasInfoThatContains('Arrival airport does not match'));
    }

    /**
     * @dataProvider invalidDepartureDateProvider
     */
    public function testUnmatchDepartureDates(\TestSymfonyGuy $I, Example $example): void
    {
        $queryId = $this->addSearchQuery($I);
        $data = $this->apiLax2Jfk($queryId);
        $data['response'][0]['routes'][0]['segments'][0]['departure']['dateTime'] = date_create($example[0])->format('Y-m-d H:i:s');
        $this->sendCallbackRequest($I, $data);
        $I->seeResponseCodeIs(200);
        $I->assertTrue($this->logger->hasInfoThatContains('Departure date does not match'));
    }

    public function testUnmatchTicketCount(\TestSymfonyGuy $I): void
    {
        $queryId = $this->addSearchQuery($I, [
            'Adults' => 3,
        ]);
        $data = $this->apiLax2Jfk($queryId, 100, '1:30', null, 2);
        $this->sendCallbackRequest($I, $data);
        $I->seeResponseCodeIs(200);
        $I->assertTrue($this->logger->hasInfoThatContains('Count of tickets less than adults'));
    }

    public function testMilesLimitIsNotSet(\TestSymfonyGuy $I): void
    {
        $queryId = $this->addSearchQuery($I);
        $data = $this->apiLax2Jfk($queryId);
        $this->sendCallbackRequest($I, $data);
        $I->seeResponseCodeIs(200);
        $I->assertTrue($this->logger->hasInfoThatContains('Miles limit not found for cabin "economy"'));
    }

    /**
     * @dataProvider milesLimitProvider
     */
    public function testMilesLimit(\TestSymfonyGuy $I, Example $example): void
    {
        $queryId = $this->addSearchQuery($I, [
            'EconomyMilesLimit' => $example['economyLimit'] ?? null,
            'PremiumEconomyMilesLimit' => $example['premiumEconomyLimit'] ?? null,
            'BusinessMilesLimit' => $example['businessLimit'] ?? null,
            'FirstMilesLimit' => $example['firstLimit'] ?? null,
            'DepartureAirports' => json_encode([$example['route']['segments'][0]['departure']['airport']]),
            'ArrivalAirports' => json_encode([$example['route']['segments'][count($example['route']['segments']) - 1]['arrival']['airport']]),
        ]);
        $data = [
            'response' => [
                $this->apiResponse(
                    $requestId = StringHandler::getRandomCode(10),
                    $queryId,
                    'test',
                    [
                        $example['route'],
                    ],
                ),
            ],
        ];
        $this->sendCallbackRequest($I, $data);
        $I->seeResponseCodeIs(200);

        $limits = $example['expected']['limit'];

        if ($example['expected']['result']) {
            $I->assertTrue(
                $this->logger->hasInfoThatContains(sprintf(
                    'success, cabin: %s, limits: %s, actual: %s',
                    $example['expected']['cabin'],
                    json_encode($limits),
                    $example['expected']['actual']
                ))
            );

            $I->seeInDatabase('RAFlightSearchRoute', [
                'RAFlightSearchQueryID' => $queryId,
                'DepCode' => $example['route']['segments'][0]['departure']['airport'],
                'ArrCode' => $example['route']['segments'][count($example['route']['segments']) - 1]['arrival']['airport'],
                'ApiRequestID' => $requestId,
                'Archived' => 0,
                'Flag' => 0,
                'ItineraryCOS' => $example['expected']['cabin'],
            ]);
        } else {
            $I->assertTrue(
                $this->logger->hasInfoThatContains(sprintf(
                    'miles limit exceeded, cabin: %s, limits: %s, actual: %s',
                    $example['expected']['cabin'],
                    json_encode($limits),
                    $example['expected']['actual']
                ))
            );
            $I->dontSeeInDatabase('RAFlightSearchRoute', [
                'RAFlightSearchQueryID' => $queryId,
            ]);
        }
    }

    /**
     * @dataProvider durationsAndStopsProvider
     */
    public function testDurationsAndStops(\TestSymfonyGuy $I, Example $example)
    {
        $queryId = $this->addSearchQuery($I, [
            'EconomyMilesLimit' => 1000000,
            'MaxTotalDuration' => $example['maxTotalDuration'],
            'MaxSingleLayoverDuration' => $example['maxSingleLayoverDuration'],
            'MaxTotalLayoverDuration' => $example['maxTotalLayoverDuration'],
            'MaxStops' => $example['maxStops'],
            'DepartureAirports' => json_encode([$example['route']['segments'][0]['departure']['airport']]),
            'ArrivalAirports' => json_encode([$example['route']['segments'][count($example['route']['segments']) - 1]['arrival']['airport']]),
        ]);
        $data = [
            'response' => [
                $this->apiResponse(
                    $requestId = StringHandler::getRandomCode(10),
                    $queryId,
                    'test',
                    [
                        $example['route'],
                    ],
                ),
            ],
        ];
        $this->sendCallbackRequest($I, $data);
        $I->seeResponseCodeIs(200);

        if ($example['expected']['result']) {
            $I->assertTrue($this->logger->hasInfoThatContains('success'));

            $I->seeInDatabase('RAFlightSearchRoute', [
                'RAFlightSearchQueryID' => $queryId,
                'DepCode' => $example['route']['segments'][0]['departure']['airport'],
                'ArrCode' => $example['route']['segments'][count($example['route']['segments']) - 1]['arrival']['airport'],
                'ApiRequestID' => $requestId,
                'Archived' => 0,
                'Flag' => 0,
            ]);
        } else {
            $I->assertTrue($this->logger->hasInfoThatContains($example['expected']['log']));
            $I->dontSeeInDatabase('RAFlightSearchRoute', [
                'RAFlightSearchQueryID' => $queryId,
            ]);
        }
    }

    public function testTaxesAndFees(\TestSymfonyGuy $I): void
    {
        $queryId = $this->addSearchQuery($I, [
            'EconomyMilesLimit' => 100000,
        ]);
        $data = $this->apiLax2Jfk($queryId, 10000, '1:30', null, 1, 'test', $this->apiCashCost(
            'USD', 1, 10, 20
        ));
        $this->sendCallbackRequest($I, $data);
        $I->seeResponseCodeIs(200);
        $I->assertTrue($this->logger->hasInfoThatContains('success'));
        $I->seeInDatabase('RAFlightSearchRoute', [
            'RAFlightSearchQueryID' => $queryId,
            'DepCode' => 'LAX',
            'ArrCode' => 'JFK',
            'ApiRequestID' => $data['response'][0]['requestId'],
            'Taxes' => 10,
            'Fees' => 20,
        ]);
        $I->assertEquals(1, $I->grabNumRecords('RAFlightSearchRoute', ['RAFlightSearchQueryID' => $queryId]));

        $this->clearLogs();
        $data = $this->apiLax2Jfk($queryId, 10000, '1:30', null, 1, 'test', $this->apiCashCost(
            'USD', 1, 30, 0
        ));
        $this->sendCallbackRequest($I, $data);
        $I->seeResponseCodeIs(200);
        $I->assertTrue($this->logger->hasInfoThatContains('success'));
        $I->seeInDatabase('RAFlightSearchRoute', [
            'RAFlightSearchQueryID' => $queryId,
            'DepCode' => 'LAX',
            'ArrCode' => 'JFK',
            'ApiRequestID' => $data['response'][0]['requestId'],
            'Taxes' => 30,
            'Fees' => 20,
        ]);
        $I->assertEquals(1, $I->grabNumRecords('RAFlightSearchRoute', ['RAFlightSearchQueryID' => $queryId]));
    }

    protected function invalidDepartureDateProvider(): array
    {
        return [
            'earlier date' => ['+2 day'],
            'later date' => ['+6 day'],
        ];
    }

    protected function milesLimitProvider(): array
    {
        return [
            // economy, 1 segment, economy limit
            [
                'route' => $this->apiCustomRoute(
                    [
                        [
                            'dep' => ['LAX', '+3 days 00:00:00'],
                            'arr' => ['JFK', '+3 days 01:30:00'],
                            'duration' => '1:30',
                            'cabin' => 'economy',
                        ],
                    ],
                    35000,
                    '1:30',
                    1
                ),
                'economyLimit' => 40000,
                'expected' => [
                    'result' => true,
                    'cabin' => 'economy',
                    'limit' => ['economy' => 40000],
                    'actual' => 35000,
                ],
            ],

            // business, 1 segment, economy limit
            [
                'route' => $this->apiCustomRoute(
                    [
                        [
                            'dep' => ['LAX', '+3 days 00:00:00'],
                            'arr' => ['JFK', '+3 days 01:30:00'],
                            'duration' => '1:30',
                            'cabin' => 'business',
                        ],
                    ],
                    49000,
                    '1:30',
                    1
                ),
                'economyLimit' => 50000,
                'expected' => [
                    'result' => true,
                    'cabin' => 'business',
                    'limit' => ['economy' => 50000],
                    'actual' => 49000,
                ],
            ],

            // firstClass, 1 segment, economy limit
            [
                'route' => $this->apiCustomRoute(
                    [
                        [
                            'dep' => ['LAX', '+3 days 00:00:00'],
                            'arr' => ['JFK', '+3 days 01:30:00'],
                            'duration' => '1:30',
                            'cabin' => 'firstClass',
                        ],
                    ],
                    49000,
                    '1:30',
                    1
                ),
                'economyLimit' => 50000,
                'expected' => [
                    'result' => true,
                    'cabin' => 'firstClass',
                    'limit' => ['economy' => 50000],
                    'actual' => 49000,
                ],
            ],

            // firstClass, 1 segment, premiumEconomy limit
            [
                'route' => $this->apiCustomRoute(
                    [
                        [
                            'dep' => ['LAX', '+3 days 00:00:00'],
                            'arr' => ['JFK', '+3 days 01:30:00'],
                            'duration' => '1:30',
                            'cabin' => 'firstClass',
                        ],
                    ],
                    49000,
                    '1:30',
                    1
                ),
                'premiumEconomyLimit' => 50000,
                'expected' => [
                    'result' => true,
                    'cabin' => 'firstClass',
                    'limit' => ['premiumEconomy' => 50000],
                    'actual' => 49000,
                ],
            ],

            // firstClass, 1 segment, premiumEconomy and business limits
            [
                'route' => $this->apiCustomRoute(
                    [
                        [
                            'dep' => ['LAX', '+3 days 00:00:00'],
                            'arr' => ['JFK', '+3 days 01:30:00'],
                            'duration' => '1:30',
                            'cabin' => 'firstClass',
                        ],
                    ],
                    59000,
                    '1:30',
                    1
                ),
                'premiumEconomyLimit' => 60000,
                'businessLimit' => 50000,
                'expected' => [
                    'result' => true,
                    'cabin' => 'firstClass',
                    'limit' => ['business' => 50000, 'premiumEconomy' => 60000],
                    'actual' => 59000,
                ],
            ],

            // firstClass, 1 segment, premiumEconomy and business limits, exceeded
            [
                'route' => $this->apiCustomRoute(
                    [
                        [
                            'dep' => ['LAX', '+3 days 00:00:00'],
                            'arr' => ['JFK', '+3 days 01:30:00'],
                            'duration' => '1:30',
                            'cabin' => 'firstClass',
                        ],
                    ],
                    69000,
                    '1:30',
                    1
                ),
                'premiumEconomyLimit' => 60000,
                'businessLimit' => 50000,
                'expected' => [
                    'result' => false,
                    'cabin' => 'firstClass',
                    'limit' => ['business' => 50000, 'premiumEconomy' => 60000],
                    'actual' => 69000,
                ],
            ],

            // economy (80% time) + business (20%), 2 segments
            [
                'route' => $this->apiCustomRoute(
                    [
                        [
                            'dep' => ['PEE', '+3 days 00:00:00'],
                            'arr' => ['DME', '+3 days 01:30:00'],
                            'duration' => '2:00',
                            'cabin' => 'business',
                        ],
                        [
                            'dep' => ['DME', '+3 days 02:30:00'],
                            'arr' => ['JFK', '+3 days 05:00:00'],
                            'duration' => '8:00',
                            'cabin' => 'economy',
                        ],
                    ],
                    35000,
                    '10:00',
                    1
                ),
                'economyLimit' => 40000,
                'businessLimit' => 50000,
                'expected' => [
                    'result' => true,
                    'cabin' => 'economy',
                    'limit' => ['economy' => 40000],
                    'actual' => 35000,
                ],
            ],

            // economy (80% time) + business (20%), 2 segments, limit exceeded
            [
                'route' => $this->apiCustomRoute(
                    [
                        [
                            'dep' => ['PEE', '+3 days 00:00:00'],
                            'arr' => ['DME', '+3 days 01:30:00'],
                            'duration' => '2:00',
                            'cabin' => 'business',
                        ],
                        [
                            'dep' => ['DME', '+3 days 02:30:00'],
                            'arr' => ['JFK', '+3 days 05:00:00'],
                            'duration' => '8:00',
                            'cabin' => 'economy',
                        ],
                    ],
                    35000,
                    '10:00',
                    1
                ),
                'economyLimit' => 30000,
                'businessLimit' => 50000,
                'expected' => [
                    'result' => false,
                    'cabin' => 'economy',
                    'limit' => ['economy' => 30000],
                    'actual' => 35000,
                ],
            ],

            // business (80% time) + economy (20%), 2 segments
            [
                'route' => $this->apiCustomRoute(
                    [
                        [
                            'dep' => ['PEE', '+3 days 00:00:00'],
                            'arr' => ['DME', '+3 days 01:30:00'],
                            'duration' => '2:00',
                            'cabin' => 'economy',
                        ],
                        [
                            'dep' => ['DME', '+3 days 02:30:00'],
                            'arr' => ['JFK', '+3 days 05:00:00'],
                            'duration' => '8:00',
                            'cabin' => 'business',
                        ],
                    ],
                    45000,
                    '10:00',
                    1
                ),
                'economyLimit' => 30000,
                'businessLimit' => 50000,
                'expected' => [
                    'result' => true,
                    'cabin' => 'business',
                    'limit' => ['business' => 50000, 'economy' => 30000],
                    'actual' => 45000,
                ],
            ],

            // business (80% time) + economy (20%), 2 segments, limit exceeded
            [
                'route' => $this->apiCustomRoute(
                    [
                        [
                            'dep' => ['PEE', '+3 days 00:00:00'],
                            'arr' => ['DME', '+3 days 01:30:00'],
                            'duration' => '2:00',
                            'cabin' => 'economy',
                        ],
                        [
                            'dep' => ['DME', '+3 days 02:30:00'],
                            'arr' => ['JFK', '+3 days 05:00:00'],
                            'duration' => '8:00',
                            'cabin' => 'business',
                        ],
                    ],
                    55000,
                    '10:00',
                    1
                ),
                'economyLimit' => 30000,
                'businessLimit' => 50000,
                'expected' => [
                    'result' => false,
                    'cabin' => 'business',
                    'limit' => ['business' => 50000, 'economy' => 30000],
                    'actual' => 55000,
                ],
            ],

            // first class (80% time) + economy (20%), 2 segments
            [
                'route' => $this->apiCustomRoute(
                    [
                        [
                            'dep' => ['PEE', '+3 days 00:00:00'],
                            'arr' => ['DME', '+3 days 01:30:00'],
                            'duration' => '2:00',
                            'cabin' => 'economy',
                        ],
                        [
                            'dep' => ['DME', '+3 days 02:30:00'],
                            'arr' => ['JFK', '+3 days 05:00:00'],
                            'duration' => '8:00',
                            'cabin' => 'firstClass',
                        ],
                    ],
                    60000,
                    '10:00',
                    1
                ),
                'economyLimit' => 30000,
                'businessLimit' => 60000,
                'expected' => [
                    'result' => true,
                    'cabin' => 'firstClass',
                    'limit' => ['business' => 60000, 'economy' => 30000],
                    'actual' => 60000,
                ],
            ],

            // first class (80% time) + economy (20%), 2 segments, limit exceeded
            [
                'route' => $this->apiCustomRoute(
                    [
                        [
                            'dep' => ['PEE', '+3 days 00:00:00'],
                            'arr' => ['DME', '+3 days 01:30:00'],
                            'duration' => '2:00',
                            'cabin' => 'economy',
                        ],
                        [
                            'dep' => ['DME', '+3 days 02:30:00'],
                            'arr' => ['JFK', '+3 days 05:00:00'],
                            'duration' => '8:00',
                            'cabin' => 'firstClass',
                        ],
                    ],
                    70000,
                    '10:00',
                    1
                ),
                'economyLimit' => 30000,
                'businessLimit' => 60000,
                'expected' => [
                    'result' => false,
                    'cabin' => 'firstClass',
                    'limit' => ['business' => 60000, 'economy' => 30000],
                    'actual' => 70000,
                ],
            ],

            // BOS-JFK (1hr) - Economy (11% of flight time)
            // JFK-LHR (8hr) - Business (89% of flight time)
            [
                'route' => $this->apiCustomRoute(
                    [
                        [
                            'dep' => ['BOS', '+3 days 00:00:00'],
                            'arr' => ['JFK', '+3 days 01:00:00'],
                            'duration' => '1:00',
                            'cabin' => 'economy',
                        ],
                        [
                            'dep' => ['JFK', '+3 days 02:00:00'],
                            'arr' => ['LHR', '+3 days 10:00:00'],
                            'duration' => '8:00',
                            'cabin' => 'business',
                        ],
                    ],
                    49000,
                    '9:00',
                    1
                ),
                'economyLimit' => 20000,
                'businessLimit' => 80000,
                'expected' => [
                    'result' => true,
                    'cabin' => 'business',
                    'limit' => ['business' => 80000, 'economy' => 20000],
                    'actual' => 49000,
                ],
            ],

            // PremiumEconomy Miles Limit = 60,000
            // Business Miles Limit = 80,000
            // 4 flight segments:
            // Flight 1 - 2h Economy Class
            // Flight 2 - 1h12m Premium Economy
            // Flight 3 - 4h40m Business
            // Flight 4 - 24m First
            [
                'route' => $this->apiCustomRoute(
                    [
                        [
                            'dep' => ['BOS', '+3 days 00:00:00'],
                            'arr' => ['JFK', '+3 days 02:00:00'],
                            'duration' => '2:00',
                            'cabin' => 'economy',
                        ],
                        [
                            'dep' => ['JFK', '+3 days 03:12:00'],
                            'arr' => ['LHR', '+3 days 04:24:00'],
                            'duration' => '1:12',
                            'cabin' => 'premiumEconomy',
                        ],
                        [
                            'dep' => ['LHR', '+3 days 05:04:00'],
                            'arr' => ['DME', '+3 days 09:44:00'],
                            'duration' => '4:40',
                            'cabin' => 'business',
                        ],
                        [
                            'dep' => ['DME', '+3 days 10:08:00'],
                            'arr' => ['PEE', '+3 days 10:32:00'],
                            'duration' => '0:24',
                            'cabin' => 'firstClass',
                        ],
                    ],
                    49000,
                    '8:28',
                    1
                ),
                'premiumEconomyLimit' => 60000,
                'businessLimit' => 80000,
                'expected' => [
                    'result' => true,
                    'cabin' => 'premiumEconomy',
                    'limit' => ['premiumEconomy' => 60000],
                    'actual' => 49000,
                ],
            ],
        ];
    }

    protected function durationsAndStopsProvider(): array
    {
        $seconds2String = function (int $seconds): string {
            return sprintf('%d:%02d', $seconds / 3600, $seconds / 60 % 60);
        };
        $route = function (array $segments) use ($seconds2String): array {
            $totalFlightDurationSeconds = array_sum(array_map(function (array $segment) {
                return Duration::parseSeconds($segment['duration']) ?? 0;
            }, $segments));
            $totalLayoverDurationSeconds = array_sum(array_map(function (array $segment) {
                return Duration::parseSeconds($segment['layoverDuration'] ?? '') ?? 0;
            }, $segments));

            return $this->apiRoute(
                array_map(function (array $segment) {
                    return $this->apiSegment(
                        $this->apiAirport($segment['dep'][0], new \DateTime($segment['dep'][1])),
                        $this->apiAirport($segment['arr'][0], new \DateTime($segment['arr'][1])),
                        $this->apiTimes($segment['duration'], $segment['layoverDuration'] ?? null),
                        'Meal',
                        RAFlightSearchQuery::API_FLIGHT_CLASS_ECONOMY,
                        'Fare class',
                        ['123'],
                        'AA',
                        'Aircraft'
                    );
                }, $segments),
                $this->apiMileCost(10000, 'Test program'),
                $this->apiCashCost('USD', 1, 10, 20),
                $this->apiTimes(
                    $seconds2String($totalFlightDurationSeconds),
                    $seconds2String($totalLayoverDurationSeconds)
                ),
                1,
                count($segments) - 1,
            );
        };

        return [
            [
                'route' => $route(
                    [
                        [
                            'dep' => ['LAX', '+3 days 00:00:00'],
                            'arr' => ['JFK', '+3 days 01:30:00'],
                            'duration' => '1:30',
                        ],
                    ],
                ),
                'maxTotalDuration' => 1.4,
                'maxSingleLayoverDuration' => null,
                'maxTotalLayoverDuration' => null,
                'maxStops' => null,
                'expected' => [
                    'result' => false,
                    'log' => 'Total duration exceeds the limit',
                ],
            ],
            [
                'route' => $route(
                    [
                        [
                            'dep' => ['LAX', '+3 days 00:00:00'],
                            'arr' => ['JFK', '+3 days 01:30:00'],
                            'duration' => '1:30',
                        ],
                    ],
                ),
                'maxTotalDuration' => 1.5,
                'maxSingleLayoverDuration' => null,
                'maxTotalLayoverDuration' => null,
                'maxStops' => null,
                'expected' => [
                    'result' => true,
                ],
            ],
            [
                'route' => $route(
                    [
                        [
                            'dep' => ['LAX', '+3 days 00:00:00'],
                            'arr' => ['JFK', '+3 days 01:30:00'],
                            'duration' => '1:30',
                        ],
                    ],
                ),
                'maxTotalDuration' => 2,
                'maxSingleLayoverDuration' => null,
                'maxTotalLayoverDuration' => null,
                'maxStops' => null,
                'expected' => [
                    'result' => true,
                ],
            ],
            [
                'route' => $route(
                    [
                        [
                            'dep' => ['LAX', '+3 days 00:00:00'],
                            'arr' => ['JFK', '+3 days 01:30:00'],
                            'duration' => '1:30',
                            'layoverDuration' => '1:00',
                        ],
                        [
                            'dep' => ['JFK', '+4 days 01:30:00'],
                            'arr' => ['LAX', '+4 days 02:30:00'],
                            'duration' => '1:30',
                        ],
                    ],
                ),
                'maxTotalDuration' => 3.9,
                'maxSingleLayoverDuration' => null,
                'maxTotalLayoverDuration' => null,
                'maxStops' => null,
                'expected' => [
                    'result' => false,
                    'log' => 'Total duration exceeds the limit',
                ],
            ],
            [
                'route' => $route(
                    [
                        [
                            'dep' => ['LAX', '+3 days 00:00:00'],
                            'arr' => ['JFK', '+3 days 01:30:00'],
                            'duration' => '1:30',
                            'layoverDuration' => '1:00',
                        ],
                        [
                            'dep' => ['JFK', '+4 days 01:30:00'],
                            'arr' => ['LAX', '+4 days 02:30:00'],
                            'duration' => '1:30',
                        ],
                    ],
                ),
                'maxTotalDuration' => 4,
                'maxSingleLayoverDuration' => null,
                'maxTotalLayoverDuration' => null,
                'maxStops' => null,
                'expected' => [
                    'result' => true,
                ],
            ],
            [
                'route' => $route(
                    [
                        [
                            'dep' => ['LAX', '+3 days 00:00:00'],
                            'arr' => ['JFK', '+3 days 01:30:00'],
                            'duration' => '1:30',
                            'layoverDuration' => '1:00',
                        ],
                        [
                            'dep' => ['JFK', '+4 days 01:30:00'],
                            'arr' => ['LAX', '+4 days 02:30:00'],
                            'duration' => '1:30',
                        ],
                    ],
                ),
                'maxTotalDuration' => 4,
                'maxSingleLayoverDuration' => 0.5,
                'maxTotalLayoverDuration' => null,
                'maxStops' => null,
                'expected' => [
                    'result' => false,
                    'log' => 'Single layover duration exceeds the limit',
                ],
            ],
            [
                'route' => $route(
                    [
                        [
                            'dep' => ['LAX', '+3 days 00:00:00'],
                            'arr' => ['JFK', '+3 days 01:30:00'],
                            'duration' => '1:30',
                            'layoverDuration' => '1:00',
                        ],
                        [
                            'dep' => ['JFK', '+4 days 01:30:00'],
                            'arr' => ['LAX', '+4 days 02:30:00'],
                            'duration' => '1:30',
                        ],
                    ],
                ),
                'maxTotalDuration' => 4,
                'maxSingleLayoverDuration' => 1,
                'maxTotalLayoverDuration' => null,
                'maxStops' => null,
                'expected' => [
                    'result' => true,
                ],
            ],
            [
                'route' => $route(
                    [
                        [
                            'dep' => ['LAX', '+3 days 00:00:00'],
                            'arr' => ['JFK', '+3 days 01:30:00'],
                            'duration' => '1:30',
                            'layoverDuration' => '1:00',
                        ],
                        [
                            'dep' => ['JFK', '+4 days 01:30:00'],
                            'arr' => ['LAX', '+4 days 02:30:00'],
                            'duration' => '1:30',
                            'layoverDuration' => '2:00',
                        ],
                        [
                            'dep' => ['LAX', '+5 days 00:00:00'],
                            'arr' => ['JFK', '+5 days 01:30:00'],
                            'duration' => '1:30',
                        ],
                    ],
                ),
                'maxTotalDuration' => 7.5,
                'maxSingleLayoverDuration' => 2,
                'maxTotalLayoverDuration' => 2,
                'maxStops' => null,
                'expected' => [
                    'result' => false,
                    'log' => 'Total layover duration exceeds the limit',
                ],
            ],
            [
                'route' => $route(
                    [
                        [
                            'dep' => ['LAX', '+3 days 00:00:00'],
                            'arr' => ['JFK', '+3 days 01:30:00'],
                            'duration' => '1:30',
                            'layoverDuration' => '1:00',
                        ],
                        [
                            'dep' => ['JFK', '+4 days 01:30:00'],
                            'arr' => ['LAX', '+4 days 02:30:00'],
                            'duration' => '1:30',
                            'layoverDuration' => '2:00',
                        ],
                        [
                            'dep' => ['LAX', '+5 days 00:00:00'],
                            'arr' => ['JFK', '+5 days 01:30:00'],
                            'duration' => '1:30',
                        ],
                    ],
                ),
                'maxTotalDuration' => 7.5,
                'maxSingleLayoverDuration' => 2,
                'maxTotalLayoverDuration' => 3,
                'maxStops' => null,
                'expected' => [
                    'result' => true,
                ],
            ],
            [
                'route' => $route(
                    [
                        [
                            'dep' => ['LAX', '+3 days 00:00:00'],
                            'arr' => ['JFK', '+3 days 01:30:00'],
                            'duration' => '1:30',
                            'layoverDuration' => '1:00',
                        ],
                        [
                            'dep' => ['JFK', '+4 days 01:30:00'],
                            'arr' => ['LAX', '+4 days 02:30:00'],
                            'duration' => '1:30',
                            'layoverDuration' => '2:00',
                        ],
                        [
                            'dep' => ['LAX', '+5 days 00:00:00'],
                            'arr' => ['JFK', '+5 days 01:30:00'],
                            'duration' => '1:30',
                        ],
                    ],
                ),
                'maxTotalDuration' => 7.5,
                'maxSingleLayoverDuration' => 2,
                'maxTotalLayoverDuration' => 3,
                'maxStops' => 1,
                'expected' => [
                    'result' => false,
                    'log' => 'Count of stops exceeds the limit',
                ],
            ],
            [
                'route' => $route(
                    [
                        [
                            'dep' => ['LAX', '+3 days 00:00:00'],
                            'arr' => ['JFK', '+3 days 01:30:00'],
                            'duration' => '1:30',
                            'layoverDuration' => '1:00',
                        ],
                        [
                            'dep' => ['JFK', '+4 days 01:30:00'],
                            'arr' => ['LAX', '+4 days 02:30:00'],
                            'duration' => '1:30',
                            'layoverDuration' => '2:00',
                        ],
                        [
                            'dep' => ['LAX', '+5 days 00:00:00'],
                            'arr' => ['JFK', '+5 days 01:30:00'],
                            'duration' => '1:30',
                        ],
                    ],
                ),
                'maxTotalDuration' => 7.5,
                'maxSingleLayoverDuration' => 2,
                'maxTotalLayoverDuration' => 3,
                'maxStops' => 2,
                'expected' => [
                    'result' => true,
                ],
            ],
        ];
    }
}
