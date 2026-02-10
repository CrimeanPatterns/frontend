<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\VisitedCountries;

use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\VisitedCountries\Period;
use AwardWallet\MainBundle\Service\VisitedCountries\Reporter;
use AwardWallet\Tests\Unit\BaseUserTest;

/**
 * @group frontend-unit
 */
class ReporterTest extends BaseUserTest
{
    private const ICON_TRIP = '✈';
    private const ICON_RESERVATION = '🏨';
    private const ICON_RENTAL = '🚗';
    private const ICON_EVENT = '🍴';
    private const ICON_PARKING = '🅿';

    private ?Reporter $reporter;

    private array $ua = [];
    private array $geoTags = [];

    public function _before()
    {
        parent::_before();

        $this->reporter = $this->container->get(Reporter::class);
        $this->ua = $this->geoTags = [];
    }

    public function _after()
    {
        $this->reporter = null;
        $this->ua = $this->geoTags = [];

        parent::_after();
    }

    /**
     * @dataProvider dataProvider
     */
    public function test(array $its, array $calls)
    {
        $this->prepareIts($its);

        foreach ($calls as $call) {
            $expected = $call[0];
            $ua = null;

            if (isset($call[1])) {
                $ua = $this->parseFamilyMember($call[1]);
            }

            if (isset($ua)) {
                $ua = $this->getFamilyMember($ua);
            }
            $expected = array_map(function ($result) {
                if (preg_match('/^([^\<]+)\s+\<([^\>]+)\>\s+-\s+\<([^\>]+)\>$/ims', $result, $matches)) {
                    $start = $matches[2] === '?' ? null : new \DateTime($matches[2]);
                    $end = $matches[3] === '?' ? null : new \DateTime($matches[3]);

                    return new Period(
                        trim($matches[1]),
                        $start,
                        $end
                    );
                }

                throw new \RuntimeException(sprintf('Invalid fixture "%s"', $result));
            }, $expected);

            $this->assertEquals($expected, $this->reporter->getCountries($this->user, $ua));
        }
    }

    public function dataProvider(): array
    {
        return [
            [
                [
                    '✈ [Russia <2020-01-01 00:00:00>] → [Russia <2020-01-01 00:10:00>]',
                    '✈ [Russia <2020-01-05 15:00:00>] → [Russia <2020-01-05 17:00:00>]',
                    '✈ [Russia <2020-10-05 23:00:00>] → [Finland <2020-10-06 03:00:00>]',
                    '✈ [Finland <2020-10-10 15:00:00>] → [Russia <2020-10-10 18:00:00>]',
                    '✈ [👤1] [Russia <2020-10-05 23:00:00>] → [Russia <2020-10-06 03:00:00>]',
                    '✈ [👤2] [Russia <2020-10-06 03:00:00>] → [Russia <2020-10-06 03:10:00>]',
                    '✈ [👤2] [Russia <2020-10-08 00:00:00>] → [Russia <2020-10-08 02:00:00>]',
                ],
                [
                    [
                        [
                            'Russia <2020-01-01 00:00:00> - <2020-10-05 23:00:00>',
                            'Finland <2020-10-06 03:00:00> - <2020-10-10 15:00:00>',
                            'Russia <2020-10-10 18:00:00> - <2020-10-10 18:00:00>',
                        ],
                    ],
                    [
                        [
                            'Russia <2020-10-05 23:00:00> - <2020-10-06 03:00:00>',
                        ],
                        '[👤1]',
                    ],
                    [
                        [
                            'Russia <2020-10-06 03:00:00> - <2020-10-08 02:00:00>',
                        ],
                        '[👤2]',
                    ],
                ],
            ],

            [
                [
                    '🏨 [Russia <2020-01-01 00:00:00> → <2020-01-10 00:10:00>]',
                    '🏨 [Ukraine <2020-02-01 00:00:00> → <2020-02-10 00:10:00>]',
                    '🏨 [👤1] [Spain <2020-02-01 00:00:00> → <2020-02-10 00:10:00>]',
                ],
                [
                    [
                        [
                            'Russia <2020-01-01 00:00:00> - <2020-01-10 00:10:00>',
                            'Ukraine <2020-02-01 00:00:00> - <2020-02-10 00:10:00>',
                        ],
                    ],
                    [
                        [
                            'Spain <2020-02-01 00:00:00> - <2020-02-10 00:10:00>',
                        ],
                        '[👤1]',
                    ],
                ],
            ],

            [
                [
                    '🚗 [Russia <2020-10-08 00:00:00>] → [Russia <2020-10-08 02:00:00>]',
                    '🚗 [Spain <2020-11-01 00:00:00>] → [Ukraine <2020-11-05 00:00:00>]',
                ],
                [
                    [
                        [],
                    ],
                ],
            ],

            [
                [
                    '🍴 [Russia <2020-10-08 00:00:00> → <2020-10-08 03:00:00>]',
                ],
                [
                    [
                        [
                            'Russia <2020-10-08 00:00:00> - <2020-10-08 03:00:00>',
                        ],
                    ],
                ],
            ],

            [
                [
                    '🅿 [Russia <2020-10-08 00:00:00> → <2020-10-08 03:00:00>]',
                ],
                [
                    [
                        [],
                    ],
                ],
            ],

            [
                [
                    '✈ [Russia <2020-01-01 00:00:00>] → [Russia <2020-01-01 05:00:00>]',
                    '🏨 [Russia <2020-01-02 00:00:00> → <2020-02-01 00:00:00>]',
                    '✈ [Russia <2020-02-01 05:00:00>] → [Germany <2020-02-01 10:00:00>]',
                    '✈ [Germany <2020-02-01 13:00:00>] → [Ireland <2020-02-01 18:00:00>]',
                    '✈ [Ireland <2020-02-02 17:00:00>] → [Australia <2020-02-03 05:00:00>]',
                    '🏨 [Australia <2020-02-03 09:00:00> → <2020-02-13 09:00:00>]',
                    '🍴 [Australia <2020-02-15 21:00:00> → <?>]',
                    '✈ [Australia <2020-03-08 09:00:00>] → [Finland <2020-03-08 19:00:00>]',
                    '✈ [Russia <2021-01-01 00:00:00>] → [Jamaica <2021-01-01 12:00:00>]',
                ],
                [
                    [
                        [
                            'Russia <2020-01-01 00:00:00> - <2020-02-01 05:00:00>',
                            'Australia <2020-02-03 05:00:00> - <2020-03-08 09:00:00>',
                            'Finland <2020-03-08 19:00:00> - <2020-03-08 19:00:00>',
                            'Russia <2021-01-01 00:00:00> - <2021-01-01 00:00:00>',
                            'Jamaica <2021-01-01 12:00:00> - <2021-01-01 12:00:00>',
                        ],
                    ],
                ],
            ],

            [
                [
                    '✈ [Russia <2020-01-05 15:00:00>] → [Russia <2020-01-05 17:00:00>]',
                    '🏨 [Russia <2020-01-05 20:00:00> → <2020-01-10 00:00:00>]',
                    '🏨 [Russia <2020-03-10 00:00:00> → <2020-03-15 00:00:00>]',
                    '✈ [Russia <2020-10-05 23:00:00>] → [Finland <2020-10-06 03:00:00>]',
                    '🚗 [Finland <2020-10-06 03:00:00>] → [Finland <2020-10-08 00:00:00>]',
                    '🍴 [Russia <2020-12-30 00:00:00> → <2020-12-30 03:00:00>]',
                ],
                [
                    [
                        [
                            'Russia <2020-01-05 15:00:00> - <2020-10-05 23:00:00>',
                            'Finland <2020-10-06 03:00:00> - <2020-10-06 03:00:00>',
                            'Russia <2020-12-30 00:00:00> - <2020-12-30 03:00:00>',
                        ],
                    ],
                ],
            ],
        ];
    }

    private function prepareIts(array $its)
    {
        foreach ($its as $it) {
            /** @var string $it */
            $type = mb_substr($it, 0, 1);

            switch ($type) {
                case static::ICON_TRIP:
                    $this->prepareTrip($it);

                    break;

                case static::ICON_RESERVATION:
                    $this->prepareReservation($it);

                    break;

                case static::ICON_RENTAL:
                    $this->prepareRental($it);

                    break;

                case static::ICON_EVENT:
                    $this->prepareEvent($it);

                    break;

                case static::ICON_PARKING:
                    $this->prepareParking($it);

                    break;
            }
        }
    }

    /**
     * @param string $trip
     *
     * ✈ [Russia <2020-01-01 00:00:00>] → [Ukraine <2020-01-01 05:00:00>]
     * ✈ [👤1] [Russia <2020-01-01 00:00:00>] → [Ukraine <2020-01-01 05:00:00>]
     */
    private function prepareTrip(string $trip)
    {
        if (!preg_match('/\[([^\]]+)\s+\<([^>]+)\>\]\s+→\s+\[([^\]]+)\s+\<([^>]+)\>\]$/ims', $trip, $matches)) {
            return;
        }

        $ua = $this->parseFamilyMember($trip);
        $startCountry = trim($matches[1]);
        $startDate = new \DateTime($matches[2]);
        $endCountry = trim($matches[3]);
        $endDate = new \DateTime($matches[4]);

        $tripId = $this->db->haveInDatabase('Trip', [
            'UserID' => $this->user->getId(),
            'UserAgentID' => isset($ua) ? $this->getFamilyMember($ua)->getId() : null,
            'RecordLocator' => StringHandler::getRandomCode(5),
        ]);

        $depCode = substr($startCountry, 0, 3);
        $arrCode = substr($endCountry, 0, 3);

        $this->db->haveInDatabase('TripSegment', [
            'TripID' => $tripId,
            'DepCode' => $depCode,
            'DepName' => $depCode,
            'DepDate' => $startDate->format('Y-m-d H:i:s'),
            'ScheduledDepDate' => $startDate->format('Y-m-d H:i:s'),
            'DepGeoTagID' => $this->getGeoTagId($startCountry, $startDate->getTimezone()),
            'ArrCode' => $arrCode,
            'ArrName' => $arrCode,
            'ArrDate' => $endDate->format('Y-m-d H:i:s'),
            'ScheduledArrDate' => $endDate->format('Y-m-d H:i:s'),
            'ArrGeoTagID' => $this->getGeoTagId($endCountry, $endDate->getTimezone()),
        ]);
    }

    /**
     * @param string $reservation
     * 🏨 [Russia <2020-01-01 00:00:00> → <2020-01-10 00:00:00>]
     * 🏨 [👤1] [Russia <2020-01-01 00:00:00> → <2020-01-10 00:00:00>]
     */
    private function prepareReservation(string $reservation)
    {
        if (!preg_match('/\[([^\<\[]+)\s+\<([^>]+)\>\s+→\s+\<([^>]+)\>\]$/ims', $reservation, $matches)) {
            return;
        }

        $ua = $this->parseFamilyMember($reservation);
        $country = trim($matches[1]);
        $startDate = new \DateTime($matches[2]);
        $endDate = new \DateTime($matches[3]);

        $this->db->haveInDatabase('Reservation', [
            'UserID' => $this->user->getId(),
            'UserAgentID' => isset($ua) ? $this->getFamilyMember($ua)->getId() : null,
            'HotelName' => 'Test Hotel',
            'CheckInDate' => $startDate->format('Y-m-d H:i:s'),
            'CheckOutDate' => $endDate->format('Y-m-d H:i:s'),
            'GeoTagID' => $this->getGeoTagId($country, $startDate->getTimezone()),
        ]);
    }

    /**
     * @param string $rental
     * 🚗 [Russia <2020-01-01 00:00:00>] → [Russia <2020-01-10 00:00:00>]
     * 🚗 [👤1] [Russia <2020-01-01 00:00:00>] → [Russia <2020-01-10 00:00:00>]
     */
    private function prepareRental(string $rental)
    {
        if (!preg_match('/\[([^\]]+)\s+\<([^>]+)\>\]\s+→\s+\[([^\]]+)\s+\<([^>]+)\>\]$/ims', $rental, $matches)) {
            return;
        }

        $ua = $this->parseFamilyMember($rental);
        $startCountry = trim($matches[1]);
        $startDate = new \DateTime($matches[2]);
        $endCountry = trim($matches[3]);
        $endDate = new \DateTime($matches[4]);

        $this->db->haveInDatabase('Rental', [
            'UserID' => $this->user->getId(),
            'UserAgentID' => isset($ua) ? $this->getFamilyMember($ua)->getId() : null,
            'PickupLocation' => 'Location',
            'PickupDatetime' => $startDate->format('Y-m-d H:i:s'),
            'PickupGeoTagID' => $this->getGeoTagId($startCountry, $startDate->getTimezone()),
            'DropoffLocation' => 'Location2',
            'DropoffDatetime' => $endDate->format('Y-m-d H:i:s'),
            'DropoffGeoTagID' => $this->getGeoTagId($endCountry, $endDate->getTimezone()),
        ]);
    }

    /**
     * @param string $event
     * 🍴 [Russia <2020-01-01 00:00:00> → <2020-01-10 00:00:00>]
     * 🍴 [Russia <2020-01-01 00:00:00> → <?>]
     * 🍴 [👤1] [Russia <2020-01-01 00:00:00> → <2020-01-10 00:00:00>]
     * 🍴 [👤1] [Russia <2020-01-01 00:00:00> → <?>]
     */
    private function prepareEvent(string $event)
    {
        if (!preg_match('/\[([^\<\[]+)\s+\<([^>]+)\>\s+→\s+\<([^>]+)\>\]$/ims', $event, $matches)) {
            return;
        }

        $ua = $this->parseFamilyMember($event);
        $country = trim($matches[1]);
        $startDate = new \DateTime($matches[2]);

        if ($matches[3] === '?') {
            $endDate = null;
        } else {
            $endDate = new \DateTime($matches[3]);
        }

        $this->db->haveInDatabase('Restaurant', [
            'UserID' => $this->user->getId(),
            'UserAgentID' => isset($ua) ? $this->getFamilyMember($ua)->getId() : null,
            'Name' => 'Event',
            'StartDate' => $startDate->format('Y-m-d H:i:s'),
            'EndDate' => $endDate ? $endDate->format('Y-m-d H:i:s') : null,
            'GeoTagID' => $this->getGeoTagId($country, $startDate->getTimezone()),
        ]);
    }

    /**
     * @param string $parking
     * 🅿 [Russia <2020-01-01 00:00:00> → <2020-01-10 00:00:00>]
     * 🅿 [👤1] [Russia <2020-01-01 00:00:00> → <2020-01-10 00:00:00>]
     */
    private function prepareParking(string $parking)
    {
        if (!preg_match('/\[([^\<]+)\s+\<([^>]+)\>\s+→\s+\<([^>]+)\>\]$/ims', $parking, $matches)) {
            return;
        }

        $ua = $this->parseFamilyMember($parking);
        $country = trim($matches[1]);
        $startDate = new \DateTime($matches[2]);
        $endDate = new \DateTime($matches[3]);

        $this->db->haveInDatabase('Parking', [
            'UserID' => $this->user->getId(),
            'UserAgentID' => isset($ua) ? $this->getFamilyMember($ua)->getId() : null,
            'StartDatetime' => $startDate->format('Y-m-d H:i:s'),
            'EndDatetime' => $endDate->format('Y-m-d H:i:s'),
            'GeoTagID' => $this->getGeoTagId($country, $startDate->getTimezone()),
        ]);
    }

    private function parseFamilyMember(string $it): ?string
    {
        if (preg_match('/\[👤([^\]]+)\]/ims', $it, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function getFamilyMember(string $id): Useragent
    {
        if (isset($this->ua[$id])) {
            return $this->ua[$id];
        }

        $name = StringHandler::getRandomName();

        return $this->ua[$id] = $this->em->getRepository(Useragent::class)->find(
            $this->aw->createFamilyMember($this->user->getId(), $name['FirstName'], $name['LastName'])
        );
    }

    private function getGeoTagId(string $country, \DateTimeZone $timeZone): int
    {
        $key = sprintf('%s-%s', $country, $timeZone->getName());

        if (isset($this->geoTags[$key])) {
            return $this->geoTags[$key];
        }

        return $this->geoTags[$key] = $this->db->haveInDatabase('GeoTag', [
            'Address' => uniqid($country),
            'Lat' => 0,
            'Lng' => 0,
            'TimeZoneLocation' => $timeZone->getName(),
            'Country' => $country,
            'CountryCode' => substr($country, 0, 2),
        ]);
    }
}
