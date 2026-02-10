<?php

namespace AwardWallet\Tests\Unit\Timeline;

use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Globals\Features\FeaturesBitSet;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\AlternativeFlightsUtils;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Menu\AlternativeFlight;
use AwardWallet\MainBundle\Timeline\Item\AirTrip;
use AwardWallet\Tests\Unit\BaseTest;

use function Codeception\Module\Utils\Reflection\setObjectProperty;

/**
 * @group frontend-unit
 */
class AlternativeFlightUtilsTest extends BaseTest
{
    /**
     * @dataProvider getCyclesDataProvider
     */
    public function testGetCycles($points, $cycles)
    {
        $utils = new AlternativeFlightsUtils($this->prophesize(LocalizeService::class)->reveal());
        $this->assertEquals($cycles, $utils::getCycles($points));
    }

    public function getCyclesDataProvider()
    {
        return [
            [
                [
                    ['FLL', 'JFK'], // 0
                    ['JFK', 'LAX'], // 1
                    ['LAX', 'SFX'], // 2
                    ['SFX', 'JFK'], // 3
                    ['JFK', 'PHX'], // 4
                    ['PHX', 'FLL'], // 5
                ],
                [
                    [[1, 3], [2, 3]], // JFK<>
                    [[0, 5], [2, 3, 4]], // FLL<>
                ],
            ],
            [
                [
                    ['FLL', 'PEE'], // 0
                    ['PEE', 'JFK'], // 1
                    ['JFK', 'LAX'], // 2
                    ['LAX', 'SFX'], // 3
                    ['SFX', 'SVO'], // 4
                    ['SVO', 'JFK'], // 5
                    ['JFK', 'SFX'], // 6
                    ['AAP', 'AAP'], // 7
                    ['SFX', 'JFK'], // 8
                    ['JFK', 'PHX'], // 9
                    ['PHX', 'FLL'], // 10
                ],
                [
                    [[2, 5], [3, 4, 5]], // JFK<>
                    [[4, 6], [5, 6]], // SFX<>
                    [[6, 8], [8]], // JFK<>
                    [[0, 10], [4, 5, 6]], // FLL<>
                ],
            ],
            [
                [
                    ['FLL', 'LAX'], // 0
                    ['LAX', 'SFX'], // 1
                    ['SFX', 'FLL'], // 2
                ],
                [
                    [[0, 2], [1, 2]],
                ],
            ],
            [
                [
                    ['FLL', 'LAX'], // 0
                    ['LAX', 'SFX'], // 1
                ],
                [],
            ],
            [
                [
                    ['FLL', 'LAX'],
                    ['PHX', 'FLL'],
                ],
                [],
            ],
            [
                [
                    ['FLL', 'LAX'], // 0
                    ['PHX', 'FLL'], // 1
                    ['FLL', 'JFK'], // 2
                    ['JFK', 'FLL'], // 3
                ],
                [
                    [[2, 3], [3]], // FLL<>
                ],
            ],
            [
                [
                    ['AAP', 'AAP'],
                    ['AAP', 'AAP'],
                ],
                [],
            ],            [
                [
                    ['AAP', 'AAP'],
                ],
                [],
            ],
        ];
    }

    /**
     * @dataProvider buildDataProvider
     * @param AirTrip[] $items
     * @param array $expectedAltFlights
     */
    public function testBuild($items, $expectedAltFlights)
    {
        $utils = new AlternativeFlightsUtils($this->prophesize(LocalizeService::class)->reveal());
        $utils->schedule($items, new FeaturesBitSet(0));
        $atLeastOneScheduled = false;

        foreach ($items as $i => $item) {
            $altFlights = $item->getTripAlternatives();

            if (!isset($altFlights)) {
                continue;
            }

            $atLeastOneScheduled = true;

            $actualAltFlights = array_map(function (AlternativeFlight $alternativeFlight) {
                return $alternativeFlight->points;
            },
                array_merge((array) $altFlights->main, (array) $altFlights->extra)
            );

            $this->assertEquals($expectedAltFlights[$i], $actualAltFlights, "mismatching segment #{$i} in data set");
        }

        if ($expectedAltFlights && !$atLeastOneScheduled) {
            $this->assertEquals($expectedAltFlights, [], "no scheduled trips");
        }
    }

    public function buildDataProvider()
    {
        return [
            [   // simple case with round trip
                $this->createSegments('CONFNO1', [
                    ['JFK', 'LAX'],
                    ['LAX', 'JFK'],
                ]),
                [
                    0 => [
                        ['JFK', 'LAX'],
                        ['JFK', 'LAX', 'JFK'],
                    ],
                    1 => [
                        ['LAX', 'JFK'],
                    ],
                ],
            ],
            [  // nested round-trips with simple segments
                $this->createSegments('CONFNO2', [
                    ['FLL', 'JFK'], // 0
                    ['JFK', 'LAX'], // 0 1
                    ['LAX', 'SFX'], // 0 1
                    ['SFX', 'JFK'], // 0 1
                    ['JFK', 'PHX'], // 0
                    ['PHX', 'FLL'], // 0
                    ['FLL', 'PEE'],
                    ['PEE', 'SVO'],
                    ['SVO', 'FRL'],
                ]),
                [
                    0 => [
                        ['FLL', 'JFK'],
                        ['FLL', 'LAX', 'FLL'],
                        ['FLL', 'SFX', 'FLL'],
                        ['FLL', 'JFK', 'FLL'],
                        ['FLL', 'JFK', 'FLL'], // diffent dates, TODO: check dates
                        ['FLL', 'FRL'],
                    ],
                    1 => [
                        ['JFK', 'LAX'],
                        ['JFK', 'LAX', 'JFK'],
                        ['JFK', 'SFX', 'JFK'],
                        ['JFK', 'LAX', 'FLL'],
                        ['JFK', 'SFX', 'FLL'],
                        ['JFK', 'FLL'],
                        ['JFK', 'FRL'],
                    ],
                    2 => [
                        ['LAX', 'SFX'],
                        ['LAX', 'SFX', 'JFK'],
                        ['LAX', 'JFK'],
                        ['LAX', 'SFX', 'FLL'],
                        ['LAX', 'JFK', 'FLL'],
                        ['LAX', 'FLL'],
                        ['LAX', 'FRL'],
                    ],
                    3 => [
                        ['SFX', 'JFK'],
                        ['SFX', 'JFK', 'FLL'],
                        ['SFX', 'FLL'],
                        ['SFX', 'FRL'],
                    ],
                    4 => [
                        ['JFK', 'PHX'],
                        ['JFK', 'FLL'],
                        ['JFK', 'PHX', 'FLL'],
                        ['JFK', 'FRL'],
                    ],
                    5 => [
                        ['PHX', 'FLL'],
                        ['PHX', 'FRL'],
                    ],
                    6 => [
                        ['FLL', 'PEE'],
                        ['FLL', 'FRL'],
                    ],
                    7 => [
                        ['PEE', 'SVO'],
                        ['PEE', 'FRL'],
                    ],
                    8 => [
                        ['SVO', 'FRL'],
                    ],
                ],
            ],
            [
                $this->createSegments(null, [
                    ['FLL', 'JFK'],
                ]),
                [
                    [
                        ['FLL', 'JFK'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param string $confNo
     * @return array
     */
    protected function createSegments($confNo, array $points)
    {
        $items = [];

        $baseDate = new \DateTime('Jan 1 2000');

        foreach ($points as [$dep, $arr]) {
            $baseDate = clone $baseDate;
            $baseDate->setTimestamp($baseDate->getTimestamp() + SECONDS_PER_HOUR * 3);
            $items[] = $this->createSegment($confNo, $dep, $arr, $baseDate);
        }

        return $items;
    }

    /**
     * @param string $confNo
     * @param string $dep
     * @param string $arr
     * @param \DateTimeInterface $dateTime
     * @return AirTrip
     */
    protected function createSegment($confNo, $dep, $arr, \DateTime $dateTime)
    {
        $endDate = clone $dateTime;
        $endDate->setTimestamp($endDate->getTimestamp() + 3600);

        $trip = new Trip();
        $trip->setCategory(Trip::CATEGORY_AIR);
        $ts = (new Tripsegment())
            ->setDepcode($dep)
            ->setArrcode($arr)
            ->setDepartureDate($dateTime)
            ->setArrivalDate($endDate);
        $ts->setTripid($trip);
        setObjectProperty($ts, 'tripsegmentid', 1);

        $item = new AirTrip($ts);
        $item->setConfNo($confNo);

        return $item;
    }
}
