<?php

namespace AwardWallet\Tests\Unit\Timeline;

use AwardWallet\MainBundle\Timeline\SegmentMapItem;
use AwardWallet\MainBundle\Timeline\SegmentMapUtils;
use AwardWallet\Tests\Unit\BaseTest;

use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertEquals;

/**
 * @group frontend-unit
 */
class SegmentMapUtilsTest extends BaseTest
{
    public function testFutureChunks()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('You can not load chunk in forward direction, specify endDate');
        SegmentMapUtils::getChunk($this->createVisibleItems(new \DateTime('Jan 1 2000'), 20), new \DateTime(), null, 1);
    }

    public function testLoadAll()
    {
        $this->assertItemsBounds(
            SegmentMapUtils::getChunk($this->createVisibleItems(new \DateTime('Jan 1 2000'), 10)),
            new \DateTime('Jan 1 2000'),
            new \DateTime('Jan 10 2000'),
            10
        );
    }

    public function testInvalidInterval()
    {
        assertEquals(
            [],
            SegmentMapUtils::getChunk($this->createVisibleItems(new \DateTime('Jan 1 2000'), 10), new \DateTime('Jan 5 2000'), new \DateTime('Jan 2 2000'))
        );
    }

    public function testChunkBetweenWithSegments1DayInterval()
    {
        $items = $this->createVisibleItems(new \DateTime('Jan 1 2000'), 20);

        // load all future despite the limit
        $this->assertItemsBounds(
            SegmentMapUtils::getChunk($items, new \DateTime('Jan 10 2000'), new \DateTime('Jan 21 2000'), 1),
            new \DateTime('Jan 8 2000'),
            new \DateTime('Jan 20 2000'),
            13
        );

        $this->assertItemsBounds(
            SegmentMapUtils::getChunk($items, new \DateTime('Jan 10 2000'), new \DateTime('Jan 21 2000'), 11),
            new \DateTime('Jan 8 2000'),
            new \DateTime('Jan 20 2000'),
            13
        );

        $this->assertItemsBounds(
            SegmentMapUtils::getChunk($items, new \DateTime('Jan 10 2000'), new \DateTime('Jan 21 2000'), 12),
            new \DateTime('Jan 7 2000'),
            new \DateTime('Jan 20 2000'),
            14
        );

        $this->assertItemsBounds(
            SegmentMapUtils::getChunk($items, new \DateTime('Jan 10 2000'), new \DateTime('Jan 21 2000'), 13),
            new \DateTime('Jan 6 2000'),
            new \DateTime('Jan 20 2000'),
            15
        );
    }

    public function testChunkBetweenWithSegments7DayInterval()
    {
        $items = $this->createVisibleItems(new \DateTime('Jan 1 2000'), 10, 7);

        $this->assertItemsBounds(
            SegmentMapUtils::getChunk($items, new \DateTime('Feb 19 2000'), new \DateTime('Apr 1 2000'), 2),
            new \DateTime('Feb 19 2000'),
            new \DateTime('Mar 4 2000'),
            3
        );

        $this->assertItemsBounds(
            SegmentMapUtils::getChunk($items, new \DateTime('Feb 19 2000'), new \DateTime('Apr 1 2000'), 6),
            new \DateTime('Jan 29 2000'),
            new \DateTime('Mar 4 2000'),
            6
        );

        $withPast = array_merge($this->createVisibleItems(new \DateTime('Jan 1 1999'), 10, 7), $items);

        $this->assertItemsBounds(
            SegmentMapUtils::getChunk($withPast, new \DateTime('Feb 19 2000'), new \DateTime('Apr 1 2000'), 50),
            new \DateTime('Jan 1 1999'),
            new \DateTime('Mar 4 2000'),
            20
        );
    }

    public function testChunkBeforeWithSegments1DayInterval()
    {
        $items = $this->createVisibleItems(new \DateTime('Jan 1 2000'), 30);

        $this->assertItemsBounds(
            SegmentMapUtils::getChunk($items, null, new \DateTime('Jan 25 2000'), 10),
            new \DateTime('Jan 13 2000'),
            new \DateTime('Jan 26 2000'),
            14
        );

        $this->assertItemsBounds(
            SegmentMapUtils::getChunk($items, null, new \DateTime('Jan 14 2000'), 10),
            new \DateTime('Jan 02 2000'),
            new \DateTime('Jan 15 2000'),
            14
        );

        $this->assertItemsBounds(
            SegmentMapUtils::getChunk($items, null, new \DateTime('Jan 03 2000'), 10),
            new \DateTime('Jan 01 2000'),
            new \DateTime('Jan 04 2000'),
            4
        );
    }

    public function testChunkBeforeWithSegments7DayInterval()
    {
        $items = $this->createVisibleItems(new \DateTime('Jan 1 2000'), 10, 7);

        $this->assertItemsBounds(
            SegmentMapUtils::getChunk($items, null, new \DateTime('Apr 1 2000'), 10),
            new \DateTime('Jan 1 2000'),
            new \DateTime('Mar 4 2000'),
            10
        );

        $this->assertItemsBounds(
            SegmentMapUtils::getChunk($items, null, new \DateTime('Apr 1 2000'), 3),
            new \DateTime('Feb 19 2000'),
            new \DateTime('Mar 4 2000'),
            3
        );
    }

    /**
     * @param \Traversable|SegmentMapItem[] $items
     */
    protected function assertItemsBounds($items, \DateTime $expectedStart, \DateTime $expectedEnd, $count)
    {
        $items = is_array($items) ? $items : iterator_to_array($items);

        assertCount($count, $items);

        $bound1 = $items[0]['startDate'];
        $bound2 = end($items)['startDate'];

        // detect order of items
        if ($bound1 <= $bound2) {
            $actualStart = $bound1;
            $actualEnd = $bound2;
        } else {
            $actualStart = $bound2;
            $actualEnd = $bound1;
        }

        assertEquals($expectedStart, $actualStart, 'wrong bottom date');
        assertEquals($expectedEnd, $actualEnd, 'wrong upper date');
    }

    /**
     * @return SegmentMapItem[]
     */
    protected function createItem(\DateTime $startDate, $deleted = false)
    {
        return [
            'startDate' => $startDate,
            'deleted' => $deleted,
        ];
    }

    /**
     * @param int $count
     * @param int $interval
     * @return \AwardWallet\MainBundle\Timeline\SegmentMapItem[]
     */
    protected function createVisibleItems(\DateTime $baseDate, $count, $interval = 1)
    {
        $items = [];

        foreach (range(1, $count) as $_) {
            $items[] = $this->createItem($baseDate);
            $baseDate = clone $baseDate;
            $baseDate->add(new \DateInterval("P{$interval}D"));
        }

        return $items;
    }
}
