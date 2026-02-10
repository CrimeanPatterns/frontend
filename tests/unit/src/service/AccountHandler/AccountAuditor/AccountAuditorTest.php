<?php

namespace AwardWallet\Tests\Unit;

use AwardWallet\MainBundle\Globals\Updater\Engine\UpdaterEngineInterface;

class AccountAuditorTest extends BaseUserTest
{
    /**
     * @var \AccountAuditor
     */
    private $accountAuditor;

    public function _before()
    {
        parent::_before();
        $this->accountAuditor = new \AccountAuditor();
    }

    /**
     * @dataProvider sourceProvider
     */
    public function testRestoreTripsOnUpdate(string $source, string $shouldRestore)
    {
        $recordLocator = (string) (int) bin2hex(random_bytes(10));
        $tripId = $this->db->haveInDatabase('Trip', [
            'recordLocator' => $recordLocator,
            'Hidden' => 1,
            'UserID' => $this->user->getUserid(),
        ]);
        $this->aw->createTripSegment([
            'TripID' => $tripId,
            'DepName' => 'ALLENTOWN, PA',
            'DepDate' => mktime(0, 10, 0, 1, 1, date("Y") + 1),
            'ArrName' => 'DETROIT',
            'ArrDate' => mktime(4, 10, 0, 1, 1, date("Y") + 1),
            'Hidden' => 1,
        ]);

        $account = new \Account($this->aw->createAwAccount($this->user->getUserid(), $this->aw->createAwProvider(), 'test'));
        $report = new \AccountCheckReport();
        $report->errorCode = ACCOUNT_CHECKED;
        $options = new \AuditorOptions();
        $options->checkIts = true;
        $options->source = $source;
        $report->properties['Itineraries'] = [
            [
                'TripSegments' => [
                    [
                        'DepDate' => mktime(0, 10, 0, 1, 1, date("Y") + 1),
                        'DepName' => 'ALLENTOWN, PA',
                        'DepCode' => 'SFO',
                        'ArrDate' => mktime(4, 10, 0, 1, 1, date("Y") + 1),
                        'ArrName' => 'DETROIT',
                        'ArrCode' => 'ATL',
                        'AirlineName' => 'DELTA AIR LINES INC',
                        'FlightNumber' => '4532',
                    ],
                ],
                'RecordLocator' => $recordLocator,
                'Kind' => 'T',
            ],
        ];

        $this->accountAuditor->save($account, $report, $options);

        if ($shouldRestore) {
            $this->db->seeInDatabase('Trip', ['TripID' => $tripId, 'Hidden' => 0]);
        } else {
            $this->db->seeInDatabase('Trip', ['TripID' => $tripId, 'Hidden' => 1]);
        }
    }

    /**
     * @dataProvider sourceProvider
     */
    public function testRestoreRentalsOnUpdate(string $source, string $shouldRestore)
    {
        $number = (string) (int) bin2hex(random_bytes(10));
        $rentalId = $this->db->haveInDatabase('Rental', [
            'Number' => $number,
            'UserID' => $this->user->getUserid(),
            'PickupLocation' => 'some location',
            'DropoffLocation' => 'some other location',
            'PickupDatetime' => mktime(0, 10, 0, 1, 1, date("Y") + 1),
            'DropoffDatetime' => mktime(4, 10, 0, 1, 1, date("Y") + 1),
            'Hidden' => 1,
        ]);

        $account = new \Account($this->aw->createAwAccount($this->user->getUserid(), $this->aw->createAwProvider(), 'test'));
        $report = new \AccountCheckReport();
        $report->errorCode = ACCOUNT_CHECKED;
        $options = new \AuditorOptions();
        $options->checkIts = true;
        $options->source = $source;
        $report->properties['Rentals'] = [
            [
                'Number' => $number,
                'PickupLocation' => 'some location',
                'DropoffLocation' => 'some other location',
                'PickupDatetime' => mktime(0, 10, 0, 1, 1, date("Y") + 1),
                'DropoffDatetime' => mktime(4, 10, 0, 1, 1, date("Y") + 1),
                'Kind' => 'L',
            ],
        ];

        $this->accountAuditor->save($account, $report, $options);

        if ($shouldRestore) {
            $this->db->seeInDatabase('Rental', ['RentalID' => $rentalId, 'Hidden' => 0]);
        } else {
            $this->db->seeInDatabase('Rental', ['RentalID' => $rentalId, 'Hidden' => 1]);
        }
    }

    /**
     * @dataProvider sourceProvider
     */
    public function testRestoreEventsOnUpdate(string $source, string $shouldRestore)
    {
        $confNo = (string) (int) bin2hex(random_bytes(10));
        $eventId = $this->db->haveInDatabase('Restaurant', [
            'ConfNo' => $confNo,
            'UserID' => $this->user->getUserid(),
            'Address' => 'some location',
            'StartDate' => mktime(0, 10, 0, 1, 1, date("Y") + 1),
            'EndDate' => mktime(4, 10, 0, 1, 1, date("Y") + 1),
            'Hidden' => 1,
        ]);

        $account = new \Account($this->aw->createAwAccount($this->user->getUserid(), $this->aw->createAwProvider(), 'test'));
        $report = new \AccountCheckReport();
        $report->errorCode = ACCOUNT_CHECKED;
        $options = new \AuditorOptions();
        $options->checkIts = true;
        $options->source = $source;
        $report->properties['Restaurants'] = [
            [
                'ConfNo' => $confNo,
                'Name' => 'SOME NAME',
                'Address' => 'some location',
                'StartDate' => mktime(0, 10, 0, 1, 1, date("Y") + 1),
                'EndDate' => mktime(4, 10, 0, 1, 1, date("Y") + 1),
                'Kind' => 'E',
            ],
        ];

        $this->accountAuditor->save($account, $report, $options);

        if ($shouldRestore) {
            $this->db->seeInDatabase('Restaurant', ['RestaurantID' => $eventId, 'Hidden' => 0]);
        } else {
            $this->db->seeInDatabase('Restaurant', ['RestaurantID' => $eventId, 'Hidden' => 1]);
        }
    }

    /**
     * @dataProvider sourceProvider
     */
    public function testRestoreReservationsOnUpdate(string $source, string $shouldRestore)
    {
        $confirmationNumber = (string) (int) bin2hex(random_bytes(10));
        $reservationId = $this->db->haveInDatabase('Reservation', [
            'ConfirmationNumber' => $confirmationNumber,
            'HotelName' => 'Some Hotel',
            'UserID' => $this->user->getUserid(),
            'CheckInDate' => mktime(0, 10, 0, 1, 1, date("Y") + 1),
            'CheckOutDate' => mktime(4, 10, 0, 1, 1, date("Y") + 1),
            'Hidden' => 1,
        ]);

        $account = new \Account($this->aw->createAwAccount($this->user->getUserid(), $this->aw->createAwProvider(), 'test'));
        $report = new \AccountCheckReport();
        $report->errorCode = ACCOUNT_CHECKED;
        $options = new \AuditorOptions();
        $options->checkIts = true;
        $options->source = $source;
        $report->properties['Reservations'] = [
            [
                'ConfirmationNumber' => $confirmationNumber,
                'HotelName' => 'Some Hotel',
                'CheckInDate' => mktime(0, 10, 0, 1, 1, date("Y") + 1),
                'CheckOutDate' => mktime(4, 10, 0, 1, 1, date("Y") + 1),
                'Kind' => 'R',
            ],
        ];

        $this->accountAuditor->save($account, $report, $options);

        if ($shouldRestore) {
            $this->db->seeInDatabase('Reservation', ['ReservationID' => $reservationId, 'Hidden' => 0]);
        } else {
            $this->db->seeInDatabase('Reservation', ['ReservationID' => $reservationId, 'Hidden' => 1]);
        }
    }

    public function sourceProvider()
    {
        return [
            ['source' => UpdaterEngineInterface::SOURCE_DESKTOP, 'restore' => true],
            ['source' => UpdaterEngineInterface::SOURCE_MOBILE, 'restore' => true],
            ['source' => UpdaterEngineInterface::SOURCE_BACKGROUND, 'restore' => false],
            ['source' => UpdaterEngineInterface::SOURCE_OPERATIONS, 'restore' => false],
        ];
    }

    /**
     * @dataProvider tripGeoTagsProvider
     * @param int $departureTimeZone
     * @param int $arrivalTimeZone
     */
    public function testTripGeoTags(string $departureName, string $arrivalName, string $departureCode, string $arrivalCode, string $departureTimeZone, string $arrivalTimeZone)
    {
        $recordLocator = (string) (int) bin2hex(random_bytes(10));
        $account = new \Account($this->aw->createAwAccount($this->user->getUserid(), $this->aw->createAwProvider(), 'test'));
        $report = new \AccountCheckReport();
        $report->errorCode = ACCOUNT_CHECKED;
        $options = new \AuditorOptions();
        $options->checkIts = true;
        $report->properties['Itineraries'] = [
            [
                'TripSegments' => [
                    [
                        'DepDate' => mktime(0, 10, 0, 1, 1, date("Y") + 1),
                        'DepName' => $departureName,
                        'DepCode' => $departureCode,
                        'ArrDate' => mktime(4, 10, 0, 1, 1, date("Y") + 1),
                        'ArrName' => $arrivalName,
                        'ArrCode' => $arrivalCode,
                        'AirlineName' => 'DELTA AIR LINES INC',
                        'FlightNumber' => '4532',
                    ],
                ],
                'RecordLocator' => $recordLocator,
                'Kind' => 'T',
            ],
        ];
        $this->accountAuditor->save($account, $report, $options);
        $this->db->seeInDatabase('Trip', ['RecordLocator' => $recordLocator]);
        $tripId = $this->db->grabFromDatabase('Trip', 'TripID', ['recordLocator' => $recordLocator]);
        $this->db->seeInDatabase('TripSegment', ['TripID' => $tripId]);
        $depGeoTagId = $this->db->grabFromDatabase('TripSegment', 'DepGeoTagID', ['TripID' => $tripId]);
        $this->assertNotNull($depGeoTagId);
        $arrGeoTagId = $this->db->grabFromDatabase('TripSegment', 'ArrGeoTagID', ['TripID' => $tripId]);
        $this->assertNotNull($arrGeoTagId);
        $this->db->seeInDatabase('GeoTag', ['GeoTagID' => $depGeoTagId, 'TimeZoneLocation' => $departureTimeZone]);
        $this->db->seeInDatabase('GeoTag', ['GeoTagID' => $arrGeoTagId, 'TimeZoneLocation' => $arrivalTimeZone]);
    }

    public function tripGeoTagsProvider()
    {
        return [
            [ // Code should take priority
                'departureName' => 'Tokyo',
                'arrivalName' => 'Osaka',
                'departureCode' => 'SVO',
                'arrivalCode' => 'DME',
                'departureTimeZone' => 'Europe/Moscow',
                'arrivalTimeZone' => 'Europe/Moscow',
            ],
            [ // Name should be used
                'departureName' => 'Tokyo',
                'arrivalName' => 'Osaka',
                'departureCode' => TRIP_CODE_UNKNOWN,
                'arrivalCode' => TRIP_CODE_UNKNOWN,
                'departureTimeZone' => 'Asia/Tokyo',
                'arrivalTimeZone' => 'Asia/Tokyo',
            ],
        ];
    }

    public function testRentalGeoTags()
    {
        $number = (string) (int) bin2hex(random_bytes(10));
        $account = new \Account($this->aw->createAwAccount($this->user->getUserid(), $this->aw->createAwProvider(), 'test'));
        $report = new \AccountCheckReport();
        $report->errorCode = ACCOUNT_CHECKED;
        $options = new \AuditorOptions();
        $options->checkIts = true;
        $report->properties['Rentals'] = [
            [
                'Number' => $number,
                'PickupLocation' => 'Moscow',
                'DropoffLocation' => 'Saint Petersburg',
                'PickupDatetime' => mktime(0, 10, 0, 1, 1, date("Y") + 1),
                'DropoffDatetime' => mktime(4, 10, 0, 1, 1, date("Y") + 1),
                'Kind' => 'L',
            ],
        ];

        $this->accountAuditor->save($account, $report, $options);
        $this->db->seeInDatabase('Rental', ['Number' => $number]);
        $pickUpGeoTagId = $this->db->grabFromDatabase('Rental', 'PickupGeoTagID', ['Number' => $number]);
        $this->assertNotNull($pickUpGeoTagId);
        $dropOffGeoTagId = $this->db->grabFromDatabase('Rental', 'DropoffGeoTagID', ['Number' => $number]);
        $this->assertNotNull($dropOffGeoTagId);
        $this->db->seeInDatabase('GeoTag', ['GeoTagID' => $pickUpGeoTagId, 'TimeZoneLocation' => 'Europe/Moscow']);
        $this->db->seeInDatabase('GeoTag', ['GeoTagID' => $dropOffGeoTagId, 'TimeZoneLocation' => 'Europe/Moscow']);
    }

    public function testEventGeoTags()
    {
        $confNo = (string) (int) bin2hex(random_bytes(10));

        $account = new \Account($this->aw->createAwAccount($this->user->getUserid(), $this->aw->createAwProvider(), 'test'));
        $report = new \AccountCheckReport();
        $report->errorCode = ACCOUNT_CHECKED;
        $options = new \AuditorOptions();
        $options->checkIts = true;
        $report->properties['Restaurants'] = [
            [
                'ConfNo' => $confNo,
                'Name' => 'SOME NAME',
                'Address' => 'Moscow',
                'StartDate' => mktime(0, 10, 0, 1, 1, date("Y") + 1),
                'EndDate' => mktime(4, 10, 0, 1, 1, date("Y") + 1),
                'Kind' => 'E',
            ],
        ];

        $this->accountAuditor->save($account, $report, $options);
        $this->db->seeInDatabase('Restaurant', ['ConfNo' => $confNo]);
        $geoTagId = $this->db->grabFromDatabase('Restaurant', 'GeoTagID', ['ConfNo' => $confNo]);
        $this->assertNotNull($geoTagId);
        $this->db->seeInDatabase('GeoTag', ['GeoTagID' => $geoTagId, 'TimeZoneLocation' => 'Europe/Moscow']);
    }

    public function testReservationGeoTags()
    {
        $confirmationNumber = (string) (int) bin2hex(random_bytes(10));
        $account = new \Account($this->aw->createAwAccount($this->user->getUserid(), $this->aw->createAwProvider(), 'test'));
        $report = new \AccountCheckReport();
        $report->errorCode = ACCOUNT_CHECKED;
        $options = new \AuditorOptions();
        $options->checkIts = true;
        $report->properties['Reservations'] = [
            [
                'ConfirmationNumber' => $confirmationNumber,
                'HotelName' => 'Some Hotel',
                'Address' => 'Moscow',
                'CheckInDate' => mktime(0, 10, 0, 1, 1, date("Y") + 1),
                'CheckOutDate' => mktime(4, 10, 0, 1, 1, date("Y") + 1),
                'Kind' => 'R',
            ],
        ];

        $this->accountAuditor->save($account, $report, $options);
        $this->db->seeInDatabase('Reservation', ['ConfirmationNumber' => $confirmationNumber]);
        $geoTagId = $this->db->grabFromDatabase('Reservation', 'GeoTagID', ['ConfirmationNumber' => $confirmationNumber]);
        $this->assertNotNull($geoTagId);
        $this->db->seeInDatabase('GeoTag', ['GeoTagID' => $geoTagId, 'TimeZoneLocation' => 'Europe/Moscow']);
    }
}
