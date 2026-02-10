<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\FlightNotification;

use AwardWallet\MainBundle\Service\FlightNotification\OffsetHandler;
use AwardWallet\MainBundle\Service\FlightNotification\OffsetStatus;
use AwardWallet\Tests\Unit\BaseContainerTest;

/**
 * @group frontend-unit
 */
class OffsetHandlerTest extends BaseContainerTest
{
    private ?OffsetHandler $offsetHandler;

    public function _before()
    {
        parent::_before();

        $this->offsetHandler = $this->container->get(OffsetHandler::class);
    }

    public function _after()
    {
        parent::_after();

        $this->offsetHandler = null;
    }

    /**
     * @dataProvider getOffsetsStatusesByProviderIdDataProvider
     */
    public function testGetOffsetsStatusesByProviderId(array $expected, array $offsets, string $now, string $depDate)
    {
        $providerId = $this->db->grabFromDatabase('Provider', 'ProviderID', ['Code' => 'testprovider']);
        $this->db->updateInDatabase('Provider', [
            'CheckInReminderOffsets' => json_encode($offsets),
        ], [
            'ProviderID' => $providerId,
        ]);

        $statuses = $this->offsetHandler->getOffsetsStatusesByProviderId(
            $providerId,
            new \DateTime($now),
            $depDateObj = new \DateTime($depDate)
        );

        foreach ($expected as $k => $e) {
            $expected[$k]['Timestamp'] = $depDateObj->getTimestamp() - $e['Offset'];
        }

        $this->assertCount(count($expected), $statuses);
        $this->assertEquals($expected, array_map(function (OffsetStatus $status) {
            return [
                'Category' => $status->getCategories(),
                'Offset' => $status->getOffset(),
                'Delay' => $status->getSendingDelay(),
                'Kind' => $status->getKind(),
                'Timestamp' => $status->getTimestamp(),
            ];
        }, $statuses));
    }

    public function getOffsetsStatusesByProviderIdDataProvider(): array
    {
        return [
            'complex' => self::data(
                [
                    [
                        'Category' => [OffsetHandler::CATEGORY_PUSH, OffsetHandler::CATEGORY_MAIL],
                        'Offset' => self::secondsFromHours(24),
                        'Delay' => -self::secondsFromHours(20),
                        'Kind' => OffsetHandler::KIND_CHECKIN,
                    ],
                    [
                        'Category' => [OffsetHandler::CATEGORY_PUSH],
                        'Offset' => self::secondsFromHours(4),
                        'Delay' => 0,
                        'Kind' => OffsetHandler::KIND_DEPARTURE,
                    ],
                    [
                        'Category' => [OffsetHandler::CATEGORY_PUSH],
                        'Offset' => self::secondsFromHours(1),
                        'Delay' => self::secondsFromHours(3),
                        'Kind' => OffsetHandler::KIND_BOARDING,
                    ],
                ],
                [
                    OffsetHandler::CATEGORY_PUSH => [24, 4, 1],
                    OffsetHandler::CATEGORY_MAIL => [24],
                ],
                '2022-12-21 02:40',
                '2022-12-21 06:40'
            ),
            'checkin, 24h + 3h' => self::data(
                [
                    [
                        'Category' => [OffsetHandler::CATEGORY_PUSH],
                        'Offset' => self::secondsFromHours(24),
                        'Delay' => self::secondsFromHours(3),
                        'Kind' => OffsetHandler::KIND_CHECKIN,
                    ],
                ],
                [
                    OffsetHandler::CATEGORY_PUSH => [24],
                ],
                '2022-01-02 07:00',
                '2022-01-03 10:00',
            ),

            'checkin, 24h + 3h + 1m' => self::data(
                [],
                [
                    OffsetHandler::CATEGORY_PUSH => [24],
                ],
                '2022-01-02 06:59',
                '2022-01-03 10:00',
            ),

            'checkin, 24h + 1h' => self::data(
                [
                    [
                        'Category' => [OffsetHandler::CATEGORY_PUSH],
                        'Offset' => self::secondsFromHours(24),
                        'Delay' => self::secondsFromHours(1),
                        'Kind' => OffsetHandler::KIND_CHECKIN,
                    ],
                ],
                [
                    OffsetHandler::CATEGORY_PUSH => [24],
                ],
                '2022-01-02 09:00',
                '2022-01-03 10:00',
            ),

            'checkin, 24h + 1m' => self::data(
                [
                    [
                        'Category' => [OffsetHandler::CATEGORY_PUSH],
                        'Offset' => self::secondsFromHours(24),
                        'Delay' => 60,
                        'Kind' => OffsetHandler::KIND_CHECKIN,
                    ],
                ],
                [
                    OffsetHandler::CATEGORY_PUSH => [24],
                ],
                '2022-01-02 09:59',
                '2022-01-03 10:00',
            ),

            'checkin, 24h' => self::data(
                [
                    [
                        'Category' => [OffsetHandler::CATEGORY_PUSH],
                        'Offset' => self::secondsFromHours(24),
                        'Delay' => 0,
                        'Kind' => OffsetHandler::KIND_CHECKIN,
                    ],
                ],
                [
                    OffsetHandler::CATEGORY_PUSH => [24],
                ],
                '2022-01-02 10:00',
                '2022-01-03 10:00',
            ),

            'checkin, 23h 59m' => self::data(
                [
                    [
                        'Category' => [OffsetHandler::CATEGORY_PUSH],
                        'Offset' => self::secondsFromHours(24),
                        'Delay' => -60,
                        'Kind' => OffsetHandler::KIND_CHECKIN,
                    ],
                ],
                [
                    OffsetHandler::CATEGORY_PUSH => [24],
                ],
                '2022-01-02 10:01',
                '2022-01-03 10:00',
            ),

            'checkin, 23h' => self::data(
                [
                    [
                        'Category' => [OffsetHandler::CATEGORY_PUSH],
                        'Offset' => self::secondsFromHours(24),
                        'Delay' => -self::secondsFromHours(1),
                        'Kind' => OffsetHandler::KIND_CHECKIN,
                    ],
                ],
                [
                    OffsetHandler::CATEGORY_PUSH => [24],
                ],
                '2022-01-02 11:00',
                '2022-01-03 10:00',
            ),

            'checkin, 3h 1m' => self::data(
                [
                    [
                        'Category' => [OffsetHandler::CATEGORY_PUSH],
                        'Offset' => self::secondsFromHours(24),
                        'Delay' => -(self::secondsFromHours(21) - 60),
                        'Kind' => OffsetHandler::KIND_CHECKIN,
                    ],
                ],
                [
                    OffsetHandler::CATEGORY_PUSH => [24],
                ],
                '2022-01-03 06:59',
                '2022-01-03 10:00',
            ),

            'checkin, 3h' => self::data(
                [
                    [
                        'Category' => [OffsetHandler::CATEGORY_PUSH],
                        'Offset' => self::secondsFromHours(24),
                        'Delay' => -self::secondsFromHours(21),
                        'Kind' => OffsetHandler::KIND_CHECKIN,
                    ],
                ],
                [
                    OffsetHandler::CATEGORY_PUSH => [24],
                ],
                '2022-01-03 07:00',
                '2022-01-03 10:00',
            ),

            'checkin, 1h' => self::data(
                [],
                [
                    OffsetHandler::CATEGORY_PUSH => [24],
                ],
                '2022-01-03 09:00',
                '2022-01-03 10:00',
            ),

            'checkin, -1h' => self::data(
                [],
                [
                    OffsetHandler::CATEGORY_PUSH => [24],
                ],
                '2022-01-03 11:00',
                '2022-01-03 10:00',
            ),

            'departure, 4h + 3h' => self::data(
                [
                    [
                        'Category' => [OffsetHandler::CATEGORY_PUSH],
                        'Offset' => self::secondsFromHours(4),
                        'Delay' => self::secondsFromHours(3),
                        'Kind' => OffsetHandler::KIND_DEPARTURE,
                    ],
                ],
                [
                    OffsetHandler::CATEGORY_PUSH => [4],
                ],
                '2022-01-03 03:00',
                '2022-01-03 10:00',
            ),

            'departure, 4h + 3h + 1m' => self::data(
                [],
                [
                    OffsetHandler::CATEGORY_PUSH => [4],
                ],
                '2022-01-03 02:59',
                '2022-01-03 10:00',
            ),

            'departure, 4h + 30m' => self::data(
                [
                    [
                        'Category' => [OffsetHandler::CATEGORY_PUSH],
                        'Offset' => self::secondsFromHours(4),
                        'Delay' => self::secondsFromHours(0.5),
                        'Kind' => OffsetHandler::KIND_DEPARTURE,
                    ],
                ],
                [
                    OffsetHandler::CATEGORY_PUSH => [4],
                ],
                '2022-01-03 05:30',
                '2022-01-03 10:00',
            ),

            'departure, 4h' => self::data(
                [
                    [
                        'Category' => [OffsetHandler::CATEGORY_PUSH],
                        'Offset' => self::secondsFromHours(4),
                        'Delay' => 0,
                        'Kind' => OffsetHandler::KIND_DEPARTURE,
                    ],
                ],
                [
                    OffsetHandler::CATEGORY_PUSH => [4],
                ],
                '2022-01-03 06:00',
                '2022-01-03 10:00',
            ),

            'departure, 3h' => self::data(
                [
                    [
                        'Category' => [OffsetHandler::CATEGORY_PUSH],
                        'Offset' => self::secondsFromHours(4),
                        'Delay' => -self::secondsFromHours(1),
                        'Kind' => OffsetHandler::KIND_DEPARTURE,
                    ],
                ],
                [
                    OffsetHandler::CATEGORY_PUSH => [4],
                ],
                '2022-01-03 07:00',
                '2022-01-03 10:00',
            ),

            'departure, 1h 1m' => self::data(
                [],
                [
                    OffsetHandler::CATEGORY_PUSH => [4],
                ],
                '2022-01-03 08:59',
                '2022-01-03 10:00',
            ),

            'departure, 1h' => self::data(
                [],
                [
                    OffsetHandler::CATEGORY_PUSH => [4],
                ],
                '2022-01-03 09:00',
                '2022-01-03 10:00',
            ),

            'departure, -1h' => self::data(
                [],
                [
                    OffsetHandler::CATEGORY_PUSH => [4],
                ],
                '2022-01-03 11:00',
                '2022-01-03 10:00',
            ),

            'boarding, 1h + 3h' => self::data(
                [
                    [
                        'Category' => [OffsetHandler::CATEGORY_PUSH],
                        'Offset' => self::secondsFromHours(1),
                        'Delay' => self::secondsFromHours(3),
                        'Kind' => OffsetHandler::KIND_BOARDING,
                    ],
                ],
                [
                    OffsetHandler::CATEGORY_PUSH => [1],
                ],
                '2022-01-03 06:00',
                '2022-01-03 10:00',
            ),

            'boarding, 1h + 3h + 1m' => self::data(
                [],
                [
                    OffsetHandler::CATEGORY_PUSH => [1],
                ],
                '2022-01-03 05:59',
                '2022-01-03 10:00',
            ),

            'boarding, 1h + 30m' => self::data(
                [
                    [
                        'Category' => [OffsetHandler::CATEGORY_PUSH],
                        'Offset' => self::secondsFromHours(1),
                        'Delay' => self::secondsFromHours(0.5),
                        'Kind' => OffsetHandler::KIND_BOARDING,
                    ],
                ],
                [
                    OffsetHandler::CATEGORY_PUSH => [1],
                ],
                '2022-01-03 08:30',
                '2022-01-03 10:00',
            ),

            'boarding, 1h' => self::data(
                [
                    [
                        'Category' => [OffsetHandler::CATEGORY_PUSH],
                        'Offset' => self::secondsFromHours(1),
                        'Delay' => 0,
                        'Kind' => OffsetHandler::KIND_BOARDING,
                    ],
                ],
                [
                    OffsetHandler::CATEGORY_PUSH => [1],
                ],
                '2022-01-03 09:00',
                '2022-01-03 10:00',
            ),

            'boarding, 40m' => self::data(
                [
                    [
                        'Category' => [OffsetHandler::CATEGORY_PUSH],
                        'Offset' => self::secondsFromHours(1),
                        'Delay' => -self::secondsFromMinutes(20),
                        'Kind' => OffsetHandler::KIND_BOARDING,
                    ],
                ],
                [
                    OffsetHandler::CATEGORY_PUSH => [1],
                ],
                '2022-01-03 09:20',
                '2022-01-03 10:00',
            ),

            'boarding, 30m' => self::data(
                [
                    [
                        'Category' => [OffsetHandler::CATEGORY_PUSH],
                        'Offset' => self::secondsFromHours(1),
                        'Delay' => -self::secondsFromMinutes(30),
                        'Kind' => OffsetHandler::KIND_BOARDING,
                    ],
                ],
                [
                    OffsetHandler::CATEGORY_PUSH => [1],
                ],
                '2022-01-03 09:30',
                '2022-01-03 10:00',
            ),

            'boarding, -30m' => self::data(
                [],
                [
                    OffsetHandler::CATEGORY_PUSH => [1],
                ],
                '2022-01-03 10:30',
                '2022-01-03 10:00',
            ),

            'checkin, 23h, push + mail' => self::data(
                [
                    [
                        'Category' => [OffsetHandler::CATEGORY_PUSH, OffsetHandler::CATEGORY_MAIL],
                        'Offset' => self::secondsFromHours(24),
                        'Delay' => -self::secondsFromHours(1),
                        'Kind' => OffsetHandler::KIND_CHECKIN,
                    ],
                ],
                [
                    OffsetHandler::CATEGORY_PUSH => [24],
                    OffsetHandler::CATEGORY_MAIL => [24],
                ],
                '2022-01-02 11:00',
                '2022-01-03 10:00',
            ),

            'checkin, 23h, push + mail, diff offsets' => self::data(
                [
                    [
                        'Category' => [OffsetHandler::CATEGORY_PUSH],
                        'Offset' => self::secondsFromHours(24.25),
                        'Delay' => self::secondsFromHours(1.75),
                        'Kind' => OffsetHandler::KIND_CHECKIN,
                    ],
                    [
                        'Category' => [OffsetHandler::CATEGORY_MAIL],
                        'Offset' => self::secondsFromHours(24),
                        'Delay' => self::secondsFromHours(2),
                        'Kind' => OffsetHandler::KIND_CHECKIN,
                    ],
                ],
                [
                    OffsetHandler::CATEGORY_PUSH => [24.25],
                    OffsetHandler::CATEGORY_MAIL => [24],
                ],
                '2022-01-02 08:00',
                '2022-01-03 10:00',
            ),

            'checkin, 23h, push + mail, diff offsets, 2' => self::data(
                [
                    [
                        'Category' => [OffsetHandler::CATEGORY_PUSH],
                        'Offset' => self::secondsFromHours(24.25),
                        'Delay' => -self::secondsFromHours(19.25),
                        'Kind' => OffsetHandler::KIND_CHECKIN,
                    ],
                    [
                        'Category' => [OffsetHandler::CATEGORY_PUSH],
                        'Offset' => self::secondsFromHours(4),
                        'Delay' => self::secondsFromHours(1),
                        'Kind' => OffsetHandler::KIND_DEPARTURE,
                    ],
                    [
                        'Category' => [OffsetHandler::CATEGORY_MAIL],
                        'Offset' => self::secondsFromHours(24),
                        'Delay' => -self::secondsFromHours(19),
                        'Kind' => OffsetHandler::KIND_CHECKIN,
                    ],
                ],
                [
                    OffsetHandler::CATEGORY_PUSH => [4, 24.25],
                    OffsetHandler::CATEGORY_MAIL => [24],
                ],
                '2022-01-03 05:00',
                '2022-01-03 10:00',
            ),

            'no offsets' => self::data(
                [],
                [],
                '2022-01-01 08:00',
                '2022-01-02 10:00',
            ),
        ];
    }

    public function testGetOffsetsStatusesBySouthwest()
    {
        $this->db->updateInDatabase('Provider', [
            'CheckInReminderOffsets' => json_encode(OffsetHandler::getDefaultOffsets()),
        ], [
            'ProviderID' => 16,
        ]);
        $statuses = $this->offsetHandler->getOffsetsStatusesByProviderId(
            16,
            new \DateTime('2022-01-01 09:30'),
            new \DateTime('2022-01-02 10:00')
        );
        $this->assertCount(2, $statuses);
        $this->assertEquals([
            [
                'Category' => [OffsetHandler::CATEGORY_PUSH],
                'Offset' => self::secondsFromHours(24.25),
                'Delay' => 15 * 60,
                'Kind' => OffsetHandler::KIND_PRECHECKIN,
            ],
            [
                'Category' => [OffsetHandler::CATEGORY_PUSH, OffsetHandler::CATEGORY_MAIL],
                'Offset' => self::secondsFromHours(24),
                'Delay' => 30 * 60,
                'Kind' => OffsetHandler::KIND_CHECKIN,
            ],
        ], array_map(function (OffsetStatus $status) {
            return [
                'Category' => $status->getCategories(),
                'Offset' => $status->getOffset(),
                'Delay' => $status->getSendingDelay(),
                'Kind' => $status->getKind(),
            ];
        }, $statuses), var_export($statuses, true));
    }

    public function testGetSouthwestOffsets()
    {
        $offsets = $this->offsetHandler->getOffsets(16); // southwest
        $this->assertIsArray($offsets);
        $this->assertEquals(24.25, $offsets['push'][OffsetHandler::KIND_PRECHECKIN] ?? null);
    }

    public function testGetDefaultOffsets()
    {
        $offsets = OffsetHandler::getDefaultOffsets();
        $this->assertIsArray($offsets);
        $this->assertEquals(OffsetHandler::CATEGORIES, array_keys($offsets));
        $this->assertArrayHasKey(OffsetHandler::KIND_CHECKIN, $offsets[OffsetHandler::CATEGORY_PUSH]);
        $this->assertArrayHasKey(OffsetHandler::KIND_CHECKIN, $offsets[OffsetHandler::CATEGORY_MAIL]);
        $this->assertArrayHasKey(OffsetHandler::KIND_DEPARTURE, $offsets[OffsetHandler::CATEGORY_PUSH]);
        $this->assertArrayHasKey(OffsetHandler::KIND_BOARDING, $offsets[OffsetHandler::CATEGORY_PUSH]);
    }

    /**
     * @dataProvider getDeadlineDataProvider
     */
    public function testGetDeadline(int $expected, float $offset, array $providerOffsets)
    {
        $this->assertEquals($expected, $this->offsetHandler->getDeadline($offset, $providerOffsets));
    }

    public function getDeadlineDataProvider(): array
    {
        return [
            'checkin' => [
                self::secondsFromHours(3),
                24,
                [
                    OffsetHandler::KIND_CHECKIN => 24,
                    OffsetHandler::KIND_DEPARTURE => 4,
                ],
            ],

            'checkin 2' => [
                self::secondsFromHours(3),
                23,
                [
                    OffsetHandler::KIND_CHECKIN => 23,
                    OffsetHandler::KIND_DEPARTURE => 4,
                ],
            ],

            'departure' => [
                self::secondsFromHours(3),
                4,
                [
                    OffsetHandler::KIND_CHECKIN => 23,
                    OffsetHandler::KIND_DEPARTURE => 4,
                ],
            ],

            'departure 2' => [
                self::secondsFromHours(2),
                3,
                [
                    OffsetHandler::KIND_CHECKIN => 23,
                    OffsetHandler::KIND_DEPARTURE => 3,
                ],
            ],

            'boarding' => [
                self::secondsFromHours(0.5),
                1,
                [
                    OffsetHandler::KIND_CHECKIN => 23,
                    OffsetHandler::KIND_DEPARTURE => 3,
                    OffsetHandler::KIND_BOARDING => 1,
                ],
            ],

            'precheckin' => [
                self::secondsFromHours(24) + 60,
                24.25,
                [
                    OffsetHandler::KIND_PRECHECKIN => 24.25,
                    OffsetHandler::KIND_CHECKIN => 24,
                    OffsetHandler::KIND_DEPARTURE => 3,
                    OffsetHandler::KIND_BOARDING => 1,
                ],
            ],
        ];
    }

    private static function secondsFromHours(float $hours): int
    {
        return ceil($hours * 3600);
    }

    private static function secondsFromMinutes(float $minutes): int
    {
        return ceil($minutes * 60);
    }

    private static function data(
        array $expected,
        array $offsets,
        string $now,
        string $depDate
    ): array {
        return [
            $expected,
            $offsets,
            $now,
            $depDate,
        ];
    }
}
