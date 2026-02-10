<?php

namespace AwardWallet\Tests\Unit\Timeline;

use AwardWallet\MainBundle\Timeline\Formatter\ItemFormatterInterface;
use AwardWallet\MainBundle\Timeline\Item;
use AwardWallet\MainBundle\Timeline\Manager;
use AwardWallet\MainBundle\Timeline\PlanManager;
use AwardWallet\MainBundle\Timeline\QueryOptions;
use AwardWallet\Tests\Unit\BaseUserTest;

/**
 * @group frontend-unit
 */
class LayoverTest extends BaseUserTest
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

    public function testDontAddLayoverWithHotel()
    {
        $tripId = $this->db->haveInDatabase("Trip", [
            "UserID" => $this->user->getUserid(),
            "RecordLocator" => 'FLIGHT1',
        ]);
        $this->aw->createTripSegment([
            "TripID" => $tripId,
            "MarketingAirlineConfirmationNumber" => "FLIGHT1",
            "DepCode" => "JFK",
            "DepDate" => "2035-01-01 11:00",
            "ArrCode" => "LAX",
            "ArrDate" => "2035-01-01 13:00",
        ]);
        $this->aw->createTripSegment([
            "TripID" => $tripId,
            "MarketingAirlineConfirmationNumber" => "FLIGHT1",
            "DepCode" => "LAX",
            "DepDate" => "2035-01-01 21:00",
            "ArrCode" => "PEE",
            "ArrDate" => "2035-01-02 11:00",
        ]);
        $this->db->haveInDatabase("Reservation", [
            "UserID" => $this->user->getUserid(),
            "ConfirmationNumber" => 'HOTEL1',
            "Address" => "LAX",
            "HotelName" => "Hilton",
            "CheckInDate" => "2035-01-01 16:00",
            "CheckOutDate" => "2035-01-02 16:00",
            "Rooms" => "a:0:{}",
        ]);

        $manager = $this->container->get(Manager::class);
        $items = $manager->query((new QueryOptions())->setFormat(ItemFormatterInterface::DESKTOP)->setUser($this->user));
        $types = array_map(function (Item\ItemInterface $item) { return get_class($item); }, $items);
        $this->assertEquals([
            Item\Date::class,
            Item\AirTrip::class,
            Item\Checkin::class,
            Item\AirTrip::class,
            Item\Date::class,
            Item\Checkout::class,
        ], $types);
    }

    public function testRoundTrip()
    {
        $tripId = $this->db->haveInDatabase("Trip", [
            "UserID" => $this->user->getUserid(),
            "RecordLocator" => 'FLIGHT1',
        ]);
        $this->aw->createTripSegment([
            "TripID" => $tripId,
            "MarketingAirlineConfirmationNumber" => "FLIGHT1",
            "DepCode" => "ORD",
            "DepDate" => "2035-06-14 20:35",
            "ArrCode" => "MSP",
            "ArrDate" => "2035-06-14 21:15",
        ]);

        $tripId = $this->db->haveInDatabase("Trip", [
            "UserID" => $this->user->getUserid(),
            "RecordLocator" => 'FLIGHT2',
        ]);
        $this->aw->createTripSegment([
            "TripID" => $tripId,
            "MarketingAirlineConfirmationNumber" => "FLIGHT1",
            "DepCode" => "MSP",
            "DepDate" => "2035-06-15 15:!5",
            "ArrCode" => "ORD",
            "ArrDate" => "2035-06-15 16:45",
        ]);

        $manager = $this->container->get(Manager::class);
        $items = $manager->query((new QueryOptions())->setFormat(ItemFormatterInterface::DESKTOP)->setUser($this->user));
        $types = array_map(function (Item\ItemInterface $item) { return get_class($item); }, $items);
        $this->assertEquals([
            Item\Date::class,
            Item\AirTrip::class,
            Item\Date::class,
            Item\AirTrip::class,
        ], $types);
    }

    public function testDontAddNegativeLayover()
    {
        $tripId = $this->db->haveInDatabase("Trip", [
            "UserID" => $this->user->getUserid(),
            "RecordLocator" => 'FLIGHT1',
        ]);
        $this->aw->createTripSegment([
            "TripID" => $tripId,
            "MarketingAirlineConfirmationNumber" => "FLIGHT1",
            "DepCode" => "ORD",
            "DepDate" => "2035-06-14 20:35",
            "ArrCode" => "MSP",
            "ArrDate" => "2035-06-14 21:15",
        ]);
        $this->aw->createTripSegment([
            "TripID" => $tripId,
            "DepCode" => "MSP",
            "DepDate" => "2035-06-14 21:00",
            "ArrCode" => "JFK",
            "ArrDate" => "2035-06-14 23:15",
        ]);

        $manager = $this->container->get(Manager::class);
        $items = $manager->query((new QueryOptions())->setFormat(ItemFormatterInterface::DESKTOP)->setUser($this->user));
        $types = array_map(function (Item\ItemInterface $item) { return get_class($item); }, $items);
        $this->assertEquals([
            Item\Date::class,
            Item\AirTrip::class,
            Item\AirTrip::class,
        ], $types);
    }

    public function testPositiveLayover()
    {
        $tripId = $this->db->haveInDatabase("Trip", [
            "UserID" => $this->user->getUserid(),
            "RecordLocator" => 'FLIGHT1',
        ]);
        $this->aw->createTripSegment([
            "TripID" => $tripId,
            "MarketingAirlineConfirmationNumber" => "FLIGHT1",
            "DepCode" => "ORD",
            "DepDate" => "2035-06-14 20:35",
            "ArrCode" => "MSP",
            "ArrDate" => "2035-06-14 21:15",
        ]);
        $this->aw->createTripSegment([
            "TripID" => $tripId,
            "DepCode" => "MSP",
            "DepDate" => "2035-06-14 21:30",
            "ArrCode" => "JFK",
            "ArrDate" => "2035-06-14 23:15",
        ]);

        $manager = $this->container->get(Manager::class);
        $items = $manager->query((new QueryOptions())->setFormat(ItemFormatterInterface::DESKTOP)->setUser($this->user));
        $types = array_map(function (Item\ItemInterface $item) { return get_class($item); }, $items);
        $this->assertEquals([
            Item\Date::class,
            Item\AirTrip::class,
            Item\AirLayover::class,
            Item\AirTrip::class,
        ], $types);
    }

    public function _after()
    {
        $this->manager = null;

        parent::_after(); // TODO: Change the autogenerated stub
    }
}
