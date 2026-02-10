<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Loyalty\AccountSaving;

use AwardWallet\MainBundle\Email\ParsedEmailSource;
use AwardWallet\MainBundle\Entity\FlightInfo;
use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\ItinerariesProcessor;
use AwardWallet\MainBundle\Loyalty\AccountSaving\SavingOptions;
use AwardWallet\Schema\Itineraries\Address;
use AwardWallet\Schema\Itineraries\Airline;
use AwardWallet\Schema\Itineraries\Flight;
use AwardWallet\Schema\Itineraries\FlightSegment;
use AwardWallet\Schema\Itineraries\MarketingCarrier;
use AwardWallet\Schema\Itineraries\TripLocation;

/**
 * @group frontend-unit
 */
class FlightInfoCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    private ?Usr $user;

    private ?ItinerariesProcessor $itinerariesProcessor;

    public function _before(\TestSymfonyGuy $I)
    {
        /** @var Usr $user */
        $this->user = $I->grabService('doctrine')->getRepository(Usr::class)->find($I->createAwUser());
        $this->itinerariesProcessor = $I->grabService(ItinerariesProcessor::class);
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $this->itinerariesProcessor = null;
    }

    public function addFlightInfo(\TestSymfonyGuy $I)
    {
        $depDate = date("Y-m-d 14:00:00", strtotime("tomorrow"));
        $arrDate = date("Y-m-d 18:00:00", strtotime("tomorrow"));
        $flightNumber = random_int(1111, 9999);
        $flightNumberWithCode = 'AA' . $flightNumber;

        $I->executeQuery("delete from FlightInfo 
        where `Airline` = 'AA' AND `FlightNumber` = '$flightNumber' AND `FlightDate` = '$depDate'
        AND `DepCode` = 'JFK' AND`ArrCode` = 'LAX'");
        $I->haveInDatabase("FlightInfo", [
            "Airline" => "AA",
            "State" => FlightInfo::STATE_CHECKED,
            "FlightNumber" => $flightNumber,
            "FlightDate" => date("Y-m-d", strtotime($depDate)),
            "DepCode" => "JFK",
            "ArrCode" => "LAX",
            "UpdatesCount" => 0,
            "Properties" => 'a:3:{s:4:"info";a:10:{s:7:"DepDate";s:23:"2018-10-28T09:35:00.000";s:10:"DepDateUtc";s:24:"2018-10-28T06:35:00.000Z";s:7:"ArrDate";s:23:"2018-10-28T13:30:00.000";s:10:"ArrDateUtc";s:24:"2018-10-28T09:30:00.000Z";s:8:"Aircraft";s:11:"Airbus A321";s:17:"DepartureTerminal";s:1:"D";s:4:"Gate";s:1:"6";s:11:"ArrivalGate";s:1:"8";s:15:"ArrivalTerminal";s:1:"4";s:12:"BaggageClaim";s:4:"A4.4";}s:26:"flight_stats.flight_status";a:7:{s:7:"DepDate";s:23:"2018-10-28T09:35:00.000";s:10:"DepDateUtc";s:24:"2018-10-28T06:35:00.000Z";s:7:"ArrDate";s:23:"2018-10-28T13:30:00.000";s:10:"ArrDateUtc";s:24:"2018-10-28T09:30:00.000Z";s:8:"Aircraft";s:11:"Airbus A321";s:17:"DepartureTerminal";s:1:"D";s:4:"Gate";s:1:"6";}s:30:"flight_stats.flight_status:log";a:2:{s:25:"2018-10-26T10:30:16+00:00";a:6:{s:7:"DepDate";s:23:"2018-10-28T09:35:00.000";s:10:"DepDateUtc";s:24:"2018-10-28T06:35:00.000Z";s:7:"ArrDate";s:23:"2018-10-28T13:30:00.000";s:10:"ArrDateUtc";s:24:"2018-10-28T09:30:00.000Z";s:8:"Aircraft";s:23:"Airbus A321 (sharklets)";s:17:"DepartureTerminal";s:1:"D";}s:25:"2018-10-28T02:35:00+00:00";a:7:{s:7:"DepDate";s:23:"2018-10-28T09:35:00.000";s:10:"DepDateUtc";s:24:"2018-10-28T06:35:00.000Z";s:7:"ArrDate";s:23:"2018-10-28T13:30:00.000";s:10:"ArrDateUtc";s:24:"2018-10-28T09:30:00.000Z";s:8:"Aircraft";s:11:"Airbus A321";s:17:"DepartureTerminal";s:1:"D";s:4:"Gate";s:1:"6";}}}',
        ]);

        $flight = new Flight();
        $segment = new FlightSegment();
        $segment->marketingCarrier = new MarketingCarrier();
        $segment->marketingCarrier->confirmationNumber = "REC001";
        $segment->marketingCarrier->flightNumber = $flightNumberWithCode;
        $segment->marketingCarrier->airline = new Airline();
        $segment->marketingCarrier->airline->name = "American Airlines";
        $segment->marketingCarrier->airline->iata = "AA";
        $segment->marketingCarrier->airline->icao = "AAL";

        $departure = new TripLocation();
        $departure->airportCode = "JFK";
        $departure->name = "JFK Airport";
        $departure->address = new Address();
        $departure->address->text = "JFK Airport";
        $departure->localDateTime = $depDate;
        $segment->departure = $departure;

        $arrival = new TripLocation();
        $arrival->airportCode = "LAX";
        $arrival->name = "LAX Airport";
        $arrival->address = new Address();
        $arrival->address->text = "LAX Airport";
        $arrival->localDateTime = $arrDate;
        $segment->arrival = $arrival;
        $flight->segments = [
            $segment,
        ];

        $this->itinerariesProcessor->save([$flight], SavingOptions::savingByEmail(new Owner($this->user, null), 123, new ParsedEmailSource(ParsedEmailSource::SOURCE_PLANS, 'test@test.com')));
        $I->grabService('doctrine.orm.entity_manager')->flush();

        $I->assertEquals(1, $I->grabNumRecords("Trip", ["UserID" => $this->user->getUserid(), "Hidden" => 0]));
        $segment = $I->query("select ts.* from TripSegment ts
        join Trip t on t.TripID = ts.TripID where t.UserID = {$this->user->getUserid()}")->fetch(\PDO::FETCH_ASSOC);

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
}
