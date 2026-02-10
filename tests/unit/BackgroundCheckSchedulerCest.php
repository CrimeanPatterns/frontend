<?php

namespace AwardWallet\Tests\Unit;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\BackgroundCheckScheduler;
use Codeception\Example;
use Codeception\Module\Aw;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @group frontend-unit
 */
class BackgroundCheckSchedulerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    /**
     * @var BackgroundCheckScheduler
     */
    private $scheduler;

    /**
     * @var int
     */
    private $userId;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->userId = $I->createAwUser(null, null, ["AccountLevel" => ACCOUNT_LEVEL_AWPLUS], true);
        $this->scheduler = $I->grabService(BackgroundCheckScheduler::class);
    }

    public function _after(\TestSymfonyGuy $I)
    {
        unset($this->scheduler);
    }

    public function testNearestTripDate(\TestSymfonyGuy $I)
    {
        $accountId = $I->createAwAccount($this->userId, "testprovider", "trip.passengers", null, ["ExpirationAutoSet" => EXPIRATION_UNKNOWN]);
        $startTs = time();
        $I->checkAccount($accountId);
        $accountNextCheckData = $this->scheduler->getAccountNextCheck($accountId);
        $I->assertEquals(ACTIVITY_SCORE_ON_NEAREST_TRIP_DATE, $accountNextCheckData['ActivityScore']);
        $previousCheckDate = $accountNextCheckData['NextCheckDate'];

        // free user
        $I->updateInDatabase('Usr', [
            'AccountLevel' => ACCOUNT_LEVEL_FREE,
        ], ['UserID' => $this->userId]);
        $I->grabService('doctrine.orm.entity_manager')->clear();
        $accountNextCheckData = $this->scheduler->getAccountNextCheck($accountId);

        $I->assertTrue(
            ($accountNextCheckData['NextCheckDate'] - $previousCheckDate) > 3600 * 24 * 80,
            sprintf(
                'NextCheckDate should be more than 80 days from previous check date. %s - %s',
                date('Y-m-d H:i:s', $accountNextCheckData['NextCheckDate']),
                date('Y-m-d H:i:s', $previousCheckDate)
            )
        );
        $I->assertTrue(
            ($accountNextCheckData['NextCheckDate'] - $startTs) > 3600 * 24 * 89,
            sprintf(
                'NextCheckDate should be more than 89 days from previous check date. %s - %s',
                date('Y-m-d H:i:s', $accountNextCheckData['NextCheckDate']),
                date('Y-m-d H:i:s', $startTs)
            )
        );
    }

    public function testBalanceChanges(\TestSymfonyGuy $I)
    {
        $tripAndRes = $this->tripAndRes();
        $providerId = $I->createAwProvider(null, null, [], [
            "ParseItineraries" => function () use ($tripAndRes) { return $tripAndRes; },
            "Parse" => function () { $this->SetBalance(rand(1, 1000000)); },
        ]);
        $accountId = $I->createAwAccount($this->userId, $providerId, "nomatter", null, ["ExpirationAutoSet" => EXPIRATION_UNKNOWN]);
        // change balance
        $this->changeBalance($I, $accountId, 3);
        $accountNextCheckData = $this->scheduler->getAccountNextCheck($accountId);
        $I->assertEquals(7 * 24, $accountNextCheckData['ActivityScore']);
        $this->changeBalance($I, $accountId, 5);
        $accountNextCheckData = $this->scheduler->getAccountNextCheck($accountId);
        $I->assertEquals(24, $accountNextCheckData['ActivityScore']);
        $this->changeBalance($I, $accountId, 7);
        $accountNextCheckData = $this->scheduler->getAccountNextCheck($accountId);
        $I->assertEquals(12, $accountNextCheckData['ActivityScore']);
    }

    public function testUnknownAccount(\TestSymfonyGuy $I)
    {
        $accountNextCheckData = $this->scheduler->getAccountNextCheck(-123);
        $I->assertEquals(32 * 24, $accountNextCheckData['ActivityScore']);
    }

    public function testDroppedToZeroAccount(\TestSymfonyGuy $I)
    {
        $balance = 3;
        $providerId = $I->createAwProvider(null, null, [], [
            "Parse" => function () use (&$balance) {
                $this->SetBalance($balance);
                $balance--;
            },
        ]);
        $accountId = $I->createAwAccount($this->userId, $providerId, "nomatter", null, ["ExpirationAutoSet" => EXPIRATION_UNKNOWN]);
        $this->changeBalance($I, $accountId, 3);
        // drop to zero
        $I->checkAccount($accountId);
        $accountNextCheckData = $this->scheduler->getAccountNextCheck($accountId);
        $I->assertEquals(6, $accountNextCheckData['ActivityScore']);
    }

    public function testOrdinaryAccount(\TestSymfonyGuy $I)
    {
        $accountId = $I->createAwAccount($this->userId, "testprovider", "balance.random", null, ["ExpirationAutoSet" => EXPIRATION_UNKNOWN, "CreationDate" => date("Y-m-d", time() - SECONDS_PER_DAY * 365)]);
        $I->checkAccount($accountId);
        $accountNextCheckData = $this->scheduler->getAccountNextCheck($accountId);
        $I->assertEquals(31 * 24, $accountNextCheckData['ActivityScore']);
        $I->assertEquals(1, $I->grabFromDatabase("Account", "BackgroundCheck", ["AccountID" => $accountId]));
    }

    public function testDisableBackgroundUpdating(\TestSymfonyGuy $I)
    {
        $accountId = $I->createAwAccount($this->userId, "testprovider", "balance.random", null, ["ExpirationAutoSet" => EXPIRATION_UNKNOWN, "CreationDate" => date("Y-m-d", time() - SECONDS_PER_DAY * 365), "DisableBackgroundUpdating" => 1]);
        $I->checkAccount($accountId);
        $accountNextCheckData = $this->scheduler->getAccountNextCheck($accountId);
        $I->assertEquals(31 * 24, $accountNextCheckData['ActivityScore']);
        $I->assertEquals(0, $I->grabFromDatabase("Account", "BackgroundCheck", ["AccountID" => $accountId]));
    }

    /**
     * @example { "expiresInDays": 2, "checkAfterDays": 2 }
     * @example { "expiresInDays": 5, "checkAfterDays": 2 }
     * @example { "expiresInDays": 9, "checkAfterDays": -8 }
     * @example { "expiresInDays": 65, "checkAfterDays": -61 }
     * @example { "expiresInDays": 95, "checkAfterDays": -91 }
     */
    public function testExpiration(\TestSymfonyGuy $I, Example $example)
    {
        $accountId = $I->createAwAccount($this->userId, "testprovider", "expiration.close", "+{$example["expiresInDays"]} days", ["ExpirationAutoSet" => EXPIRATION_UNKNOWN]);
        $I->checkAccount($accountId);
        $accountNextCheckData = $this->scheduler->getAccountNextCheck($accountId);
        $I->assertEquals(ACTIVITY_SCORE_ON_EXPIRATION_DATE, $accountNextCheckData['ActivityScore']);
        $fields = $I->query("select ExpirationDate, QueueDate from Account where AccountID = $accountId")->fetch(\PDO::FETCH_ASSOC);
        $I->assertEquals($example["checkAfterDays"], round((strtotime($fields['QueueDate']) - strtotime($fields['ExpirationDate'])) / SECONDS_PER_DAY));
    }

    public function testErrorCount(\TestSymfonyGuy $I)
    {
        // this check is to trigger error code change, and reser ErrorCount
        $accountId = $I->createAwAccount($this->userId, "testprovider", "invalid.logon", null, ["ExpirationAutoSet" => EXPIRATION_UNKNOWN]);
        $I->checkAccount($accountId);

        // two consequent errors - ensure ErrorCount increased
        $I->checkAccount($accountId);
        $I->assertEquals(1, $I->grabFromDatabase("Account", "ErrorCount", ["AccountID" => $accountId]));
        $I->checkAccount($accountId);
        $I->assertEquals(2, $I->grabFromDatabase("Account", "ErrorCount", ["AccountID" => $accountId]));

        // check ErrorCount reset on successful check
        $I->executeQuery("update Account set Login = 'balance.random' where AccountID = {$accountId}");
        $I->checkAccount($accountId);
        $I->assertEquals(0, $I->grabFromDatabase("Account", "ErrorCount", ["AccountID" => $accountId]));
    }

    public function testInvalid(\TestSymfonyGuy $I)
    {
        $accountId = $I->createAwAccount($this->userId, "testprovider", "invalid.logon", null, ["ExpirationAutoSet" => EXPIRATION_UNKNOWN, "CreationDate" => date("Y-m-d", time() - SECONDS_PER_DAY * 365)]);
        $I->checkAccount($accountId);
        $I->assertEquals(7 * 24, $this->scheduler->getAccountNextCheck($accountId)['ActivityScore']);

        for ($n = 1; $n < 5; $n++) {
            $I->executeQuery("update Account set ErrorCount = $n where AccountID = {$accountId}");
            $I->assertEquals(31 * 24, $this->scheduler->getAccountNextCheck($accountId)['ActivityScore'], "iteration $n");
        }

        for (; $n < 10; $n++) {
            $I->executeQuery("update Account set ErrorCount = $n where AccountID = {$accountId}");
            $I->assertEquals(90 * 24, $this->scheduler->getAccountNextCheck($accountId)['ActivityScore'], "iteration $n");
        }

        for (; $n < 13; $n++) {
            $I->executeQuery("update Account set ErrorCount = $n where AccountID = {$accountId}");
            $I->assertEquals(3 * 365 * 24, $this->scheduler->getAccountNextCheck($accountId)['ActivityScore'], "iteration $n");
        }
    }

    public function testLockout(\TestSymfonyGuy $I)
    {
        $accountId = $I->createAwAccount($this->userId, "testprovider", "lockout", null, ["ExpirationAutoSet" => EXPIRATION_UNKNOWN, "CreationDate" => date("Y-m-d", time() - SECONDS_PER_DAY * 365)]);
        $I->checkAccount($accountId);
        $I->assertEquals(60 * 24, $this->scheduler->getAccountNextCheck($accountId)['ActivityScore']);
    }

    public function testProviderError(\TestSymfonyGuy $I)
    {
        $accountId = $I->createAwAccount($this->userId, "testprovider", "provider.error", null, ["ExpirationAutoSet" => EXPIRATION_UNKNOWN, "CreationDate" => date("Y-m-d", time() - SECONDS_PER_DAY * 365)]);
        $I->checkAccount($accountId);
        $I->assertEquals(2 * 24, $this->scheduler->getAccountNextCheck($accountId)['ActivityScore']);
    }

    public function testTripDelay(\TestSymfonyGuy $I)
    {
        $providerId = $I->createAwProvider(null, null, [], [
            "ParseItineraries" => function () {
                return [
                    [
                        'Kind' => 'T',
                        'RecordLocator' => 'TEST001',
                        'Passengers' => 'John Smith',
                        'TotalCharge' => 100,
                        'Tax' => 7,
                        'Currency' => 'USD',
                        'ReservationDate' => strtotime("2013-08-01"),
                        'TripSegments' => [
                            [
                                'AirlineName' => 'Beta Alpha Test Airlines',
                                'Duration' => '2:00',
                                'DepDate' => strtotime("2037-08-01 7:00"),
                                'DepCode' => 'LGA',
                                'DepName' => 'JF Kennedy Airport',
                                'ArrDate' => strtotime("2037-08-01 11:00"),
                                'ArrCode' => 'JFK',
                                'ArrName' => 'Los Angeles International Airport',
                                'FlightNumber' => 'TE223',
                                'Seats' => '23',
                            ],
                        ],
                    ],
                ];
            },
        ]);
        $accountId = $I->createAwAccount($this->userId, $providerId, "nomatter", null, ["ExpirationAutoSet" => EXPIRATION_UNKNOWN]);
        $I->checkAccount($accountId);
        $tripId = $I->grabFromDatabase("Trip", "TripID", ["AccountID" => $accountId]);
        $tzLocation = $I->query("select TimeZoneLocation from GeoTag where Address = 'LGA'")->fetchColumn();
        $tz = new \DateTimeZone($tzLocation);
        $offset = $tz->getOffset(new \DateTime());
        $I->executeQuery("update Account set UpdateDate = '2000-01-01' where AccountID = $accountId");

        $depDate = strtotime("-30 minute");
        $I->executeQuery("update TripSegment set ScheduledDepDate = '" . date("Y-m-d H:i", $depDate + $offset) . "', DepDate = '" . date("Y-m-d H:i", strtotime("+1 hour", $depDate + $offset)) . "' where TripID = $tripId");
        $this->scheduler->schedule($accountId);
        $I->assertEquals(date("Y-m-d H:i:\\00", strtotime("+1 hour", $depDate)), $I->grabFromDatabase("Account", "QueueDate", ["AccountID" => $accountId]));

        $updateDate = time();
        $I->executeQuery("update Account set UpdateDate = '" . date("Y-m-d H:i", $updateDate) . "' where AccountID = $accountId");
        $this->scheduler->schedule($accountId);
        $I->assertEquals(date("Y-m-d H:i:\\00", strtotime("+1 hour", $updateDate)), $I->grabFromDatabase("Account", "QueueDate", ["AccountID" => $accountId]));

        $depDate = strtotime("-90 minute");
        $I->executeQuery("update TripSegment set ScheduledDepDate = '" . date("Y-m-d H:i", $depDate + $offset) . "', DepDate = '" . date("Y-m-d H:i", strtotime("+1 hour", $depDate + $offset)) . "' where TripID = $tripId");
        $this->scheduler->schedule($accountId);
        $I->assertEquals(168, round((strtotime($I->grabFromDatabase("Account", "QueueDate", ["AccountID" => $accountId])) - time()) / 3600));

        $depDate = strtotime("+30 minute");
        $I->executeQuery("update TripSegment set ScheduledDepDate = '" . date("Y-m-d H:i", $depDate + $offset) . "', DepDate = '" . date("Y-m-d H:i", strtotime("+1 hour", $depDate + $offset)) . "' where TripID = $tripId");
        $this->scheduler->schedule($accountId);
        $I->assertEquals(date("Y-m-d H:i:\\00", strtotime("+1 hour", $depDate)), $I->grabFromDatabase("Account", "QueueDate", ["AccountID" => $accountId]));
    }

    /**
     * @example { "tripArrDate": -2, "expectedCheckInDays": 1 }
     * @example { "tripArrDate": -4, "expectedCheckInDays": 1 }
     * @example { "tripArrDate": -6, "expectedCheckInDays": 7 }
     * @example { "tripArrDate": -1, "expectedCheckInDays": 2 }
     * @example { "tripArrDate": 0, "expectedCheckInDays": 3 }
     * @example { "tripArrDate": 2, "expectedCheckInDays": 2 }
     */
    public function testUpdateQantasAfterTrip(\TestSymfonyGuy $I, Example $example)
    {
        $accountId = $I->createAwAccount($this->userId, 33, "nomatter", null);
        $tripId = $I->haveInDatabase("Trip", [
            "AccountID" => $accountId,
            "UserID" => $this->userId,
            "ProviderID" => 33,
        ]);
        $now = time();
        $tripDate = strtotime(date("Y-m-d", strtotime("{$example["tripArrDate"]} day", $now)));
        $gmtGeoTagId = $I->grabFromDatabase("GeoTag", "GeoTagID", ["TimeZoneLocation" => 'UTC']);
        $I->haveInDatabase("TripSegment", [
            "TripID" => $tripId,
            "DepDate" => date("Y-m-d", $tripDate) . " 9:00",
            "ScheduledDepDate" => date("Y-m-d", $tripDate) . " 9:00",
            "ArrDate" => date("Y-m-d", $tripDate) . " 11:00",
            "ScheduledArrDate" => date("Y-m-d", $tripDate) . " 11:00",
            "DepCode" => Aw::GMT_AIRPORT,
            "DepName" => "Gmt1",
            "DepGeoTagID" => $gmtGeoTagId,
            "ArrCode" => Aw::GMT_AIRPORT_2,
            "ArrName" => "Gmt2",
            "ArrGeoTagID" => $gmtGeoTagId,
        ]);
        $this->scheduler->schedule($accountId);
        $I->assertEquals(date("Y-m-d", strtotime("+{$example["expectedCheckInDays"]} day", $now)), date("Y-m-d", strtotime($I->grabFromDatabase("Account", "QueueDate", ["AccountID" => $accountId]))));
    }

    /**
     * @dataProvider acceleratedUpdateDataProvider
     */
    public function testAcceleratedUpdate(\TestSymfonyGuy $I, Example $data)
    {
        $accountId = $I->createAwAccount($this->userId, "testprovider", "balance.random", null, ["UpdateDate" => $data['UpdateDate'], 'BalanceWatchStartDate' => date("Y-m-d H:i:s", strtotime($data['BalanceWatchStartDate']))]);
        $nextCheck = $this->scheduler->getAccountNextCheck($accountId);
        $I->assertEquals($data['ExpectedActivityScore'], $nextCheck['ActivityScore']);
        $I->assertEquals($data['ExpectedPriority'], $nextCheck['Priority']);
        $I->assertLessThan(360, abs(strtotime($data['ExpectedNextCheckDate']) - $nextCheck['NextCheckDate']));
    }

    public function testRestaurant(\TestSymfonyGuy $I)
    {
        $accountId = $I->createAwAccount($this->userId, 'opentable', 123, null, ['ExpirationAutoSet' => EXPIRATION_UNKNOWN, 'CreationDate' => date('Y-m-d', time() - SECONDS_PER_DAY * 30)]);
        $I->haveInDatabase('Restaurant', [
            'AccountID' => $accountId,
            'UserID' => $this->userId,
            'ProviderID' => 74,
            'Name' => 'Test',
            'StartDate' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        ]);

        for ($i = 0; $i < 10; $i++) {
            $I->haveInDatabase('AccountBalance', [
                'AccountID' => $accountId,
                'UpdateDate' => date('Y-m-d H:i:s', strtotime(sprintf('-%d hour', 100 + $i))),
                'Balance' => rand(1, 100),
            ]);
        }

        $nextCheck = $this->scheduler->getAccountNextCheck($accountId);
        $I->assertEquals(168, $nextCheck['ActivityScore']);
    }

    public function testQuestion(\TestSymfonyGuy $I)
    {
        $accountId = $I->createAwAccount($this->userId, "testprovider", "security.question", null, ["ExpirationAutoSet" => EXPIRATION_UNKNOWN, "CreationDate" => date("Y-m-d", time() - SECONDS_PER_DAY * 365)]);
        $I->checkAccount($accountId);
        $I->assertEquals(7 * 24, $this->scheduler->getAccountNextCheck($accountId)['ActivityScore']);

        for ($n = 1; $n < 5; $n++) {
            $I->executeQuery("update Account set ErrorCount = $n where AccountID = {$accountId}");
            $I->assertEquals(31 * 24, $this->scheduler->getAccountNextCheck($accountId)['ActivityScore'], "iteration $n");
        }

        for (; $n < 10; $n++) {
            $I->executeQuery("update Account set ErrorCount = $n where AccountID = {$accountId}");
            $I->assertEquals(90 * 24, $this->scheduler->getAccountNextCheck($accountId)['ActivityScore'], "iteration $n");
        }

        for (; $n < 13; $n++) {
            $I->executeQuery("update Account set ErrorCount = $n where AccountID = {$accountId}");
            $I->assertEquals(3 * 365 * 24, $this->scheduler->getAccountNextCheck($accountId)['ActivityScore'], "iteration $n");
        }
    }

    public function testArchived(\TestSymfonyGuy $I)
    {
        $accountId = $I->createAwAccount($this->userId, 'testprovider', 'balance.random', null, [
            'IsArchived' => Account::ARCHIVED,
            'CreationDate' => date('Y-m-d', time() - SECONDS_PER_DAY * 365),
        ]);
        $I->checkAccount($accountId);
        $I->assertEquals(90 * 24, $this->scheduler->getAccountNextCheck($accountId)['ActivityScore']);

        $accountId = $I->createAwAccount($this->userId, 'testprovider', 'invalid.logon', null, [
            'IsArchived' => Account::ARCHIVED,
            'CreationDate' => date('Y-m-d', time() - SECONDS_PER_DAY * 365),
        ]);
        $I->checkAccount($accountId);
        $I->assertEquals(90 * 24, $this->scheduler->getAccountNextCheck($accountId)['ActivityScore']);
    }

    public function testUserDisabledNotifications(\TestSymfonyGuy $I)
    {
        $accountId = $I->createAwAccount($this->userId, 'testprovider', 'balance.random', null, [
            'ExpirationAutoSet' => EXPIRATION_UNKNOWN,
            'CreationDate' => date('Y-m-d', time() - SECONDS_PER_DAY * 365),
        ]);
        $I->checkAccount($accountId);

        /** @var EntityManagerInterface $em */
        $em = $I->grabService('doctrine.orm.entity_manager');
        /** @var Usr $user */
        $user = $em->find(Usr::class, $this->userId);
        $I->haveInDatabase('DoNotSend', [
            'Email' => $user->getEmail(),
        ]);
        $I->updateInDatabase('Usr', [
            'WpDisableAll' => 1,
            'MpDisableAll' => 1,
        ], ['UserID' => $this->userId]);

        $I->assertEqualsWithDelta(
            strtotime('+100 year'),
            $this->scheduler->getAccountNextCheck($accountId)['NextCheckDate'],
            2 * 30 * 24 * 3600,
        );
    }

    private function changeBalance(\TestSymfonyGuy $I, $accountId, $times)
    {
        for ($i = 0; $i < $times; $i++) {
            $I->checkAccount($accountId);
        }
    }

    private function tripAndRes()
    {
        return [
            [
                'Kind' => 'T',
                'RecordLocator' => 'TEST001',
                'Passengers' => 'John Smith',
                'TotalCharge' => 100,
                'Tax' => 7,
                'Currency' => 'USD',
                'ReservationDate' => strtotime("2013-08-01"),
                'TripSegments' => [
                    [
                        'AirlineName' => 'Beta Alpha Test Airlines',
                        'Duration' => '2:00',
                        'DepDate' => strtotime("2037-08-01 7:00"),
                        'DepCode' => 'JFK',
                        'DepName' => 'JF Kennedy Airport',
                        'ArrDate' => strtotime("2037-08-01 11:00"),
                        'ArrCode' => 'LAX',
                        'ArrName' => 'Los Angeles International Airport',
                        'FlightNumber' => 'TE223',
                        'Seats' => '23',
                    ],
                ],
            ],
            [
                'Kind' => 'R',
                'ConfirmationNumber' => '1252463788',
                'HotelName' => 'Test Hotel',
                'CheckInDate' => strtotime("2037-08-01 17:00"),
                'CheckOutDate' => strtotime("2037-08-03 10:00"),
                'Address' => 'Los Angeles',
                'Phone' => '123-745-856',
                'Guests' => 3,
                'Kids' => 0,
                'Rooms' => 2,
                'Rate' => '9600 starpoints and USD 180.00',
                'RateType' => 'Spg Cash & Points Only To Be Booked With A Spg Award Stay. Guest Must Be A Spg.member. Must Redeem Starpoints For Cash And Points Award.',
                'CancellationPolicy' => '',
                'RoomType' => 'Superior Romantic Glimmering Room - Earlybird',
                'RoomTypeDescription' => 'Non-Smoking Room Confirmed',
                'Cost' => 280.60,
                'Taxes' => 35.10,
                'Total' => 315.70,
                'Currency' => 'USD',
            ],
        ];
    }

    private function acceleratedUpdateDataProvider()
    {
        return [
            [
                'UpdateDate' => '2001-01-01',
                'BalanceWatchStartDate' => "-12 hour",
                'ExpectedActivityScore' => 1,
                'ExpectedPriority' => BackgroundCheckScheduler::PRIORITY_ACCELERATED_UPDATE,
                'ExpectedNextCheckDate' => "2001-01-01 01:00",
            ],
            [
                'UpdateDate' => '2001-01-01',
                'BalanceWatchStartDate' => "-97 hour",
                'ExpectedActivityScore' => 768,
                'ExpectedPriority' => BackgroundCheckScheduler::PRIORITY_DEFAULT,
                'ExpectedNextCheckDate' => "+32 day",
            ],
            [
                'UpdateDate' => '2001-01-01',
                'BalanceWatchStartDate' => "+5 minute",
                'ExpectedActivityScore' => 768,
                'ExpectedPriority' => BackgroundCheckScheduler::PRIORITY_DEFAULT,
                'ExpectedNextCheckDate' => "+32 day",
            ],
            [
                'UpdateDate' => date("Y-m-d H:i:s"),
                'BalanceWatchStartDate' => "-12 hour",
                'ExpectedActivityScore' => 1,
                'ExpectedPriority' => BackgroundCheckScheduler::PRIORITY_ACCELERATED_UPDATE,
                'ExpectedNextCheckDate' => "+1 hour",
            ],
            [
                'UpdateDate' => date("Y-m-d H:i:s", strtotime("-6 hour")),
                'BalanceWatchStartDate' => "-12 hour",
                'ExpectedActivityScore' => 1,
                'ExpectedPriority' => BackgroundCheckScheduler::PRIORITY_ACCELERATED_UPDATE,
                'ExpectedNextCheckDate' => "-5 hour",
            ],
            [
                'UpdateDate' => date("Y-m-d H:i:s", strtotime("-10 minute")),
                'BalanceWatchStartDate' => "-12 hour",
                'ExpectedActivityScore' => 1,
                'ExpectedPriority' => BackgroundCheckScheduler::PRIORITY_ACCELERATED_UPDATE,
                'ExpectedNextCheckDate' => "+50 minute",
            ],
            [
                'UpdateDate' => date("Y-m-d H:i:s"),
                'BalanceWatchStartDate' => "-12 hour",
                'ExpectedActivityScore' => 1,
                'ExpectedPriority' => BackgroundCheckScheduler::PRIORITY_ACCELERATED_UPDATE,
                'ExpectedNextCheckDate' => "+56 minute",
            ],
        ];
    }
}
