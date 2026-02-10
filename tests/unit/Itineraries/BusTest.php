<?php

namespace AwardWallet\Tests\Unit\Itineraries;

use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\Tests\Unit\BaseUserTest;

/**
 * @group frontend-unit
 */
class BusTest extends BaseUserTest
{
    public function testDoNotRequireFlightNumber()
    {
        $providerId = $this->aw->createAwProvider(
            StringUtils::getRandomCode(19),
            'p' . StringUtils::getRandomCode(19),
            [],
            [
                'ParseItineraries' => function () {
                    $baseDate = strtotime("+1 month");

                    return [
                        [
                            'RecordLocator' => 'BUS123',
                            'Kind' => 'T',
                            'TripCategory' => TRIP_CATEGORY_BUS,
                            'TripSegments' => [
                                [
                                    'FlightNumber' => FLIGHT_NUMBER_UNKNOWN,
                                    'DepName' => 'School',
                                    'DepCode' => TRIP_CODE_UNKNOWN,
                                    'DepAddress' => '70 W Oak St, Basking Ridge, NJ 07920, USA',
                                    'ArrName' => 'University',
                                    'ArrCode' => TRIP_CODE_UNKNOWN,
                                    'ArrAddress' => '450 S Easton Rd, Glenside, PA 19038, USA',
                                    'DepDate' => strtotime(date("Y-m-d", $baseDate) . " 7:00"),
                                    'ArrDate' => strtotime(date("Y-m-d", $baseDate) . " 9:00"),
                                ],
                            ],
                        ],
                    ];
                },
            ]
        );

        $accountId = $this->aw->createAwAccount($this->user->getUserid(), $providerId, "Something");
        $this->aw->checkAccount($accountId);
        $this->assertEquals(1, $this->db->query("select count(ts.TripSegmentID) from TripSegment ts 
		join Trip t on ts.TripID = t.TripID where t.AccountID = $accountId")->fetchColumn());
    }
}
