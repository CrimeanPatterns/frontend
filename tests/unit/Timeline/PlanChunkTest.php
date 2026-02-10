<?php

namespace AwardWallet\Tests\Unit\Timeline;

use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\PlanItem;
use AwardWallet\MainBundle\Timeline\Manager;
use AwardWallet\MainBundle\Timeline\PlanManager;
use AwardWallet\MainBundle\Timeline\QueryOptions;
use AwardWallet\Tests\Unit\BaseUserTest;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertTrue;

/**
 * @group frontend-unit
 */
class PlanChunkTest extends BaseUserTest
{
    /** @var PlanManager */
    private $planManager;
    /** @var Manager */
    private $timelineManager;

    public function _before()
    {
        parent::_before();

        $this->planManager = $this->container->get('aw.timeline.plan_manager');
        $this->timelineManager = $this->container->get(Manager::class);
    }

    public function testPlanFullWrap()
    {
        $userId = $this->user->getUserid();

        $baseDate = new \DateTimeImmutable('-1 DAY');
        $this->db->haveInDatabase('Rental', [
            'UserID' => $userId,
            'Number' => 'Rental100',
            'PickupLocation' => 'Moscow',
            'PickupDateTime' => $this->convertToSql($baseDate),
            'DropoffLocation' => 'Moscow',
            'DropoffDateTime' => $this->convertToSql($baseDate->add(\DateInterval::createFromDateString('2 DAY'))),
            'CreateDate' => $this->convertToSql($baseDate->sub(\DateInterval::createFromDateString('1 DAY'))),
        ]);

        $this->planManager->create(null, $baseDate->getTimestamp());

        $segments = $this->timelineManager->query(
            QueryOptions::createMobile()
                ->setWithDetails(true)
                ->setUser($this->user)
                ->lock()
        );

        assertFalse($this->timelineManager->hasMoreBefore(new \DateTime('@' . $segments[0]->startDate->ts), $this->user, null, true));
    }

    public function testPlanInline()
    {
        $baseDate = new \DateTimeImmutable('-20 DAY');
        $userId = $this->user->getUserid();

        $this->db->haveInDatabase('Rental', [
            'UserID' => $userId,
            'Number' => 'Rental100',
            'PickupLocation' => 'Moscow',
            'PickupDateTime' => $this->convertToSql($firstRental = $baseDate),
            'DropoffLocation' => 'Moscow',
            'DropoffDateTime' => $this->convertToSql($lastRental = $baseDate->add(\DateInterval::createFromDateString('1 DAY'))),
            'CreateDate' => $this->convertToSql($baseDate->sub(\DateInterval::createFromDateString('3 DAY'))),
        ]);

        $baseDate = $lastRental->add(\DateInterval::createFromDateString('5 DAY'));
        $this->db->haveInDatabase('Reservation', [
            'UserID' => $userId,
            'ConfirmationNumber' => 'Reservation100',
            'HotelName' => 'Reservation100',
            'CreateDate' => $this->convertToSql($baseDate->sub(\DateInterval::createFromDateString('2 DAY'))),
            'CheckInDate' => $this->convertToSql($baseDate),
            'CheckOutDate' => $this->convertToSql($baseDate->add(\DateInterval::createFromDateString('2 DAY'))),
            'Rooms' => 'a:0:{}',
        ]);

        $plan = $this->planManager->create(null, $baseDate->getTimestamp());
        $this->planManager->move(
            $plan,
            $baseDate->getTimestamp(),
            'planStart',
            null
        );

        $segments = $this->timelineManager->query(
            QueryOptions::createMobile()
                ->setWithDetails(true)
                ->setUser($this->user)
                ->lock()
        );
        assertEquals(
            ['date',
                'pickup',
                'date',
                'dropoff',
                'planStart',
                'date',
                'checkin',
                'date',
                'checkout',
                'planEnd', ],
            array_map(function ($item) {
                return $item->type;
            }, $segments)
        );

        $plans = array_values(array_filter($segments, function ($item) {
            return $item instanceof PlanItem;
        }));
        assertTrue($this->timelineManager->hasMoreBefore(new \DateTime('@' . $plans[0]->startDate->ts), $this->user, null, true));

        $segments = $this->timelineManager->query(
            QueryOptions::createMobile()
                ->setWithDetails(true)
                ->setUser($this->user)
                ->setEndDate(new \DateTime('@' . $plans[0]->startDate->ts))
                ->lock()
        );

        assertEquals(
            ['date',
                'pickup',
                'date',
                'dropoff', ],
            array_map(function ($item) {
                return $item->type;
            }, $segments)
        );

        assertFalse($this->timelineManager->hasMoreBefore(new \DateTime('@' . $segments[0]->startDate->ts), $this->user, null, true));
    }

    public function _after()
    {
        $this->planManager = $this->timelineManager = null;

        parent::_after();
    }
}
