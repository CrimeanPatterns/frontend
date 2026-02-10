<?php

namespace AwardWallet\Tests\Unit\PushNotifications;

use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\Notification;

use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertMatchesRegularExpression;

/**
 * @group mobile
 * @group push
 * @group frontend-unit
 */
class NewItinerariesTest extends BaseNotificationListenerTest
{
    public function _before()
    {
        parent::_before();

        $this->getDevice('3.18.1');
        $this->container->get(LocalizeService::class)->setLocale('en_US');
    }

    public function reservationsDataProvider()
    {
        $dataUS = [
            'Kind' => 'R',
            'ConfirmationNumber' => 'ABCD10050',
            'HotelName' => 'Pan American Hotel',
            'Address' => '79-00 Queens Blvd, Elmhurst, NY 11373, USA',
        ];

        $dataNonUS = array_merge(
            $dataUS,
            [
                'Address' => 'Tverskaya St, 3, Moskva, 125009',
                'HotelName' => 'The Ritz-Carlton',
            ]
        );

        return [
            [
                0,
                $dataUS,
                $date = new \DateTimeImmutable('+25 days 14:00'),
                sprintf('/Pan American Hotel in Queens County, NY on %s, has been added\./ims', $date->format('F j, Y')),
            ],
            [
                1,
                $dataUS,
                $date,
                sprintf('/Pan American Hotel in Queens County, NY on %s, and 1 additional item added\./ims', $date->format('F j, Y')),
            ],
            [
                3,
                $dataUS,
                $date,
                sprintf('/Pan American Hotel in Queens County, NY on %s, and 3 additional items added\./ims', $date->format('F j, Y')),
            ],
            [
                0,
                $dataNonUS,
                $date,
                sprintf('/The Ritz-Carlton in Moskva, Russia on %s, has been added\./ims', $date->format('F j, Y')),
            ],
            [
                1,
                $dataNonUS,
                $date,
                sprintf('/The Ritz-Carlton in Moskva, Russia on %s, and 1 additional item added\./ims', $date->format('F j, Y')),
            ],
            [
                3,
                $dataNonUS,
                $date,
                sprintf('/The Ritz-Carlton in Moskva, Russia on %s, and 3 additional items added\./ims', $date->format('F j, Y')),
            ],
        ];
    }

    /**
     * @dataProvider reservationsDataProvider
     */
    public function testNewReservations($additionalCount, array $hotelData, \DateTimeImmutable $baseDate, $regexp)
    {
        $itineraries = [];

        for ($i = 1; $i <= $additionalCount; $i++) {
            $checkInDate = $baseDate->modify("+" . ($i * 2) . " days 14:00");
            $checkOutDate = $checkInDate->modify('+2 days');
            $itineraries[] = array_merge(
                $hotelData,
                [
                    'CheckInDate' => $checkInDate,
                    'CheckOutDate' => $checkOutDate,
                    'ConfirmationNumber' => 'ABCDEFG10050_' . $i,
                ]
            );
        }

        $this->assertNotificationForNewItineraries(
            array_merge(
                $itineraries,
                [array_merge(
                    $hotelData,
                    [
                        'CheckInDate' => $baseDate,
                        'CheckOutDate' => $baseDate->modify('+2 days'),
                    ]
                )]
            ),
            $regexp
        );
    }

    public function rentalsDataProvider()
    {
        $dataUS = [
            'Kind' => 'L',
            'Number' => 'ABCD10050',
            'PickupLocation' => 'LGA',
            'DropoffLocation' => 'EWR',
            'RentalCompany' => 'TestItineraryRental',
        ];

        $dataNonUS = array_merge(
            $dataUS,
            [
                'PickupLocation' => 'VKO',
                'DropoffLocation' => 'VKO',
                'RentalCompany' => 'TestItineraryRental',
            ]
        );

        $dataAirport = array_merge(
            $dataUS,
            ['PickupLocation' => 'JFK']
        );

        return [
            [
                0,
                $dataUS,
                $date = new \DateTimeImmutable('+25 days 14:00'),
                sprintf('/TestItineraryRental car rental at LGA airport on %s, has been added\./ims', $date->format('F j, Y')),
            ],
            [
                1,
                $dataUS,
                $date,
                sprintf('/TestItineraryRental car rental at LGA airport on %s, and 1 additional item added\./ims', $date->format('F j, Y')),
            ],
            [
                3,
                $dataUS,
                $date,
                sprintf('/TestItineraryRental car rental at LGA airport on %s, and 3 additional items added\./ims', $date->format('F j, Y')),
            ],
            [
                0,
                $dataNonUS,
                $date,
                sprintf('/TestItineraryRental car rental at VKO airport on %s, has been added\./ims', $date->format('F j, Y')),
            ],
            [
                1,
                $dataNonUS,
                $date,
                sprintf('/TestItineraryRental car rental at VKO airport on %s, and 1 additional item added\./ims', $date->format('F j, Y')),
            ],
            [
                3,
                $dataNonUS,
                $date,
                sprintf('/TestItineraryRental car rental at VKO airport on %s, and 3 additional items added\./ims', $date->format('F j, Y')),
            ],
            [
                0,
                $dataAirport,
                $date,
                sprintf('/TestItineraryRental car rental at JFK airport on %s, has been added\./ims', $date->format('F j, Y')),
            ],
            [
                1,
                $dataAirport,
                $date,
                sprintf('/TestItineraryRental car rental at JFK airport on %s, and 1 additional item added\./ims', $date->format('F j, Y')),
            ],
            [
                3,
                $dataAirport,
                $date,
                sprintf('/TestItineraryRental car rental at JFK airport on %s, and 3 additional items added\./ims', $date->format('F j, Y')),
            ],
        ];
    }

    /**
     * @dataProvider rentalsDataProvider
     */
    public function testNewRentals($additionalCount, array $rentalData, \DateTimeImmutable $baseDate, $regexp)
    {
        $itineraries = [];

        for ($i = 1; $i <= $additionalCount; $i++) {
            $itineraries[] = array_merge(
                $rentalData,
                [
                    'PickupDatetime' => $checkin = $baseDate->modify("+" . ($i * 2) . " days 14:00"),
                    'DropoffDatetime' => $checkin->modify('+2 days'),
                    'Number' => 'ABCDEFG10050_' . $i,
                ]
            );
        }

        $this->assertNotificationForNewItineraries(
            array_merge(
                $itineraries,
                [array_merge(
                    $rentalData,
                    [
                        'PickupDatetime' => $baseDate,
                        'DropoffDatetime' => $baseDate->modify('+2 days'),
                    ]
                )]
            ),
            $regexp,
            [
                'ShortName' => 'TestItineraryRental',
                'IATACode' => 'TI',
            ]
        );
    }

    public function eventsDataProvider()
    {
        $dataUS = [
            'Kind' => 'E',
            'ConfNo' => 'ABCD10050',
            'Name' => 'McDonalds',
            'Address' => '79-00 Queens Blvd, Elmhurst, NY 11373, USA',
        ];

        $dataNonUS = array_merge(
            $dataUS,
            [
                'Address' => 'Tverskaya St, 3, Moskva, 125009',
                'Name' => 'Bliny',
            ]
        );

        return [
            [
                0,
                $dataUS,
                $date = new \DateTimeImmutable('+25 days 14:00'),
                sprintf('/McDonalds on %s at 2:00 PM, has been added./ims', $date->format('F j, Y')),
            ],
            [
                1,
                $dataUS,
                $date,
                sprintf('/McDonalds on %s at 2:00 PM, and 1 additional item added\./ims', $date->format('F j, Y')),
            ],
            [
                3,
                $dataUS,
                $date,
                sprintf('/McDonalds on %s at 2:00 PM, and 3 additional items added\./ims', $date->format('F j, Y')),
            ],
            [
                0,
                $dataNonUS,
                $date,
                sprintf('/Bliny on %s at 2:00 PM, has been added\./ims', $date->format('F j, Y')),
            ],
            [
                1,
                $dataNonUS,
                $date,
                sprintf('/Bliny on %s at 2:00 PM, and 1 additional item added\./ims', $date->format('F j, Y')),
            ],
            [
                3,
                $dataNonUS,
                $date,
                sprintf('/Bliny on %s at 2:00 PM, and 3 additional items added\./ims', $date->format('F j, Y')),
            ],
        ];
    }

    /**
     * @dataProvider eventsDataProvider
     */
    public function testNewEvents($additionalCount, array $eventData, \DateTimeImmutable $baseDate, $regexp)
    {
        $itineraries = [];

        for ($i = 1; $i <= $additionalCount; $i++) {
            $itineraries[] = array_merge(
                $eventData,
                [
                    'StartDate' => $checkin = $baseDate->modify("+" . ($i * 2) . " days 14:00"),
                    'ConfNo' => 'ABCDEFG10050_' . $i,
                ]
            );
        }

        $this->assertNotificationForNewItineraries(
            array_merge(
                $itineraries,
                [array_merge(
                    $eventData,
                    [
                        'StartDate' => $baseDate,
                    ]
                )]
            ),
            $regexp
        );
    }

    public function tripsegmentsDataProvider()
    {
        $data = [
            'Kind' => 'T',
            'RecordLocator' => 'ABCDEF',
            'TripCategory' => TRIP_CATEGORY_AIR,
            'TripSegments' => [
                [
                    'AirlineName' => 'American Airlines',
                    'FlightNumber' => '10050',
                    'DepCode' => 'JFK',
                    'DepName' => 'JFK',
                    'ArrCode' => 'LAX',
                    'ArrName' => 'LAX',
                ],
            ],
        ];

        return [
            [
                0,
                function () use ($data) {
                    $data['TripSegments'][0]['FlightNumber'] = 'UnknownFlightNumber';

                    return $data;
                },
                $date = new \DateTimeImmutable('+25 days 12:00'),
                sprintf('/American Airlines reservation JFK to LAX on %s, has been added\./ims', $date->format('F j, Y')),
            ],
            [
                0,
                $data,
                $date,
                sprintf('/American Airlines reservation 10050 JFK to LAX on %s, has been added\./ims', $date->format('F j, Y')),
            ],
            [
                1,
                $data,
                $date,
                sprintf('/American Airlines reservation 10050 JFK to LAX on %s, and 1 additional item added\./ims', $date->format('F j, Y')),
            ],
            [
                3,
                $data,
                $date,
                sprintf('/American Airlines reservation 10050 JFK to LAX on %s, and 3 additional items added\./ims', $date->format('F j, Y')),
            ],
        ];
    }

    /**
     * @dataProvider tripsegmentsDataProvider
     * @param array $tripData
     */
    public function testNewTripsegments($additionalCount, $tripData, \DateTimeImmutable $baseDate, $regexp)
    {
        if (is_object($tripData) && $tripData instanceof \Closure) {
            $tripData = $tripData();
        }

        $itineraries = [];

        for ($i = 1; $i <= $additionalCount; $i++) {
            $trip = array_merge(
                $tripData,
                ['RecordLocator' => 'ABCDEF' . $i]
            );
            $trip['TripSegments'][0]['DepDate'] = $date = $baseDate->modify("+" . ($i * 2) . " days 14:00");
            $trip['TripSegments'][0]['ArrDate'] = $date->modify('+3 hours');

            $itineraries[] = $trip;
        }

        $tripData['TripSegments'][0]['DepDate'] = $baseDate;
        $tripData['TripSegments'][0]['ArrDate'] = $baseDate->modify('+3 hours');

        $this->assertNotificationForNewItineraries(
            array_merge($itineraries, [$tripData]),
            $regexp
        );
    }

    public function testNewPropertiesTripsegments()
    {
        $baseDate = new \DateTimeImmutable('+25 days 05:00');

        $tripData = [
            'Kind' => 'T',
            'RecordLocator' => 'ABCDEF',
            'TripCategory' => TRIP_CATEGORY_AIR,
            'TripSegments' => [
                [
                    'FlightNumber' => '10050',
                    'AirlineName' => 'American Airlines',
                    'DepCode' => 'JFK',
                    'DepName' => 'JFK',
                    'ArrCode' => 'LAX',
                    'ArrName' => 'LAX',
                    'DepDate' => $baseDate,
                    'ArrDate' => $baseDate->modify('+3 hours'),
                    'Seats' => function () {
                        static $value = '';
                        $result = $value;
                        $value .= "45";

                        return $result;
                    },
                ],
            ],
        ];

        $notifications = [];
        $this->captureNotificationsOn(function () use ($tripData) {
            $accountId = $this->createAccountWithItineraries([$tripData]);
            $this->aw->checkAccount($accountId);
            $accountId = $this->createAccountWithItineraries([$tripData]);
            $this->aw->checkAccount($accountId);
        }, $notifications);

        $this->assertCount(1, $notifications);
        /** @var Notification $notification */
        $notification = @unserialize($notifications[0]);
        assertInstanceOf(Notification::class, $notification);
        $messageRegexp = sprintf('/American Airlines reservation 10050 JFK to LAX on %s, has been added\./ims', $baseDate->format('F j, Y'));
        assertMatchesRegularExpression($messageRegexp, $notification->getMessage());
    }

    protected function assertNotificationForNewItineraries(array $itineraries, $regexp, array $providerData = [])
    {
        if ($providerData) {
            [$_, $notifications] = $this->createAndCheckAccountWithItineraries($itineraries, $providerData);
        } else {
            [$_, $notifications] = $this->createAndCheckAccountWithItineraries($itineraries);
        }

        $this->assertOneNotificationWithText($notifications, $regexp);
    }
}
