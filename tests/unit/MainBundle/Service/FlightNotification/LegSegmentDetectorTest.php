<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\FlightNotification;

use AwardWallet\MainBundle\Entity\Repositories\TripsegmentRepository;
use AwardWallet\MainBundle\Service\FlightNotification\LegSegmentDetector;
use AwardWallet\Tests\Modules\DbBuilder\Trip;
use AwardWallet\Tests\Modules\DbBuilder\TripSegment;
use AwardWallet\Tests\Modules\DbBuilder\User;
use AwardWallet\Tests\Unit\BaseContainerTest;

/**
 * @group frontend-unit
 */
class LegSegmentDetectorTest extends BaseContainerTest
{
    private ?LegSegmentDetector $legSegmentDetector;

    private ?TripsegmentRepository $tsRep;

    public function _before()
    {
        parent::_before();

        $this->legSegmentDetector = $this->container->get(LegSegmentDetector::class);
        $this->tsRep = $this->container->get(TripsegmentRepository::class);
    }

    public function _after()
    {
        parent::_after();

        $this->legSegmentDetector = null;
        $this->tsRep = null;
    }

    /**
     * @dataProvider isLegSegmentDataProvider
     */
    public function testIsLegSegment(bool $expected, array $tripsegments, TripSegment $targetTripsegment, float $offset)
    {
        foreach ($tripsegments as $tripsegment) {
            $this->dbBuilder->makeTripSegment($tripsegment);
        }

        $this->assertEquals($expected, $this->legSegmentDetector->isLegSegment($this->tsRep->find($targetTripsegment->getId()), $offset));
    }

    public function isLegSegmentDataProvider()
    {
        return [
            'one segment' => [
                true,
                [
                    $ts = new TripSegment(
                        'PEE',
                        'PEE',
                        new \DateTimeImmutable('2020-01-01 00:00:00'),
                        'DME',
                        'DME',
                        new \DateTimeImmutable('2020-01-01 02:00:00'),
                        new Trip('AAA', [], new User())
                    ),
                ],
                $ts,
                4,
            ],
            'two segments, not leg' => [
                false,
                [
                    $ts = new TripSegment(
                        'PEE',
                        'PEE',
                        new \DateTimeImmutable('2020-01-01 00:00:00'),
                        'DME',
                        'DME',
                        new \DateTimeImmutable('2020-01-01 02:00:00'),
                        new Trip('AAA', [
                            new TripSegment(
                                'OVB',
                                'OVB',
                                new \DateTimeImmutable('2019-12-31 21:00:00'),
                                'PEE',
                                'PEE',
                                new \DateTimeImmutable('2019-12-31 23:00:00'),
                            ),
                        ], new User())
                    ),
                ],
                $ts,
                4,
            ],
            'two segments, leg' => [
                true,
                [
                    $ts = new TripSegment(
                        'PEE',
                        'PEE',
                        new \DateTimeImmutable('2020-01-01 00:00:00'),
                        'DME',
                        'DME',
                        new \DateTimeImmutable('2020-01-01 02:00:00'),
                        new Trip('AAA', [
                            new TripSegment(
                                'OVB',
                                'OVB',
                                new \DateTimeImmutable('2019-12-31 18:00:00'),
                                'PEE',
                                'PEE',
                                new \DateTimeImmutable('2019-12-31 19:00:00'),
                            ),
                        ], new User())
                    ),
                ],
                $ts,
                4,
            ],
            'two trips, leg' => [
                true,
                [
                    $ts = new TripSegment(
                        'PEE',
                        'PEE',
                        new \DateTimeImmutable('2020-01-01 00:00:00'),
                        'DME',
                        'DME',
                        new \DateTimeImmutable('2020-01-01 02:00:00'),
                        new Trip('AAA', [], $user = new User())
                    ),
                    new TripSegment(
                        'OVB',
                        'OVB',
                        new \DateTimeImmutable('2019-12-31 20:00:00'),
                        'PEE',
                        'PEE',
                        new \DateTimeImmutable('2019-12-31 22:00:00'),
                        new Trip('BBB', [], $user)
                    ),
                ],
                $ts,
                4,
            ],
        ];
    }
}
