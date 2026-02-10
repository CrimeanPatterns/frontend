<?php

namespace AwardWallet\Tests\Unit\Checker;

/**
 * @group frontend-unit
 */
class GeoTagCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    private $userId;

    public function _before(\CodeGuy $I)
    {
        $this->userId = $I->createAwUser(null, null, [], true, true);
    }

    public function testReservation(\CodeGuy $I)
    {
        $address = '123, Street';

        $providerId = $I->createAwProvider(null, null, [], [
            'ParseItineraries' => function () use (&$address) {
                /** @var $this \TAccountChecker */
                return [
                    [
                        'Kind' => 'R',
                        'ConfirmationNumber' => '1252463788',
                        'HotelName' => 'Not existing hotel',
                        'CheckInDate' => strtotime('2030-01-01 12:20'),
                        'CheckOutDate' => strtotime('2030-01-02 12:20'),
                        'Address' => $address,
                    ],
                ];
            },
        ]);
        $accountId = $I->createAwAccount($this->userId, $providerId, "some");
        $I->checkAccount($accountId);

        $geoTagId = $I->grabFromDatabase("Reservation", "GeoTagID", ["AccountID" => $accountId]);
        $I->assertNotEmpty($geoTagId);
        $I->seeInDatabase("GeoTag", ["GeoTagID" => $geoTagId, "Address" => '123, Street']);

        $address = '124, Street';
        $I->checkAccount($accountId);

        $geoTag2Id = $I->grabFromDatabase("Reservation", "GeoTagID", ["AccountID" => $accountId]);
        $I->assertNotEmpty($geoTag2Id);
        $I->assertNotEquals($geoTagId, $geoTag2Id);
        $I->assertEquals($address, $I->grabFromDatabase("GeoTag", "Address", ["GeoTagID" => $geoTag2Id]));
    }

    public function testRental(\CodeGuy $I)
    {
        $address = 'PHL';

        $providerId = $I->createAwProvider(null, null, [], [
            'ParseItineraries' => function () use (&$address) {
                /** @var $this \TAccountChecker */
                return [
                    [
                        'RentalCompany' => 'Hertz',
                        'Kind' => 'L',
                        'Number' => '123456789',
                        'PickupDatetime' => strtotime('2030-01-01 12:20'),
                        'PickupLocation' => $address,
                        'DropoffDatetime' => strtotime('2030-01-02 12:20'),
                        'DropoffLocation' => 'JFK',
                    ],
                ];
            },
        ]);
        $accountId = $I->createAwAccount($this->userId, $providerId, "some");
        $I->checkAccount($accountId);

        $geoTagId = $I->grabFromDatabase("Rental", "PickupGeoTagID", ["AccountID" => $accountId]);
        $I->assertNotEmpty($geoTagId);
        $I->seeInDatabase("GeoTag", ["GeoTagID" => $geoTagId, "Address" => "PHL"]);

        $geoTagId = $I->grabFromDatabase("Rental", "DropoffGeoTagID", ["AccountID" => $accountId]);
        $I->assertNotEmpty($geoTagId);
        $I->seeInDatabase("GeoTag", ["GeoTagID" => $geoTagId, "Address" => "JFK"]);

        $address = 'LAX';
        $I->checkAccount($accountId);

        $geoTag2Id = $I->grabFromDatabase("Rental", "PickupGeoTagID", ["AccountID" => $accountId]);
        $I->assertNotEquals($geoTagId, $geoTag2Id);
        $I->seeInDatabase("GeoTag", ["GeoTagID" => $geoTag2Id, "Address" => "LAX"]);
    }

    public function testRestaurant(\CodeGuy $I)
    {
        $address = '124 Street';

        $providerId = $I->createAwProvider(null, null, [], [
            'ParseItineraries' => function () use (&$address) {
                /** @var $this \TAccountChecker */
                return [
                    [
                        'Kind' => 'E',
                        'ConfNo' => '123456789',
                        'Name' => 'Kentucky Fried Chicken',
                        'StartDate' => strtotime('12 may 2030, 12:00'),
                        'EndDate' => strtotime('12 may 2030, 14:00'),
                        'Address' => $address,
                    ],
                ];
            },
        ]);
        $accountId = $I->createAwAccount($this->userId, $providerId, "some");
        $I->checkAccount($accountId);

        $geoTagId = $I->grabFromDatabase("Restaurant", "GeoTagID", ["AccountID" => $accountId]);
        $I->assertNotEmpty($geoTagId);
        $I->seeInDatabase("GeoTag", ["GeoTagID" => $geoTagId, "Address" => "124 Street"]);

        $address = '125 Street';
        $I->checkAccount($accountId);

        $geoTag2Id = $I->grabFromDatabase("Restaurant", "GeoTagID", ["AccountID" => $accountId]);
        $I->assertNotEmpty($geoTag2Id);
        $I->seeInDatabase("GeoTag", ["GeoTagID" => $geoTag2Id, "Address" => "125 Street"]);
    }

    public function testTrip(\CodeGuy $I)
    {
        $providerId = $I->createAwProvider(null, null, [], [
            'ParseItineraries' => function () {
                /** @var $this \TAccountChecker */
                return [
                    [
                        'Kind' => 'T',
                        'RecordLocator' => 'TEST001',
                        'TripSegments' => [
                            [
                                'DepDate' => strtotime("2037-08-01 7:00"),
                                'DepCode' => 'JFK',
                                'DepName' => 'JF Kennedy Airport',
                                'ArrDate' => strtotime("2037-08-01 11:00"),
                                'ArrCode' => 'LAX',
                                'ArrName' => 'Los Angeles International Airport',
                                'FlightNumber' => 'TE223',
                            ],
                        ],
                    ],
                ];
            },
        ]);
        $accountId = $I->createAwAccount($this->userId, $providerId, "some");
        $I->checkAccount($accountId);

        $tripId = $I->grabFromDatabase("Trip", "TripID", ["AccountID" => $accountId]);
        $geoTagId = $I->grabFromDatabase("TripSegment", "DepGeoTagID", ["TripID" => $tripId]);
        $I->assertNotEmpty($geoTagId);
        $I->seeInDatabase("GeoTag", [
            "GeoTagID" => $geoTagId,
            "FoundAddress" => "New York John F. Kennedy International Airport, New York, US",
        ]);

        $geoTagId = $I->grabFromDatabase("TripSegment", "ArrGeoTagID", ["TripID" => $tripId]);
        $I->assertNotEmpty($geoTagId);
        $I->seeInDatabase("GeoTag", [
            "GeoTagID" => $geoTagId,
            "FoundAddress" => "Los Angeles International Airport, Los Angeles, US",
        ]);

        $I->checkAccount($accountId);
    }
}
