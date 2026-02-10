<?php

namespace AwardWallet\MainBundle\Timeline;

use AwardWallet\MainBundle\Globals\CollectionUtils;
use AwardWallet\MainBundle\Globals\StringHandler;

class SegmentMapUtils
{
    /**
     * @param SegmentMapItem[] $items
     * @param string|string[] $type
     * @return int[]
     */
    public static function filterIdsByType($items, $type)
    {
        if (!is_array($type)) {
            $type = [$type];
        }

        $acc = [];

        foreach ($items as $item) {
            if (in_array($item['type'], $type, true)) {
                $acc[] = (int) $item['id'];
            }
        }

        return $acc;
    }

    /**
     * Use cases:
     *   * load all:           no start, no end, no limit
     *   * load all until:     no start,    end, no limit
     *   * load chunk until:   no start,    end,    limit
     *   * load all between:      start,    end, no limit
     *   * load chunk between:    start,    end,    limit // start bound may be epxanded in past to fill available slot.
     *
     * @param SegmentMapItem[] $segments
     * @param int $limit
     * @param bool $showDeleted
     * @param string $shareId
     * @return \Traversable<SegmentMapItem>|SegmentMapItem[]
     */
    public static function getChunk($segments, ?\DateTime $startDate = null, ?\DateTime $endDate = null, $limit = null, $showDeleted = false, $shareId = null)
    {
        // TODO: handle fast(count($map) < 10) execution path?

        if (!$segments) {
            return [];
        }

        if (!$startDate && !$endDate && !$limit && StringHandler::isEmpty($shareId)) {
            // load all\
            return $segments;
        }

        if (!$endDate && $limit) {
            throw new \InvalidArgumentException('You can not load chunk in forward direction, specify endDate');
        }

        $endDateShifted = null;
        $endDateSafe = null;

        if ($endDate) {
            $base = \DateTimeImmutable::createFromMutable($endDate);
            // mitigate timzone shift
            $endDateShifted = $base->add(new \DateInterval('P1D'));
            $endDateSafe = $base->sub(new \DateInterval('P1D'));
        }

        $startDateShifted = null;
        $startDateSafe = null;

        // expand time boundaries symmetrically
        if ($startDate) {
            // mitigate timzone shift
            $base = \DateTimeImmutable::createFromMutable($startDate);
            $startDateShifted = $base->sub(new \DateInterval('P1D'));
            $startDateSafe = $base->add(new \DateInterval('P1D'));

            if ($endDateShifted && ($startDateShifted->getTimestamp() > $endDateShifted->getTimestamp())) {
                return [];
            }
        }

        // iterate over timeline from end
        $segmentsBackward = CollectionUtils::reverse($segments);

        if ($endDateShifted) {
            // iterate over reversed timeline map to cut from nearest segment to upper date
            $segmentsBackward = CollectionUtils::dropWhile(
                $segmentsBackward,
                function (/** @var SegmentMapItem $item */ &$item) use ($endDateShifted) {
                    return $item['startDate']->getTimestamp() > $endDateShifted->getTimestamp();
                }
            );
        }

        // skip deleted segments if needed
        $itemsProvider = $showDeleted ? $segmentsBackward :
            CollectionUtils::filter($segmentsBackward, function (/** @var SegmentMapItem $item */ &$item) {
                return !$item['deleted'];
            });

        // filter by sharing key
        if (null !== $shareId) {
            $itemsProvider = CollectionUtils::filter(
                $itemsProvider,
                function (/** @var SegmentMapItem $item */ &$item) use ($shareId) {
                    return $shareId === $item['shareId'];
                }
            );
        }

        // grab older segments with limit and additional segments shifted
        // by 2 days from first and last "limited" segment
        $lastItemDate = null;

        return CollectionUtils::takeWhile(
            $itemsProvider,
            function (/** @var SegmentMapItem $item */ &$item) use (&$limit, $startDateSafe, $endDateSafe, &$startDateShifted, &$endDateShifted, &$lastItemDate) {
                $itemDate = $item['startDate'];

                $itemInSafeInterval =
                    ($isRightSafe = ($endDateSafe ? $itemDate->getTimestamp() < $endDateSafe->getTimestamp() : true))
                                    && ($startDateSafe ? $itemDate->getTimestamp() > $startDateSafe->getTimestamp() : true);

                $itemInShiftedInterval = ($startDateShifted ? $itemDate->getTimestamp() >= $startDateShifted->getTimestamp() : true);

                if (null === $limit) {
                    // load all between,
                    // load all until

                    return $itemInShiftedInterval;
                } else {
                    // load chunk until
                    // load chunk between

                    if ($itemInSafeInterval && $limit > 0) {
                        $limit--;
                        $lastItemDate = $itemDate;

                        return true;
                    } else {
                        if ($limit > 0) {
                            if ($isRightSafe) {
                                $limit--;
                            }

                            $lastItemDate = $itemDate;

                            return true;
                        } elseif (
                            0 === $limit
                            && (!$itemInShiftedInterval || !$startDateShifted)
                        ) {
                            // shift by two day in the past to mitigate possible timezone shift
                            $startDateShifted = clone $lastItemDate;
                            $startDateShifted->sub(new \DateInterval('P1D'));
                            // special value to indicate last mile items
                            $limit = -1;

                            return $itemDate->getTimestamp() >= $startDateShifted->getTimestamp();
                        } else {
                            $lastItemDate = $itemDate;

                            return $itemInShiftedInterval;
                        }
                    }
                }
            }
        );
    }

    public static function getChunkByOptions($segments, QueryOptions $queryOptions)
    {
        $sharedPlan = $queryOptions->getSharedPlan();

        $chunk = self::getChunk(
            $segments,
            $sharedPlan ? $sharedPlan->getStartDate() : $queryOptions->getStartDate(),
            $sharedPlan ? $sharedPlan->getEndDate() : $queryOptions->getEndDate(),
            $queryOptions->getMaxSegments(),
            $queryOptions->isShowDeleted(),
            $queryOptions->getShareId(),
        );

        return CollectionUtils::toArray($chunk);
    }
}
