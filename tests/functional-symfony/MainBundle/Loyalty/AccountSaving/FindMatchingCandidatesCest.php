<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Loyalty\AccountSaving;

use AwardWallet\MainBundle\Entity\Repositories\TripRepository;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\Schema\Itineraries\Flight;
use AwardWallet\Schema\Itineraries\FlightSegment;
use AwardWallet\Schema\Itineraries\MarketingCarrier;
use AwardWallet\Schema\Itineraries\TripLocation;

/**
 * @group frontend-unit
 */
class FindMatchingCandidatesCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    /**
     * @var Usr
     */
    private $user;
    /**
     * @var TripRepository
     */
    private $repo;

    public function _before(\TestSymfonyGuy $I)
    {
        $userId = $I->createAwUser();
        $this->repo = $I->grabService('doctrine')->getRepository(Trip::class);
        $this->user = $I->grabService('doctrine')->getRepository(Usr::class)->find($userId);
    }

    public function testMatchByMarketingAirlineNumber(\TestSymfonyGuy $I)
    {
        $tripId = $I->haveInDatabase("Trip", ["UserID" => $this->user->getId()]);
        $I->createTripSegment([
            "TripID" => $tripId,
            "DepCode" => "JFK",
            "DepName" => "JFK",
            "ArrCode" => "LAX",
            "ArrName" => "LAX",
            "DepDate" => date("Y-m-d 14:00"),
            "ScheduledDepDate" => date("Y-m-d 14:00"),
            "ArrDate" => date("Y-m-d 18:00"),
            "ScheduledArrDate" => date("Y-m-d 18:00"),
            "MarketingAirlineConfirmationNumber" => "REC001",
        ]);
        $I->createTripSegment([
            "TripID" => $tripId,
            "DepCode" => "JFK",
            "ArrCode" => "LAX",
            "DepDate" => date("Y-m-d 14:00"),
            "ArrDate" => date("Y-m-d 18:00"),
            "MarketingAirlineConfirmationNumber" => "REC002",
        ]);
        $trip2Id = $I->haveInDatabase("Trip", ["UserID" => $this->user->getId()]);
        $I->createTripSegment([
            "TripID" => $trip2Id,
            "DepCode" => "JFK",
            "ArrCode" => "LAX",
            "DepDate" => date("Y-m-d 14:00"),
            "ArrDate" => date("Y-m-d 18:00"),
            "MarketingAirlineConfirmationNumber" => "REC003",
        ]);
        $trip3Id = $I->haveInDatabase("Trip", ["UserID" => $this->user->getId(), "RecordLocator" => "REC001"]);
        $I->createTripSegment([
            "TripID" => $trip3Id,
            "DepCode" => "JFK",
            "ArrCode" => "LAX",
            "DepDate" => date("Y-m-d 14:00"),
            "ArrDate" => date("Y-m-d 18:00"),
            "MarketingAirlineConfirmationNumber" => "REC004",
            "FlightNumber" => "223322",
        ]);
        $trip4Id = $I->haveInDatabase("Trip", ["UserID" => $this->user->getId()]);
        $I->createTripSegment([
            "TripID" => $trip3Id,
            "DepCode" => "JFK",
            "DepName" => "JFK",
            "ArrCode" => "LAX",
            "ArrName" => "LAX",
            "DepDate" => date("Y-m-d 14:00"),
            "ScheduledDepDate" => date("Y-m-d 14:00"),
            "ArrDate" => date("Y-m-d 18:00"),
            "ScheduledArrDate" => date("Y-m-d 18:00"),
        ]);
        $flight = new Flight();
        $segment = new FlightSegment();
        $segment->marketingCarrier = new MarketingCarrier();
        $segment->marketingCarrier->confirmationNumber = "REC001";
        $segment->marketingCarrier->flightNumber = "223322";
        $segment->departure = new TripLocation();
        $segment->departure->localDateTime = date("Y-m-d 14:00");
        $segment->departure->airportCode = "PEE";
        $segment->arrival = new TripLocation();
        $segment->arrival->localDateTime = date("Y-m-d 18:00");
        $segment->arrival->airportCode = "DMD";
        $flight->segments = [
            $segment,
        ];
        $results = $this->repo->findMatchingCandidates($this->user, $flight);
        $I->assertCount(1, $results);
        /** @var Trip $match */
        $match = array_shift($results);
        $I->assertEquals($tripId, $match->getId());
    }

    public function testMatchByHiddenSegment(\TestSymfonyGuy $I)
    {
        $tripId = $I->haveInDatabase("Trip", ["UserID" => $this->user->getId()]);
        $I->createTripSegment([
            "TripID" => $tripId,
            "DepCode" => "JFK",
            "DepName" => "JFK",
            "ArrCode" => "LAX",
            "ArrName" => "LAX",
            "DepDate" => date("Y-m-d 14:00"),
            "ScheduledDepDate" => date("Y-m-d 14:00"),
            "ArrDate" => date("Y-m-d 18:00"),
            "ScheduledArrDate" => date("Y-m-d 18:00"),
            "MarketingAirlineConfirmationNumber" => "REC001",
            "Hidden" => 1,
        ]);
        $flight = new Flight();
        $segment = new FlightSegment();
        $segment->marketingCarrier = new MarketingCarrier();
        $segment->marketingCarrier->confirmationNumber = "REC001";
        $segment->marketingCarrier->flightNumber = "223322";
        $segment->departure = new TripLocation();
        $segment->departure->localDateTime = date("Y-m-d 14:00");
        $segment->departure->airportCode = "PEE";
        $segment->arrival = new TripLocation();
        $segment->arrival->localDateTime = date("Y-m-d 18:00");
        $segment->arrival->airportCode = "DMD";
        $flight->segments = [
            $segment,
        ];
        $results = $this->repo->findMatchingCandidates($this->user, $flight);
        $I->assertCount(0, $results);
    }
}
