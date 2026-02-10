<?php

namespace AwardWallet\Tests\Unit\Timeline;

use AwardWallet\MainBundle\Timeline\Formatter\ItemFormatterInterface;
use AwardWallet\MainBundle\Timeline\Item;
use AwardWallet\MainBundle\Timeline\Manager;
use AwardWallet\MainBundle\Timeline\PlanManager;
use AwardWallet\MainBundle\Timeline\QueryOptions;
use AwardWallet\Tests\Unit\BaseUserTest;
use Codeception\Module\Aw;

/**
 * @group frontend-unit
 */
class CreatePlanTest extends BaseUserTest
{
    /**
     * @var PlanManager
     */
    private $manager;

    public function _before()
    {
        parent::_before();
        $this->manager = $this->container->get('aw.timeline.plan_manager');
    }

    public function testCreateOnEmpty()
    {
        $startTime = time();
        $plan = $this->manager->create(null, $startTime);
        $this->assertEquals(date("c", $startTime - SECONDS_PER_HOUR * 8), $plan->getStartDate()->format("c"));
        $this->assertEquals(date("c", $startTime + SECONDS_PER_HOUR * 8), $plan->getEndDate()->format("c"));
    }

    public function testSimpleOneSegment()
    {
        $startTime = strtotime("tomorrow");
        $this->db->haveInDatabase("Restaurant", [
            "UserID" => $this->user->getId(),
            "ConfNo" => 'Rest1',
            "Address" => Aw::GMT_AIRPORT, // burkina faso, always GMT
            'Name' => 'Birthday',
            "StartDate" => date("Y-m-d", $startTime),
            "EndDate" => date("Y-m-d H:i:s", $startTime + SECONDS_PER_HOUR * 5),
        ]);

        $plan = $this->manager->create(null, $startTime);
        $this->assertEquals(date("c", $startTime - SECONDS_PER_HOUR * 8), $plan->getStartDate()->format("c"));
        $this->assertEquals(date("c", $startTime + SECONDS_PER_HOUR * 8), $plan->getEndDate()->format("c"));
    }

    public function testPastMid()
    {
        $startTime = strtotime("tomorrow");
        $this->db->haveInDatabase("Restaurant", [
            "UserID" => $this->user->getUserid(),
            "ConfNo" => 'Rest1',
            "Address" => Aw::GMT_AIRPORT,
            'Name' => 'Birthday',
            "StartDate" => date("Y-m-d H:i:s", $startTime - SECONDS_PER_HOUR * 4),
            "EndDate" => date("Y-m-d H:i:s", $startTime - SECONDS_PER_HOUR),
        ]);
        $this->db->haveInDatabase("Restaurant", [
            "UserID" => $this->user->getUserid(),
            "ConfNo" => 'Rest1',
            "Address" => Aw::GMT_AIRPORT,
            'Name' => 'Birthday',
            "StartDate" => date("Y-m-d", $startTime),
            "EndDate" => date("Y-m-d H:i:s", $startTime + SECONDS_PER_HOUR * 5),
        ]);

        $plan = $this->manager->create(null, $startTime);
        $this->assertEquals(date("c", $startTime - SECONDS_PER_HOUR * 2), $plan->getStartDate()->format("c"));
        $this->assertEquals(date("c", $startTime + SECONDS_PER_HOUR * 8), $plan->getEndDate()->format("c"));
    }

    public function testPastSame()
    {
        $startTime = strtotime("tomorrow");
        $this->db->haveInDatabase("Restaurant", [
            "UserID" => $this->user->getUserid(),
            "ConfNo" => 'Rest1',
            "Address" => Aw::GMT_AIRPORT,
            'Name' => 'Birthday',
            "StartDate" => date("Y-m-d", $startTime),
            "EndDate" => date("Y-m-d H:i:s", $startTime + SECONDS_PER_HOUR),
        ]);
        $this->db->haveInDatabase("Restaurant", [
            "UserID" => $this->user->getUserid(),
            "ConfNo" => 'Rest1',
            "Address" => Aw::GMT_AIRPORT,
            'Name' => 'Birthday',
            "StartDate" => date("Y-m-d", $startTime),
            "EndDate" => date("Y-m-d H:i:s", $startTime + SECONDS_PER_HOUR * 5),
        ]);

        $plan = $this->manager->create(null, $startTime);
        $this->assertEquals(date("c", $startTime - SECONDS_PER_HOUR * 8), $plan->getStartDate()->format("c"));
        $this->assertEquals(date("c", $startTime + SECONDS_PER_HOUR * 8), $plan->getEndDate()->format("c"));
    }

    public function testFutureMid()
    {
        $startTime = strtotime("tomorrow 22:00");
        $this->db->haveInDatabase("Restaurant", [
            "UserID" => $this->user->getUserid(),
            "ConfNo" => 'Rest1',
            'Name' => 'Birthday',
            "Address" => Aw::GMT_AIRPORT,
            "StartDate" => date("Y-m-d H:i:s", $startTime),
            "EndDate" => date("Y-m-d H:i:s", $startTime + SECONDS_PER_HOUR * 5),
        ]);
        $this->db->haveInDatabase("Restaurant", [
            "UserID" => $this->user->getUserid(),
            "ConfNo" => 'Rest2',
            'Name' => 'Birthday',
            "Address" => Aw::GMT_AIRPORT,
            "StartDate" => date("Y-m-d H:i:s", $startTime + SECONDS_PER_HOUR * 4),
            "EndDate" => date("Y-m-d H:i:s", $startTime + SECONDS_PER_HOUR * 5),
        ]);

        $plan = $this->manager->create(null, $startTime);
        $this->assertEquals(date("c", $startTime - SECONDS_PER_HOUR * 8), $plan->getStartDate()->format("c"));
        $this->assertEquals(date("c", $startTime + SECONDS_PER_HOUR * 2), $plan->getEndDate()->format("c"));
    }

    public function testFutureIntersection()
    {
        $startTime = strtotime("tomorrow");
        $this->db->haveInDatabase("Restaurant", [
            "UserID" => $this->user->getUserid(),
            "ConfNo" => 'Rest1',
            'Name' => 'Birthday',
            "Address" => Aw::GMT_AIRPORT,
            "StartDate" => date("Y-m-d H:i:s", $startTime),
            "EndDate" => date("Y-m-d H:i:s", $startTime + SECONDS_PER_HOUR * 5),
        ]);
        $this->db->haveInDatabase("Plan", [
            "UserID" => $this->user->getUserid(),
            "Name" => "Intersection1",
            "StartDate" => date("Y-m-d H:i:s", $startTime + SECONDS_PER_HOUR * 2),
            "EndDate" => date("Y-m-d H:i:s", $startTime + SECONDS_PER_DAY * 6),
        ]);
        // no empty plans
        $this->db->haveInDatabase("Restaurant", [
            "UserID" => $this->user->getUserid(),
            "ConfNo" => 'Rest2',
            'Name' => 'Birthday',
            "Address" => Aw::GMT_AIRPORT,
            "StartDate" => date("Y-m-d H:i:s", $startTime + SECONDS_PER_DAY * 5),
            "EndDate" => date("Y-m-d H:i:s", $startTime + SECONDS_PER_DAY * 6),
        ]);

        $plan = $this->manager->create(null, $startTime);
        $this->assertEquals(date("c", $startTime - SECONDS_PER_HOUR * 8), $plan->getStartDate()->format("c"));
        $this->assertEquals(date("c", $startTime + SECONDS_PER_HOUR * 1), $plan->getEndDate()->format("c"));
    }

    public function testPastIntersection()
    {
        $startTime = strtotime("tomorrow");
        $this->db->haveInDatabase("Restaurant", [
            "UserID" => $this->user->getUserid(),
            "ConfNo" => 'Rest1',
            'Name' => 'Birthday',
            "Address" => Aw::GMT_AIRPORT,
            "StartDate" => date("Y-m-d H:i:s", $startTime),
            "EndDate" => date("Y-m-d H:i:s", $startTime + SECONDS_PER_HOUR * 5),
        ]);
        $this->db->haveInDatabase("Plan", [
            "UserID" => $this->user->getUserid(),
            "Name" => "Intersection1",
            "StartDate" => date("Y-m-d H:i:s", $startTime - SECONDS_PER_DAY * 6),
            "EndDate" => date("Y-m-d H:i:s", $startTime - SECONDS_PER_HOUR * 2),
        ]);
        // no empty plans
        $this->db->haveInDatabase("Restaurant", [
            "UserID" => $this->user->getUserid(),
            "ConfNo" => 'Rest2',
            "Address" => Aw::GMT_AIRPORT,
            'Name' => 'Birthday',
            "StartDate" => date("Y-m-d H:i:s", $startTime - SECONDS_PER_DAY * 5),
            "EndDate" => date("Y-m-d H:i:s", $startTime - SECONDS_PER_DAY * 4),
        ]);

        $plan = $this->manager->create(null, $startTime);
        $this->assertEquals(date("c", $startTime - SECONDS_PER_HOUR * 1), $plan->getStartDate()->format("c"));
        $this->assertEquals(date("c", $startTime + SECONDS_PER_HOUR * 8), $plan->getEndDate()->format("c"));
    }

    public function testWithinPlan()
    {
        $startTime = strtotime("tomorrow");
        $this->db->haveInDatabase("Restaurant", [
            "UserID" => $this->user->getUserid(),
            "ConfNo" => 'Rest1',
            'Name' => 'Birthday',
            "Address" => Aw::GMT_AIRPORT,
            "StartDate" => date("Y-m-d H:i:s", $startTime),
            "EndDate" => date("Y-m-d H:i:s", $startTime + SECONDS_PER_HOUR * 5),
        ]);
        $this->db->haveInDatabase("Plan", [
            "UserID" => $this->user->getUserid(),
            "Name" => "Intersection1",
            "StartDate" => date("Y-m-d H:i:s", $startTime - SECONDS_PER_HOUR * 6),
            "EndDate" => date("Y-m-d H:i:s", $startTime + SECONDS_PER_HOUR * 2),
        ]);

        $plan = $this->manager->create(null, $startTime);
        $this->assertNull($plan);
    }

    public function testExtendByConfNo()
    {
        $startTime = strtotime("tomorrow");
        $this->db->haveInDatabase("Reservation", [
            "UserID" => $this->user->getUserid(),
            "ConfirmationNumber" => 'HOTEL1',
            "Address" => Aw::GMT_AIRPORT,
            "HotelName" => "Hilton",
            "CheckInDate" => date("Y-m-d", $startTime),
            "CheckOutDate" => date("Y-m-d H:i:s", $startTime + SECONDS_PER_DAY * 3 + SECONDS_PER_HOUR * 5),
            "Rooms" => "a:0:{}",
        ]);

        $plan = $this->manager->create(null, $startTime);
        $this->assertEquals(date("c", $startTime - SECONDS_PER_HOUR * 8), $plan->getStartDate()->format("c"));
        $this->assertEquals(date("c", $startTime + SECONDS_PER_DAY * 3 + SECONDS_PER_HOUR * 13), $plan->getEndDate()->format("c"));
    }

    public function testExtendByConfNoWithIntersection()
    {
        $startTime = strtotime("tomorrow");
        $this->db->haveInDatabase("Reservation", [
            "UserID" => $this->user->getUserid(),
            "ConfirmationNumber" => 'HOTEL1',
            "Address" => Aw::GMT_AIRPORT,
            "HotelName" => "Hilton",
            "CheckInDate" => date("Y-m-d", $startTime),
            "CheckOutDate" => date("Y-m-d H:i:s", $startTime + SECONDS_PER_DAY * 3 + SECONDS_PER_HOUR * 5),
            "Rooms" => "a:0:{}",
        ]);
        $this->db->haveInDatabase("Plan", [
            "UserID" => $this->user->getUserid(),
            "Name" => "Intersection1",
            "StartDate" => date("Y-m-d H:i:s", $startTime + SECONDS_PER_DAY),
            "EndDate" => date("Y-m-d H:i:s", $startTime + SECONDS_PER_DAY * 3),
        ]);
        $this->db->haveInDatabase("Reservation", [
            "UserID" => $this->user->getUserid(),
            "ConfirmationNumber" => 'HOTEL2',
            "Address" => Aw::GMT_AIRPORT,
            "HotelName" => "Hilton",
            "CheckInDate" => date("Y-m-d", $startTime + SECONDS_PER_DAY * 2),
            "CheckOutDate" => date("Y-m-d H:i:s", $startTime + SECONDS_PER_DAY * 3),
            "Rooms" => "a:0:{}",
        ]);

        $plan = $this->manager->create(null, $startTime);
        $this->assertEquals(date("c", $startTime - SECONDS_PER_HOUR * 8), $plan->getStartDate()->format("c"));
        $this->assertEquals(date("c", $startTime + SECONDS_PER_HOUR * 8), $plan->getEndDate()->format("c"));
    }

    public function testLayovers()
    {
        $startTime = strtotime("tomorrow");
        $tripId = $this->db->haveInDatabase("Trip", [
            "UserID" => $this->user->getUserid(),
            "RecordLocator" => 'FLIGHT1',
        ]);
        $this->aw->createTripSegment([
            "TripID" => $tripId,
            "DepCode" => Aw::GMT_AIRPORT_2,
            "DepDate" => date("Y-m-d H:i:s", $startTime),
            "ArrCode" => Aw::GMT_AIRPORT,
            "ArrDate" => date("Y-m-d H:i:s", $startTime + SECONDS_PER_HOUR),
            "MarketingAirlineConfirmationNumber" => "FLIGHT1",
        ]);
        $this->aw->createTripSegment([
            "TripID" => $tripId,
            "DepCode" => Aw::GMT_AIRPORT,
            "DepDate" => date("Y-m-d H:i:s", $startTime + SECONDS_PER_HOUR * 2),
            "ArrCode" => Aw::GMT_AIRPORT_3,
            "ArrDate" => date("Y-m-d H:i:s", $startTime + SECONDS_PER_HOUR * 3),
            "MarketingAirlineConfirmationNumber" => "FLIGHT1",
        ]);

        $plan = $this->manager->create(null, $startTime);

        $manager = $this->container->get(Manager::class);
        $items = $manager->query((new QueryOptions())->setFormat(ItemFormatterInterface::DESKTOP)->setUser($this->user));
        $types = array_map(function (Item\ItemInterface $item) { return get_class($item); }, $items);
        $this->assertEquals([
            Item\PlanStart::class,
            Item\Date::class,
            Item\AirTrip::class,
            Item\AirLayover::class,
            Item\AirTrip::class,
            Item\PlanEnd::class,
        ], $types);
    }

    public function _after()
    {
        $this->manager = null;

        parent::_after(); // TODO: Change the autogenerated stub
    }
}
