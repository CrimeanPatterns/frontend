<?php

namespace AwardWallet\Tests\Unit\AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\FlightInfo\FlightInfo;
use Doctrine\ORM\EntityManager;

/**
 * @group frontend-unit
 */
class FlightInfoCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    /**
     * @var FlightInfo
     */
    private $flightInfo;
    /**
     * @var Usr
     */
    private $user;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->flightInfo = $I->grabService(FlightInfo::class);
        $this->user = $I->grabService('doctrine')->getRepository(Usr::class)->find($I->createAwUser());
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $this->flightInfo = null;
        $this->user = null;
    }

    public function updateTripSegments(\TestSymfonyGuy $I)
    {
        $depDate = date("Y-m-d 14:00:00", strtotime("tomorrow"));
        $arrDate = date("Y-m-d 18:00:00", strtotime("tomorrow"));
        $flightNumber = random_int(111111, 999999);
        $I->executeQuery("delete from FlightInfo 
        where `Airline` = 'AA' AND `FlightNumber` = '$flightNumber' AND `FlightDate` = '$depDate'
        AND `DepCode` = 'JFK' AND`ArrCode` = 'LAX'");
        $flightInfoId = $I->haveInDatabase("FlightInfo", [
            "Airline" => "AA",
            "FlightNumber" => $flightNumber,
            "FlightDate" => date("Y-m-d", strtotime($depDate)),
            "DepCode" => "JFK",
            "ArrCode" => "LAX",
            "UpdatesCount" => 0,
            "Properties" => 'a:3:{s:4:"info";a:10:{s:7:"DepDate";s:23:"2018-10-28T09:35:00.000";s:10:"DepDateUtc";s:24:"2018-10-28T06:35:00.000Z";s:7:"ArrDate";s:23:"2018-10-28T13:30:00.000";s:10:"ArrDateUtc";s:24:"2018-10-28T09:30:00.000Z";s:8:"Aircraft";s:11:"Airbus A321";s:17:"DepartureTerminal";s:1:"D";s:4:"Gate";s:1:"6";s:11:"ArrivalGate";s:1:"8";s:15:"ArrivalTerminal";s:1:"4";s:12:"BaggageClaim";s:4:"A4.4";}s:26:"flight_stats.flight_status";a:7:{s:7:"DepDate";s:23:"2018-10-28T09:35:00.000";s:10:"DepDateUtc";s:24:"2018-10-28T06:35:00.000Z";s:7:"ArrDate";s:23:"2018-10-28T13:30:00.000";s:10:"ArrDateUtc";s:24:"2018-10-28T09:30:00.000Z";s:8:"Aircraft";s:11:"Airbus A321";s:17:"DepartureTerminal";s:1:"D";s:4:"Gate";s:1:"6";}s:30:"flight_stats.flight_status:log";a:2:{s:25:"2018-10-26T10:30:16+00:00";a:6:{s:7:"DepDate";s:23:"2018-10-28T09:35:00.000";s:10:"DepDateUtc";s:24:"2018-10-28T06:35:00.000Z";s:7:"ArrDate";s:23:"2018-10-28T13:30:00.000";s:10:"ArrDateUtc";s:24:"2018-10-28T09:30:00.000Z";s:8:"Aircraft";s:23:"Airbus A321 (sharklets)";s:17:"DepartureTerminal";s:1:"D";}s:25:"2018-10-28T02:35:00+00:00";a:7:{s:7:"DepDate";s:23:"2018-10-28T09:35:00.000";s:10:"DepDateUtc";s:24:"2018-10-28T06:35:00.000Z";s:7:"ArrDate";s:23:"2018-10-28T13:30:00.000";s:10:"ArrDateUtc";s:24:"2018-10-28T09:30:00.000Z";s:8:"Aircraft";s:11:"Airbus A321";s:17:"DepartureTerminal";s:1:"D";s:4:"Gate";s:1:"6";}}}',
        ]);
        /** @var EntityManager $em */
        $em = $I->grabService("doctrine.orm.entity_manager");
        /** @var \AwardWallet\MainBundle\Entity\FlightInfo $flightInfoEntity */
        $flightInfoEntity = $em->find(\AwardWallet\MainBundle\Entity\FlightInfo::class, $flightInfoId);
        $I->assertNotEmpty($flightInfoEntity);

        $trip1Id = $I->haveInDatabase("Trip", ["UserID" => $this->user->getUserid(), "RecordLocator" => "REC001"]);
        $trip1Segment1Id = $I->createTripSegment([
            "TripID" => $trip1Id,
            "DepCode" => "JFK",
            "DepName" => "JFK",
            "ArrCode" => "LAX",
            "ArrName" => "LAX",
            "FlightNumber" => $flightNumber,
            "DepDate" => $depDate,
            "ArrDate" => $arrDate,
            "ScheduledDepDate" => $depDate,
            "ScheduledArrDate" => $arrDate,
            "MarketingAirlineConfirmationNumber" => "REC001",
            "FlightInfoID" => $flightInfoId,
        ]);
        $trip2Id = $I->haveInDatabase("Trip", ["UserID" => $this->user->getUserid()]);
        $trip2Segment1Id = $I->createTripSegment([
            "TripID" => $trip2Id,
            "DepCode" => "JFK",
            "DepName" => "JFK",
            "ArrCode" => "LAX",
            "ArrName" => "LAX",
            "FlightNumber" => $flightNumber,
            "DepDate" => $depDate,
            "ArrDate" => $arrDate,
            "ScheduledDepDate" => $depDate,
            "ScheduledArrDate" => $arrDate,
            "MarketingAirlineConfirmationNumber" => "REC001",
            "FlightInfoID" => $flightInfoId,
        ]);
        $trip3Id = $I->haveInDatabase("Trip", ["UserID" => $this->user->getUserid()]);
        $trip3Segment1Id = $I->createTripSegment([
            "TripID" => $trip2Id,
            "DepCode" => "JFK",
            "DepName" => "JFK",
            "ArrCode" => "LAX",
            "ArrName" => "LAX",
            "FlightNumber" => $flightNumber,
            "DepDate" => $depDate,
            "ArrDate" => $arrDate,
            "ScheduledDepDate" => $depDate,
            "ScheduledArrDate" => $arrDate,
            "MarketingAirlineConfirmationNumber" => "REC001",
        ]);

        $this->flightInfo->updateTripSegments($flightInfoEntity);

        foreach ([$trip1Segment1Id, $trip2Segment1Id] as $segmentId) {
            $segment = $I->query("select ts.* from TripSegment ts where ts.TripSegmentID = $segmentId")->fetch(\PDO::FETCH_ASSOC);

            $I->assertEquals("2018-10-28 09:35:00", $segment["DepDate"]);
            $I->assertEquals("2018-10-28 13:30:00", $segment["ArrDate"]);
            $I->assertEquals($depDate, $segment["ScheduledDepDate"]);
            $I->assertEquals($arrDate, $segment["ScheduledArrDate"]);

            $I->assertEquals("6", $segment["DepartureGate"]);
            $I->assertEquals("8", $segment["ArrivalGate"]);

            $I->assertEquals("D", $segment["DepartureTerminal"]);
            $I->assertEquals("4", $segment["ArrivalTerminal"]);

            $aircraftId = $I->grabFromDatabase("Aircraft", "AircraftID", ["Name" => "Airbus A321"]);
            $I->assertNotEmpty($aircraftId);
            $I->assertEquals($aircraftId, $segment["AircraftID"]);

            $I->assertEquals("A4.4", $segment["BaggageClaim"]);
        }

        foreach ([$trip3Segment1Id] as $segmentId) {
            $segment = $I->query("select ts.* from TripSegment ts where ts.TripSegmentID = $segmentId")->fetch(\PDO::FETCH_ASSOC);
            $I->assertEquals($depDate, $segment["DepDate"]);
            $I->assertEquals($arrDate, $segment["ArrDate"]);

            $I->assertEmpty($segment["DepartureGate"]);
            $I->assertEmpty($segment["ArrivalGate"]);
            $I->assertEmpty($segment["DepartureTerminal"]);
            $I->assertEmpty($segment["ArrivalTerminal"]);
            $I->assertEmpty($segment["AircraftID"]);
            $I->assertEmpty($segment["BaggageClaim"]);
        }
    }
}
