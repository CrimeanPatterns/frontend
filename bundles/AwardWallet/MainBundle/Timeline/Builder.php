<?php

namespace AwardWallet\MainBundle\Timeline;

use AwardWallet\Common\Entity\Geotag;
use AwardWallet\MainBundle\Entity\Plan;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\CollectionUtils;
use AwardWallet\MainBundle\Service\RA\Flight\FlightDealSubscriber;
use AwardWallet\MainBundle\Timeline\FilterCallback\FilterCallbackInterface;
use AwardWallet\MainBundle\Timeline\FilterCallback\PassingFilterCallback;
use AwardWallet\MainBundle\Timeline\Item\AbstractItinerary;
use AwardWallet\MainBundle\Timeline\Item\AirTrip;
use AwardWallet\MainBundle\Timeline\Item\CanCreatePlanInterface;
use AwardWallet\MainBundle\Timeline\Item\Checkin;
use AwardWallet\MainBundle\Timeline\Item\Checkout;
use AwardWallet\MainBundle\Timeline\Item\CruiseTrip;
use AwardWallet\MainBundle\Timeline\Item\Date;
use AwardWallet\MainBundle\Timeline\Item\ItemInterface;
use AwardWallet\MainBundle\Timeline\Item\ItineraryInterface;
use AwardWallet\MainBundle\Timeline\Item\LayoverBoundaryInterface;
use AwardWallet\MainBundle\Timeline\Item\LayoverInterface;
use AwardWallet\MainBundle\Timeline\Item\PlanEnd;
use AwardWallet\MainBundle\Timeline\Item\PlanStart;
use Clock\ClockInterface;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class Builder
{
    private EntityManagerInterface $em;
    private ClockInterface $clock;
    private Connection $connection;

    public function __construct(
        EntityManagerInterface $em,
        ClockInterface $clock, Connection $connection
    ) {
        $this->em = $em;
        $this->clock = $clock;
        $this->connection = $connection;
    }

    /**
     * @param ItemInterface[] $items
     * @return ItemInterface[]
     */
    public function build(array $items, QueryOptions $queryOptions)
    {
        $this->sortByStartDate($items);
        $this->moveCheckoutsBeforeFlight($items);
        $this->moveCheckinsAfterFlight($items);

        if ($queryOptions->getShowPlans() && !$queryOptions->isBareSegments()) {
            $items = \array_merge(
                $items,
                (new PlanItemQuery($this->em))
                    ->getTimelineItems($queryOptions->getUser(), $queryOptions)
            );
        }

        $this->sortByStartDate($items);

        if (($sharedPlan = $queryOptions->getSharedPlan()) && !$queryOptions->isBareSegments()) {
            $items = $this->filterByTravelPlan($items, $sharedPlan);
        }

        if (!$queryOptions->isBareSegments()) {
            $items = $this->addLayovers($items);
        }

        // DO NOT MOVE UP\DOWN WITHOUT CAREFUL REASONING ABOUT EXISTING USAGE SITES
        /** @var FilterCallbackInterface $filterCallback */
        if (
            !$queryOptions->isBareSegments()
            && !empty($filterCallback = $queryOptions->getFilterCallback())
            && !($filterCallback instanceof PassingFilterCallback)
        ) {
            $items =
                it($items)
                ->filter($filterCallback->getCallback())
                ->toArray();
        }

        if (!$queryOptions->isBareSegments()) {
            $items = $this->addDates($items);

            if ($queryOptions->isShowPlans()) {
                $items = $this->calculatePlansDurations($items);
            }

            $items = $this->filterByDates(
                $items,
                $queryOptions->getStartDate(),
                $queryOptions->getEndDate(),
                (bool) $queryOptions->getFuture()
            );
        }

        if ($queryOptions->isShowPlans() && !$queryOptions->isBareSegments()) {
            $this->fillPlansInfo($items, $queryOptions->isShowDeleted());
        }

        $this->addPriceDropMonitoringIndicator($items, $queryOptions->getUser());

        if ($queryOptions->hasMaxSegments() && !$queryOptions->isBareSegments()) {
            $items = $this->sliceMaxSegments(
                $items,
                $queryOptions->getMaxSegments(),
                $queryOptions->hasEndDate()
            );
        }

        return $items;
    }

    public static function getNights(\DateTime $start, \DateTime $end): int
    {
        if ($end <= $start || ($end->getTimestamp() - $start->getTimestamp()) <= 3 * 3600) {
            return 0;
        }

        $boundaryDate = (clone $start)->setTime(4, 0);
        $nights = $boundaryDate >= $start ? 1 : 0;
        $days = ($end->getTimestamp() - $boundaryDate->getTimestamp()) / (60 * 60 * 24);
        $nights += $days;

        return $nights > 30 ? 0 : $nights;
    }

    /**
     * @param Item\ItemInterface[] $items
     * @return Item\ItemInterface[]
     */
    private function addLayovers(array $items)
    {
        $result = [];

        foreach ($items as $item) {
            /** @var $segment Tripsegment */
            /** @var $lastSegment Tripsegment */
            /** @var $lastItem Item\ItemInterface */
            if (!($item instanceof Item\AbstractTrip) || !($item instanceof Item\LayoverBoundaryInterface) || !($segment = $item->getSource()) || !($segment instanceof Tripsegment)) {
                $lastSegment = null;
            } else {
                $trip = $segment->getTripid();

                if (!$segment->getHidden() && !$trip->getHidden()) {
                    if (!empty($lastSegment)) {
                        if (
                            !$item instanceof CruiseTrip
                            && !empty($segment->getDepcode()) && $segment->getDepcode() === $lastSegment->getArrcode()
                            // do not insert layovers at round-trips
                            && !empty($segment->getArrcode()) && !empty($lastSegment->getDepcode()) && $segment->getArrcode() !== $lastSegment->getDepcode()
                        ) {
                            if ($item instanceof AirTrip) {
                                $layover = new Item\AirLayover($lastItem, $item);
                            } else {
                                $layover = new Item\Layover($lastItem, $item);
                            }

                            $duration = $layover->getDuration();

                            if ($duration->days < 1 && $duration->invert) {
                                $lastItem->setLayoverBoundaryType(Item\LayoverBoundaryInterface::LAYOVER_TYPE_START);
                                $item->setLayoverBoundaryType(Item\LayoverBoundaryInterface::LAYOVER_TYPE_END);
                                $layover->getContext()->setPrevStartDate($lastItem->getStartDate());
                                $result[] = $layover;
                            }
                        }

                        // todo: add check of distance by coordinates
                        if (
                            $item instanceof CruiseTrip
                            && $lastItem instanceof CruiseTrip
                            && $lastSegment->getTripid()->getId() === $trip->getId()
                        ) {
                            $layover = new Item\CruiseLayover($lastItem, $item);
                            $duration = $layover->getDuration();

                            if ($duration->invert) {
                                $lastItem->setLayoverBoundaryType(Item\LayoverBoundaryInterface::LAYOVER_TYPE_START);
                                $item->setLayoverBoundaryType(Item\LayoverBoundaryInterface::LAYOVER_TYPE_END);
                                $layover->getContext()->setPrevStartDate($lastItem->getStartDate());
                                $result[] = $layover;
                            }
                        }
                    }

                    $lastSegment = $segment;
                    $lastItem = $item;
                }
            }
            $result[] = $item;
        }

        return $result;
    }

    /**
     * @param ItemInterface[] $items
     * @param int $maxSegments
     * @param bool $startFromEnd
     * @return ItemInterface[]
     */
    private function sliceMaxSegments(array $items, $maxSegments, $startFromEnd)
    {
        if ($maxSegments <= 0) {
            return [];
        }

        if (count($items) > $maxSegments) {
            if ($startFromEnd) {
                $isWaitingForLayoverPair = false;
                $isEnclosingDateCaptured = false;
                $slotsLeft = $maxSegments;

                $items = array_reverse(CollectionUtils::toArray(CollectionUtils::takeWhile(
                    CollectionUtils::reverse($items),
                    function (ItemInterface $item) use (&$slotsLeft, &$isWaitingForLayoverPair, &$isEnclosingDateCaptured) {
                        if ($isEnclosingDateCaptured) {
                            if ($item instanceof PlanStart) {
                                return true;
                            }

                            return false;
                        }

                        $isRealSegment = !($item instanceof Date) && !($item instanceof LayoverInterface);

                        // capture airsegment-layover-airsegment chains
                        if ($item instanceof LayoverBoundaryInterface) {
                            $slotsLeft--;

                            if ($item->isLayoverBoundaryType(LayoverBoundaryInterface::LAYOVER_TYPE_END)) {
                                $isWaitingForLayoverPair = true;
                            } elseif ($isWaitingForLayoverPair && $item->isLayoverBoundaryType(LayoverBoundaryInterface::LAYOVER_TYPE_START)) {
                                $isWaitingForLayoverPair = false;
                            }

                            return true;
                        }

                        if ($isWaitingForLayoverPair) {
                            if ($isRealSegment) {
                                $slotsLeft--;
                            }

                            return true;
                        }

                        if ($slotsLeft <= 0) {
                            if ($item instanceof Date) {
                                $isEnclosingDateCaptured = true;

                                return true;
                            } elseif (!$isRealSegment) {
                                return false;
                            }
                        } elseif ($isRealSegment) {
                            $slotsLeft--;
                        }

                        return $slotsLeft >= 0 || !$isEnclosingDateCaptured;
                    }
                )));
            } else {
                for ($n = $maxSegments; $n < count($items); $n++) {
                    $item = $items[$n];

                    if ($item instanceof Date) {
                        $items = array_slice($items, 0, $n);

                        break;
                    }
                }
            }
        }

        return $items;
    }

    /**
     * @param ItemInterface[] $items
     * @return ItemInterface[]
     */
    private function filterByDates(array $items, ?\DateTime $startDate = null, ?\DateTime $endDate = null, bool $isFuture = false)
    {
        // cut by nearest day start
        // we will always return full days
        // this function expects two days around requested intervals in source data, see Manager->loadSegments
        if (!empty($startDate)) {
            $stamp = $startDate->getTimestamp();

            for ($n = count($items) - 1; $n >= 0; $n--) {
                $item = $items[$n];

                if ($item instanceof Date && $item->getStartDate()->getTimestamp() <= $stamp) {
                    $offset = $n;

                    if ($n > 0 && $items[$n - 1] instanceof PlanStart) {
                        $offset = $n - 1;
                    }
                    $items = array_slice($items, $offset);

                    break;
                }
            }
        }

        if (!empty($endDate) && !$isFuture) {
            $stamp = $endDate->getTimestamp();

            for ($n = 0; $n < count($items); $n++) {
                $item = $items[$n];

                if (($item instanceof Date || $item instanceof PlanStart) && $item->getStartDate()->getTimestamp() >= $stamp) {
                    $items = array_slice($items, 0, $n);

                    break;
                }
            }
        }

        return $items;
    }

    /**
     * @param ItemInterface[] $items
     */
    private function sortByStartDate(array &$items)
    {
        usort($items, fn (ItemInterface $a, ItemInterface $b) =>
            $a->getStartDate()->getTimestamp() <=> $b->getStartDate()->getTimestamp()
            ?: $a->getId() <=> $b->getId()
        );
    }

    /**
     * @param ItemInterface[] $items
     */
    private function moveCheckoutsBeforeFlight(array &$items)
    {
        foreach ($items as $offset => $item) {
            if (!$item instanceof AbstractItinerary) {
                continue;
            }

            $source = $item->getSource();

            if (
                $source instanceof Tripsegment
                && !empty($source->getDepgeotagid())
                && intval($source->getDepartureDate()->format("H")) < 15
            ) {
                $startDate = clone $item->getStartDate();
                $startDate->modify('-3 hour');
                $endDate = clone $item->getStartDate();
                $endDate->modify('15:00');
                $nearCheckouts = $this->searchNear($items, $startDate, $endDate);
                /** @var Checkout[] $nearCheckouts */
                $nearCheckouts = array_filter($nearCheckouts, function (ItemInterface $segment) {
                    return $segment instanceof Checkout && !empty($segment->getSource()->getGeotagid());
                });

                foreach ($nearCheckouts as &$checkout) {
                    $diff = $startDate->diff($checkout->getStartDate());
                    $checkout->getLocalDate()->sub($diff);
                    $checkout->setStartDate($startDate);
                }
            }
        }
    }

    /**
     * @param ItemInterface[] $items
     */
    private function moveCheckinsAfterFlight(array &$items)
    {
        /*
         * AIR: +1 hour after arrival
         * TRAIN: +0.5 hours after arrival
         * BUS: +0.5 hours after arrival
         * CRUISE: +3 hours after arrival (arrival before 10:00) or +2 hours (arrival after 10:00)
         * FERRY: +1 hour after arrival
         * TRANSFER: +15 minutes after arrival
         */
        $changes = [];

        foreach ($items as $offset => $item) {
            if (!$item instanceof AbstractItinerary) {
                continue;
            }

            $source = $item->getSource();

            if ($source instanceof Tripsegment
                && !empty($source->getArrgeotagid())
            ) {
                $arrGeoTag = $source->getArrgeotagid();
                $arrDate = Geotag::getLocalDateTimeByGeoTag($source->getArrivalDate(), $arrGeoTag);

                $startDate = clone $arrDate;
                $startDate->modify('0:00');

                $endDate = clone $arrDate;
                $endDate->modify('+3 hour');

                // there are possible bug: one hotel can be moved more than once, once for each flight
                $nearCheckins = $this->searchNear($items, $startDate, $endDate);
                $arrTz = $arrGeoTag->getDateTimeZone();
                /** @var Checkin[] $nearCheckins */
                $nearCheckins = array_filter($nearCheckins, function (ItemInterface $segment) use ($arrTz) {
                    return
                        $segment instanceof Checkin
                        && !empty($segment->getSource()->getGeotagid())
                        && abs(
                            $segment->getSource()->getGeotagid()->getDateTimeZone()->getOffset($this->clock->current()->getAsDateTime())
                            - $arrTz->getOffset($this->clock->current()->getAsDateTime())
                        ) <= 3600;
                });

                foreach ($nearCheckins as $checkin) {
                    $distance = $item->getSource()->getArrgeotagid()->distanceFrom($checkin->getSource()->getGeoTagid());
                    $key = spl_object_hash($checkin);

                    if (!isset($changes[$key]) || $distance < $changes[$key]['distance']) {
                        /** @var Checkout[] $checkouts */
                        $checkouts = array_filter($items, function (ItemInterface $segment) use ($checkin) {
                            return $segment instanceof Checkout && $segment->getConfNo() === $checkin->getConfNo();
                        });
                        $checkout = array_shift($checkouts);
                        $changes[$key] = ['checkin' => $checkin, 'checkout' => $checkout, 'startDate' => $arrDate, 'distance' => $distance, 'trip' => $source->getTripid()];
                    }
                }
            }
        }

        foreach ($changes as $hash => $change) {
            /** @var Checkin $checkin */
            $checkin = $change['checkin'];
            /** @var Checkout $checkout */
            $checkout = $change['checkout'];
            /** @var \DateTime $arrivalDate */
            $arrivalDate = $change['startDate'];
            /** @var \AwardWallet\MainBundle\Entity\Trip $trip */
            $trip = $change['trip'];

            /** @var \DateTime $checkinDate */
            $checkinDate = $checkin->getStartDate();

            // Get transport category and calculate new check-in time
            $transportCategory = $trip->getCategory();
            $newCheckingDate = $this->getTransportAdjustment($arrivalDate, $transportCategory, $checkinDate);

            if (isset($checkout) && $newCheckingDate < $checkout->getStartDate()) {
                $checkin->setStartDate($newCheckingDate);
                $diff = $checkinDate->diff($newCheckingDate);
                $checkin->getLocalDate()->add($diff);
            }
        }
    }

    /**
     * Calculate hotel check-in time based on transport category and arrival time.
     */
    private function getTransportAdjustment(\DateTime $arrivalDate, int $transportCategory, \DateTime $originalCheckinDate): \DateTime
    {
        switch ($transportCategory) {
            case Trip::CATEGORY_AIR:
                return $this->resolveCheckinTime($arrivalDate, $originalCheckinDate, '+1 hour');

            case Trip::CATEGORY_TRAIN:
            case Trip::CATEGORY_BUS:
                return $this->resolveCheckinTime($arrivalDate, $originalCheckinDate, '+30 minute');

            case Trip::CATEGORY_CRUISE:
                return $this->resolveCheckinTime($arrivalDate, $originalCheckinDate, '+3 hour');

            case Trip::CATEGORY_FERRY:
                return $this->resolveCheckinTime($arrivalDate, $originalCheckinDate, '+1 hour');

            case Trip::CATEGORY_TRANSFER:
                return $this->resolveCheckinTime($arrivalDate, $originalCheckinDate, '+15 minute');

            default:
                // Default to no correction for unknown categories
                return clone $originalCheckinDate;
        }
    }

    private function resolveCheckinTime(\DateTime $arrival, \DateTime $reserved, string $offset): \DateTime
    {
        $arrivalWithTravel = (clone $arrival)->modify($offset);
        $endOfArrivalDay = (clone $arrival)->setTime(23, 59, 59);

        return clone min(max($arrivalWithTravel, $reserved), $endOfArrivalDay);
    }

    /**
     * @param ItemInterface[] $items
     */
    private function searchNear(array $items, \DateTime $startDate, \DateTime $endDate): array
    {
        $startIndex = $this->binarySearch($items, $startDate, true);
        $endIndex = $this->binarySearch($items, $endDate, false, $startIndex);

        return array_slice($items, $startIndex, $endIndex - $startIndex + 1);
    }

    /**
     * @param ItemInterface[] $items
     */
    private function binarySearch(array $items, \DateTime $target, bool $findStart, int $startIndex = 0): int
    {
        $low = $startIndex;
        $total = count($items);
        $high = $total - 1;

        // For empty array or invalid range
        if ($high < $low) {
            return -1;
        }

        while ($low <= $high) {
            $mid = intdiv($low + $high, 2);

            if ($items[$mid]->getStartDate() < $target) {
                $low = $mid + 1;
            } elseif ($items[$mid]->getStartDate() > $target) {
                $high = $mid - 1;
            } else {
                if ($findStart) {
                    // Looking for the first occurrence (lower bound)
                    // Continue searching in the left part
                    $high = $mid - 1;
                } else {
                    // Looking for the last occurrence (upper bound)
                    // Continue searching in the right part
                    $low = $mid + 1;
                }
            }
        }

        if ($findStart) {
            // Return the index of the first element >= target
            // Or -1 if all elements < target
            return $low <= $total - 1 ? $low : -1;
        } else {
            // Return the index of the last element <= target
            // Or -1 if all elements > target
            return $high >= 0 ? $high : -1;
        }
    }

    /**
     * @param Item\ItemInterface[] $items
     * @return Item\ItemInterface[]
     */
    private function addDates(array $items)
    {
        $result = [];
        $endDate = null;

        foreach ($items as $k => $item) {
            if (
                $item instanceof LayoverInterface && !empty($item->getContext()->getPrevStartDate())
                && $item->getContext()->getPrevStartDate()->format('Y-m-d') !== $item->getStartDate()->format('Y-m-d')
            ) {
                $result[] = new Item\Date($item->getStartDate(), $item->getLocalDate());
            }

            $localDate = $item->getLocalDate();

            if (empty($localDate)) {
                $result[] = $item;

                continue;
            }
            $date = $localDate->format("Y-m-d");

            if (!($item instanceof Item\LayoverInterface) && (empty($prevDate) || $prevDate !== $date)) {
                if (!empty($dateItem)) {
                    $dateItem->setEndDate($endDate);
                }
                $dateItem = new Item\Date($item->getStartDate(), $item->getLocalDate());
                $result[] = $dateItem;
            }
            $result[] = $item;

            if (empty($endDate) || $item->getEndDate() > $endDate) {
                $endDate = $item->getEndDate();
            }
            $prevDate = $date;
        }

        if (!empty($dateItem)) {
            $dateItem->setEndDate($endDate);
        }

        return $result;
    }

    private function calculatePlansDurations(array $items): array
    {
        $inPlan = false;
        $onlyTrips = true;
        /** @var Item\PlanStart $planStart */
        $planStart = null;
        /** @var Item\ItemInterface $firstItSegmentInPlan */
        $firstItSegmentInPlan = null;
        /** @var Item\ItemInterface $lastItSegmentInPlan */
        $lastItSegmentInPlan = null;

        foreach ($items as $item) {
            switch (true) {
                case $item instanceof Item\PlanStart:
                    $inPlan = true;
                    $onlyTrips = true;
                    $planStart = $item;
                    $firstItSegmentInPlan = null;
                    $lastItSegmentInPlan = null;

                    break;

                case $item instanceof Item\PlanEnd:
                    if ($inPlan) {
                        if (
                            !is_null($firstItSegmentInPlan)
                            && !is_null($lastItSegmentInPlan)
                            && $planStart
                            && (
                                !$onlyTrips
                                || (
                                    ($firstSource = $firstItSegmentInPlan->getSource()) instanceof Tripsegment
                                    && ($lastSource = $lastItSegmentInPlan->getSource()) instanceof Tripsegment
                                    && !empty($firstSource->getDepcode())
                                    && $firstSource->getDepcode() === $lastSource->getArrcode()
                                )
                            )
                        ) {
                            $planStart->setStartSegmentDate($firstItSegmentInPlan->getStartDate());
                            $planStart->setEndSegmentDate($lastItSegmentInPlan->getEndDate() ?? $lastItSegmentInPlan->getStartDate());
                        }
                    }

                    $inPlan = false;

                    break;

                default:
                    if (
                        $inPlan
                        && $item instanceof Item\ItineraryInterface
                        && !empty($itinerary = $item->getItinerary())
                        && !$itinerary->getHidden()
                    ) {
                        if (is_null($firstItSegmentInPlan)) {
                            $firstItSegmentInPlan = $item;
                        }

                        $onlyTrips = $onlyTrips && $item instanceof Item\AbstractTrip;
                        $lastItSegmentInPlan = $item;
                    }

                    break;
            }
        }

        return $items;
    }

    /**
     * @param Item\ItemInterface[] $items
     */
    private function fillPlansInfo(array &$items, $isShowDeleted)
    {
        $inPlan = false;
        /** @var Item\PlanStart $planStart */
        $planStart = null;
        $lastUpdated = null;
        $localDate = null;
        $prevItem = null;

        foreach ($items as $index => $item) {
            switch (true) {
                case $item instanceof Item\PlanStart:
                    $inPlan = true;
                    $planStart = $item;
                    /** @var \DateTime $lastUpdated */
                    $lastUpdated = null;

                    if ($index < (count($items) - 1) && $items[$index + 1] instanceof Item\Date) {
                        $item->setLocalDate($items[$index + 1]->getLocalDate());
                    } elseif (!empty($localDate)) {
                        $item->setLocalDate($localDate);
                    } else {
                        // TODO: use user home timezone
                        $item->setLocalDate($item->getStartDate());
                    }

                    break;

                case $item instanceof Item\PlanEnd:
                    if ($inPlan) {
                        $inPlan = false;
                        $planStart->setLastUpdated($lastUpdated);
                    } else {
                        foreach (array_reverse(array_slice($items, 0, $index)) as $reverseItem) {
                            if ($reverseItem instanceof CanCreatePlanInterface) {
                                $reverseItem->setCanCreatePlan(false);
                            }
                        }
                    }

                    if (!empty($localDate)) {
                        $item->setLocalDate($localDate);
                    } else {
                        // TODO: use user home timezone
                        $item->setLocalDate($item->getEndDate());
                    }

                    break;

                case $item instanceof Item\Date:
                    if (!$inPlan) {
                        if ($isShowDeleted && $this->presentUndeletedSegments($index, $items)) {
                            $item->setCanCreatePlan(true);
                        }

                        if (!$isShowDeleted) {
                            $item->setCanCreatePlan(true);
                        }
                    }
                    $localDate = $item->getLocalDate();

                    break;

                default:
                    if ($inPlan && $item instanceof Item\ItineraryInterface) {
                        if (!empty($itinerary = $item->getItinerary())) {
                            $date = $itinerary->getUpdateDate();

                            if (empty($date)) {
                                $date = $itinerary->getCreateDate();
                            }
                        }

                        if (!empty($date) && (empty($lastUpdated) || $date->getTimestamp() > $lastUpdated->getTimestamp())) {
                            $lastUpdated = $date;
                        }
                    }

                    if (
                        !$inPlan
                        && $item instanceof CanCreatePlanInterface
                        && !($prevItem instanceof Item\Date)
                    ) {
                        $item->setCanCreatePlan(true);
                    }
            }
            $item->setBreakAfter(!$inPlan);
            $prevItem = $item;
        }
    }

    private function addPriceDropMonitoringIndicator(array &$items, Usr $user): void
    {
        $isStaff = $user->hasRole('ROLE_STAFF')
            && in_array($user->getId(), FlightDealSubscriber::STAFF_USER_IDS);

        if (!$isStaff) {
            return;
        }

        /** @var AirTrip[] $airTrips */
        $airTrips = it($items)
            ->filter(function (ItemInterface $item) {
                return $item instanceof AirTrip && $item->getSource() instanceof Tripsegment;
            })
            ->toArray();

        if (empty($airTrips)) {
            return;
        }

        $q = $this->connection->executeQuery('
            SELECT t.TripID
            FROM RAFlightSearchQuery q
                JOIN MileValue mv ON mv.MileValueID = q.MileValueID
                JOIN Trip t ON t.TripID = mv.TripID
            WHERE
                q.DeleteDate IS NULL
                AND t.Hidden = 0
                AND t.Cancelled = 0
                AND t.UserID = :userId
                AND t.TripID IN (:tripIds)
        ', [
            'userId' => $user->getId(),
            'tripIds' => array_map(fn (AirTrip $item) => $item->getSource()->getTripid()->getId(), $airTrips),
        ], [
            'userId' => \PDO::PARAM_INT,
            'tripIds' => Connection::PARAM_INT_ARRAY,
        ]);

        $monitoringLowPricesTrips = [9224689];

        while ($row = $q->fetchAssociative()) {
            $monitoringLowPricesTrips[] = $row['TripID'];
        }

        if (!empty($monitoringLowPricesTrips)) {
            foreach ($airTrips as $airTrip) {
                $airTrip->setMonitoringLowPrices(
                    in_array($airTrip->getSource()->getTripid()->getId(), $monitoringLowPricesTrips)
                );
            }
        }
    }

    /**
     * @param Item\ItemInterface[] $items
     * @return bool Has undeleted segments in day
     * Count undeleted segments in day
     */
    private function presentUndeletedSegments($index, array $items)
    {
        $dateSegment = $items[$index];

        $count = 0;
        $segs = [];

        do {
            $seg = $items[$index];

            $segs[] = $seg;

            if (
                (!($seg instanceof Date) && $seg->getStartDate() >= $dateSegment->getStartDate() && $seg->getEndDate() <= $dateSegment->getEndDate())
                && !(
                    $seg instanceof ItineraryInterface
                    && (
                        (!is_null($seg->getItinerary()) && $seg->getItinerary()->getHidden())
                        || (!is_null($seg->getSource()) && $seg->getSource()->getHidden())
                    )
                )
            ) {
                $count++;
            }

            $index++;
        } while (isset($items[$index]) && !($items[$index] instanceof Date));

        return $count > 0;
    }

    /**
     * @param Item\ItemInterface[] $items
     */
    private function filterByTravelPlan($items, Plan $plan)
    {
        $planStarted = false;
        $planEnded = false;

        foreach ($items as $key => $item) {
            if ($item instanceof PlanStart && $item->getPlan()->getId() === $plan->getId()) {
                $planStarted = true;
            }

            if (!$planStarted || $planEnded) {
                unset($items[$key]);
            }

            if ($item instanceof PlanEnd && $item->getPlan()->getId() === $plan->getId()) {
                $planEnded = true;
            }
        }

        return $items;
    }
}
