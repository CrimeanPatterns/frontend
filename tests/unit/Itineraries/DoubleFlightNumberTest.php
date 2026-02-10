<?php

namespace AwardWallet\Tests\Unit\Itineraries;

use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\Tests\Unit\BaseUserTest;

/**
 * @group frontend-unit
 */
class DoubleFlightNumberTest extends BaseUserTest
{
    public function testDoubles()
    {
        $providerId = $this->aw->createAwProvider(
            StringUtils::getRandomCode(20),
            null,
            [],
            [
                'ParseItineraries' => function () {
                    $baseDate = strtotime("+1 month");

                    return [
                        [
                            'RecordLocator' => 'TESTTE',
                            'Kind' => 'T',
                            'TripSegments' => [
                                [
                                    'FlightNumber' => '123',
                                    'AirlineName' => 'some name',
                                    'DepName' => 'some dep name',
                                    'ArrName' => 'some arr name',
                                    'DepCode' => 'JFK',
                                    'ArrCode' => 'LAX',
                                    'DepDate' => strtotime(date("Y-m-d", $baseDate) . " 7:00"),
                                    'ArrDate' => strtotime(date("Y-m-d", $baseDate) . " 9:00"),
                                ],
                                [
                                    'FlightNumber' => '123',
                                    'AirlineName' => 'some name',
                                    'DepName' => 'some dep name',
                                    'ArrName' => 'some arr name',
                                    'DepCode' => 'LAX',
                                    'ArrCode' => 'SEA',
                                    'DepDate' => strtotime(date("Y-m-d", $baseDate) . " 10:00"),
                                    'ArrDate' => strtotime(date("Y-m-d", $baseDate) . " 12:00"),
                                ],
                            ],
                        ],
                    ];
                },
            ]
        );

        $accountId = $this->aw->createAwAccount($this->user->getUserid(), $providerId, "Something");
        $this->aw->checkAccount($accountId);
        $this->assertEquals(2, $this->db->query("select count(ts.TripSegmentID) from TripSegment ts 
		join Trip t on ts.TripID = t.TripID where t.AccountID = $accountId")->fetchColumn());
    }
}
