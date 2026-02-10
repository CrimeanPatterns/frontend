<?php

namespace AwardWallet\Tests\Unit;

use Codeception\Module\Aw;

class ItineraryFilterTest extends BaseUserTest
{
    public function testFilterSeats()
    {
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), Aw::TEST_PROVIDER_ID, "future.trip.round");
        $report = new \AccountCheckReport();
        $report->account = new \Account($accountId);
        $report->properties['Itineraries'] = [
            [
                'RecordLocator' => 'X8383S',
                'Passengers' => 'Mr STEPHANE CHARBONNEAU',
                'AccountNumbers' => '2024663055',
                'Currency' => null,
                'TotalCharge' => null,
                'TripSegments' => [
                    [
                        'DepCode' => 'BER',
                        'DepName' => 'Berlin Brandenburg Airport',
                        'ArrCode' => 'CDG',
                        'ArrName' => 'Charles De Gaulle Airport',
                        'DepDate' => strtotime('2030-01-01 10:00'),
                        'ArrDate' => strtotime('2030-01-01 11:50'),
                        'Duration' => null,
                        'Cabin' => 'Economy',
                        'Meal' => 'Snack, sandwich and/or meal',
                        'FlightNumber' => 'TE223',
                        'Seats' => '12A (Preferred), 12C (Preferred)',
                    ],
                    [
                        'DepCode' => 'TLS',
                        'DepName' => 'Blagnac',
                        'ArrCode' => 'BER',
                        'ArrName' => 'Berlin Brandenburg Airport',
                        'DepDate' => strtotime('2030-01-01 12:20'),
                        'ArrDate' => strtotime('2030-01-01 14:20'),
                        'Duration' => '2h15m',
                        'Cabin' => 'Economy',
                        'Meal' => 'Snack, sandwich and/or meal',
                        'FlightNumber' => 'TE223',
                        'Seats' => 'No Seat ¤',
                    ],
                    [
                        'DepCode' => 'TLS',
                        'DepName' => 'Blagnac',
                        'ArrCode' => 'BER',
                        'ArrName' => 'Berlin Brandenburg Airport',
                        'DepDate' => strtotime('2030-01-01 12:20'),
                        'ArrDate' => strtotime('2030-01-01 14:20'),
                        'Duration' => '2h15m',
                        'Cabin' => 'Economy',
                        'Meal' => 'Snack, sandwich and/or meal',
                        'FlightNumber' => 'TE223',
                        'Seats' => 'A2 , 05s',
                    ],
                ],
            ],
        ];
        $report->filter();
        $this->assertTrue(isset($report->properties['Itineraries'][0]['TripSegments'][0]['Seats']));
        $this->assertEquals("12A (Preferred), 12C (Preferred)", $report->properties['Itineraries'][0]['TripSegments'][0]['Seats']);
        $this->assertFalse(isset($report->properties['Itineraries'][0]['TripSegments'][1]['Seats']));
        $this->assertTrue(isset($report->properties['Itineraries'][0]['TripSegments'][2]['Seats']));
        $this->assertEquals("A2, 05s", $report->properties['Itineraries'][0]['TripSegments'][2]['Seats']);
    }
}
