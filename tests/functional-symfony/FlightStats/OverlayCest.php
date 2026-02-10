<?php

namespace AwardWallet\Tests\FunctionalSymfony\FlightStats;

use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\Receiver;

/**
 * @group frontend-functional
 */
class OverlayCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function testApplyOverlayOnNew(\TestSymfonyGuy $I)
    {
        $flightNumber = random_int(1, 99999);
        $year = intval(date("Y")) + 1;
        $id = "AA.{$flightNumber}.LGA.{$year}-03-16T17:40";
        $overlayData = '{"departure":{"terminal":"B","localDateTime":"' . $year . '-03-16T18:36:00","gate":"C8"},"arrival":{"localDateTime":"' . $year . '-03-16T20:41:00","gate":"B20","baggage":"5"}}';
        $I->haveInDatabase("Overlay", ["Kind" => "S", "ID" => $id, 'Source' => Receiver::SOURCE, 'ExpirationDate' => date("Y-m-d H:i:s", strtotime("+ 30 day")), 'Data' => $overlayData]);

        $userId = $I->createAwUser(null, null, [], true);
        $accountId = $this->createTestAccount($I, $userId, $flightNumber, $year);
        $I->checkAccount($accountId, true);
        $I->assertEquals(ACCOUNT_CHECKED, $I->grabFromDatabase("Account", "ErrorCode", ["AccountID" => $accountId]));

        $segment = $I->query("select ts.* from TripSegment ts join Trip t on ts.TripID = t.TripID where t.AccountID = $accountId")->fetch(\PDO::FETCH_ASSOC);
        $I->assertNotEmpty($segment);
        $I->assertEquals($year . '-03-16 18:36:00', $segment['DepDate']);
        $I->assertEquals($year . '-03-16 20:41:00', $segment['ArrDate']);
        $I->assertSame("C8", $segment['DepartureGate']);
        $I->assertSame("B20", $segment['ArrivalGate']);

        // check second update
        $overlayData = '{"departure":{"terminal":"B","localDateTime":"' . $year . '-03-16T18:50:00","gate":"C8"},"arrival":{"localDateTime":"' . $year . '-03-16T20:41:00","gate":"B20","baggage":"5"}}';
        $I->executeQuery("update Overlay set Data = '" . addslashes($overlayData) . "' where Kind = 'S' and ID = '{$id}' and Source = '" . Receiver::SOURCE . "'");
        $I->checkAccount($accountId, true);
        $segment = $I->query("select ts.* from TripSegment ts join Trip t on ts.TripID = t.TripID where t.AccountID = $accountId")->fetch(\PDO::FETCH_ASSOC);
        $I->assertNotEmpty($segment);
        $I->assertEquals($year . '-03-16 18:50:00', $segment['DepDate']);
    }

    public function testApplyOverlayOnExisting(\TestSymfonyGuy $I)
    {
        $flightNumber = random_int(1, 99999);
        $year = intval(date("Y")) + 1;

        $userId = $I->createAwUser(null, null, [], true);
        $accountId = $this->createTestAccount($I, $userId, $flightNumber, $year);
        $I->checkAccount($accountId, true);
        $I->assertEquals(ACCOUNT_CHECKED, $I->grabFromDatabase("Account", "ErrorCode", ["AccountID" => $accountId]));
        $segment = $I->query("select ts.* from TripSegment ts join Trip t on ts.TripID = t.TripID where t.AccountID = $accountId")->fetch(\PDO::FETCH_ASSOC);
        $I->assertNotEmpty($segment);
        $I->assertEquals($year . '-03-16 17:40:00', $segment['DepDate']);

        $id = "AA.{$flightNumber}.LGA.{$year}-03-16T17:40";
        $overlayData = '{"departure":{"terminal":"B","localDateTime":"' . $year . '-03-16T18:36:00","gate":"C8"},"arrival":{"localDateTime":"' . $year . '-03-16T20:41:00","gate":"B20","baggage":"5"}}';
        $I->haveInDatabase("Overlay", ["Kind" => "S", "ID" => $id, 'Source' => Receiver::SOURCE, 'ExpirationDate' => date("Y-m-d H:i:s", strtotime("+ 30 day")), 'Data' => $overlayData]);
        $I->checkAccount($accountId, true);

        $segment = $I->query("select ts.* from TripSegment ts join Trip t on ts.TripID = t.TripID where t.AccountID = $accountId")->fetch(\PDO::FETCH_ASSOC);
        $I->assertNotEmpty($segment);
        $I->assertEquals($year . '-03-16 18:36:00', $segment['DepDate']);
    }

    private function createTestAccount(\TestSymfonyGuy $I, $userId, $flightNumber, $year)
    {
        $providerId = $I->createAwProvider(
            $code = 'tripalerts' . StringHandler::getRandomCode(7),
            $code,
            ['IATACode' => 'AA'],
            [
                'ParseItineraries' => function () use ($flightNumber, $year) {
                    return [
                        [
                            "RecordLocator" => "FLIGHT1",
                            "TripSegments" => [
                                [
                                    "AirlineName" => "American Airlines",
                                    "Status" => "Confirmed",
                                    "FlightNumber" => $flightNumber,
                                    "DepName" => "LGA",
                                    "DepCode" => "LGA",
                                    "DepDate" => strtotime($year . '-03-16 17:40:00'),
                                    "ArrName" => "CMH",
                                    "ArrCode" => "CMH",
                                    "ArrDate" => strtotime($year . '-03-16 19:45:00'),
                                ],
                            ],
                        ],
                    ];
                },
            ]
        );

        return $I->createAwAccount($userId, $providerId, $code);
    }
}
