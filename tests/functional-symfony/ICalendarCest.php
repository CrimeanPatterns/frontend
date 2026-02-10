<?php

namespace AwardWallet\Tests\FunctionalSymfony;

use AwardWallet\MainBundle\Globals\StringUtils;

/**
 * @group frontend-functional
 */
class ICalendarCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    private $code;
    private $userId;
    private $recLoc;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->code = StringUtils::getRandomCode(32);
        $this->userId = $I->createAwUser(null, null, ['ItineraryCalendarCode' => $this->code]);
        $this->recLoc = 'RL' . $I->grabRandomString(6);
    }

    public function testSuccess(\TestSymfonyGuy $I)
    {
        $tripId = $I->haveInDatabase("Trip", $this->createTrip($this->recLoc));
        $segmentId1 = $I->createTripSegment($this->createFlight($tripId, $this->recLoc, 'LAX', 'BUF'));

        $I->sendGET("/iCal/$this->code");
        $I->see("VCALENDAR");
        $I->see("LAX");
        $I->see("BUF");
    }

    public function testFlightsNotInTravelPlan(\TestSymfonyGuy $I)
    {
        $tripId = $I->haveInDatabase("Trip", $this->createTrip($this->recLoc));
        $I->createTripSegment($this->createFlight($tripId, $this->recLoc, 'LAX', 'BUF'));
        $I->createTripSegment($this->createFlight($tripId, $this->recLoc, 'BUF', 'LAX', 'tomorrow 16:00', 'tomorrow 18:00'));

        $I->sendGET("/iCal/$this->code");
        $I->see("VCALENDAR");
        $I->see("LAX -> BUF");
        $I->see("BUF -> LAX");
        $I->dontSee("DTSTART;VALUE=DATE");
    }

    public function testTrainNotInTravelPlan(\TestSymfonyGuy $I)
    {
        $tripId = $I->haveInDatabase("Trip", $this->createTrip($this->recLoc, 3));
        $segmentId1 = $I->createTripSegment($this->createTrain($tripId, 'New York, NY', 'Boston, MA'));

        $I->sendGET("/iCal/$this->code");
        $I->see("VCALENDAR");
        $I->see("Boston, MA");
        $I->dontSee("DTSTART;VALUE=DATE");
    }

    public function testRestaurantNotInTravelPlan(\TestSymfonyGuy $I)
    {
        $restId = $I->haveInDatabase("Restaurant", $this->createRestaurant($this->recLoc));

        $I->sendGET("/iCal/$this->code");
        $I->see("VCALENDAR");
        $I->see("SUMMARY:Restaurant reservation for");
        $I->dontSee("DTSTART;VALUE=DATE");
    }

    public function testMeetingNotInTravelPlan(\TestSymfonyGuy $I)
    {
        $restId = $I->haveInDatabase("Restaurant", $this->createRestaurant($this->recLoc, '18:00:00', 2));

        $I->sendGET("/iCal/$this->code");
        $I->see("VCALENDAR");
        $I->dontSee("SUMMARY:Restaurant reservation");
        $I->dontSee("DTSTART;VALUE=DATE");
    }

    public function testReservationNotInTravelPlan(\TestSymfonyGuy $I)
    {
        $restId = $I->haveInDatabase("Reservation", $this->createReservation($this->recLoc));

        $I->sendGET("/iCal/$this->code");
        $I->see("VCALENDAR");
        $I->see("SUMMARY:Check-in to");
        $I->see("SUMMARY:Check-out");
        $I->dontSee("DTSTART;VALUE=DATE");
    }

    public function testRentalNotInTravelPlan(\TestSymfonyGuy $I)
    {
        $rentId = $I->haveInDatabase("Rental", $this->createRental($this->recLoc, 'LAX'));

        $I->sendGET("/iCal/$this->code");
        $I->see("VCALENDAR");
        $I->see("Nissan Altima");
        $I->see("National");
        $I->see("SUMMARY:Rental pickup");
        $I->see("SUMMARY:Rental dropoff");
        $I->dontSee("DTSTART;VALUE=DATE");
    }

    public function testTaxiNotInTravelPlan(\TestSymfonyGuy $I)
    {
        $taxiId = $I->haveInDatabase("Rental", $this->createTaxi($this->recLoc, 'tomorrow +1 days 16:00', 'tomorrow +1 days 18:00'));

        $I->sendGET("/iCal/$this->code");
        $I->see("VCALENDAR");
        $I->see("SUMMARY:Transfer (National)");
        $I->see("DESCRIPTION:Confirmation");
        $I->dontSee("DTSTART;VALUE=DATE");
    }

    public function testTravelPlan(\TestSymfonyGuy $I)
    {
        $tripId = $I->haveInDatabase("Trip", $this->createTrip($this->recLoc));
        $I->createTripSegment($this->createFlight($tripId, $this->recLoc, 'BUF', 'LAX'));
        $I->createTripSegment($this->createFlight($tripId, $this->recLoc, 'LAX', 'BUF', 'tomorrow +2 days 16:00', 'tomorrow +2 days 18:00'));

        $taxiId = $I->haveInDatabase("Rental", $this->createTaxi($this->recLoc, 'tomorrow +1 days 16:00', 'tomorrow +1 days 18:00'));

        $rentId = $I->haveInDatabase("Rental", $this->createRental($this->recLoc, 'LAX', 2));

        $restId = $I->haveInDatabase("Reservation", $this->createReservation($this->recLoc, 2));

        $tpId = $I->haveInDatabase("Plan", [
            "UserID" => $this->userId,
            "Name" => "Test Travel Plan",
            "StartDate" => $this->formatDate("yesterday"),
            "EndDate" => $this->formatDate("tomorrow +3 days"),
            "ShareCode" => StringUtils::getRandomCode(32),
        ]);

        $I->sendGET("/iCal/$this->code");
        $I->see("VCALENDAR");
        $I->see("LAX -> BUF");
        $I->see("BUF -> LAX");
        $I->see("DTSTART;VALUE=DATE");
        $I->see("DESCRIPTION:Agenda:");
        $I->see("Transfer (National)");
        $I->see("rental car from  (National)");
        $I->see("\nCheck-in to Santa Barbara");
        $I->see("DTSTART:");
    }

    private function createTrip($recLoc, $category = 1)
    {
        return [
            "UserID" => $this->userId,
            "RecordLocator" => $recLoc,
            "Category" => $category,
        ];
    }

    private function createFlight($tripId, $recordLocator, $depCode, $arrCode, $depDate = 'tomorrow 13:00:00', $arrDate = 'tomorrow 15:00:00')
    {
        return [
            "TripID" => $tripId,
            'FlightNumber' => (string) random_int(100, 9999),
            'DepDate' => $this->formatDate($depDate),
            'ScheduledDepDate' => $this->formatDate($depDate),
            'DepCode' => $depCode,
            'DepName' => $depCode,
            'ArrDate' => $this->formatDate($arrDate),
            'ScheduledArrDate' => $this->formatDate($arrDate),
            'ArrCode' => $arrCode,
            'ArrName' => $arrCode,
            'AirlineName' => 'Delta',
            'MarketingAirlineConfirmationNumber' => $recordLocator,
        ];
    }

    private function createTrain($tripId, $depName, $arrName, $depDate = 'tomorrow 13:00:00', $arrDate = 'tomorrow 15:00:00')
    {
        return [
            "TripID" => $tripId,
            'FlightNumber' => (string) random_int(100, 9999),
            'DepDate' => $this->formatDate($depDate),
            'ScheduledDepDate' => $this->formatDate($depDate),
            'DepName' => $depName,
            'ArrDate' => $this->formatDate($arrDate),
            'ScheduledArrDate' => $this->formatDate($arrDate),
            'ArrName' => $arrName,
            'AirlineName' => 'Delta',
        ];
    }

    private function createRestaurant($recLoc, $dateStr = 'tomorrow 18:00:00', $eventtype = 1)
    {
        return [
            "UserID" => $this->userId,
            "ConfNo" => $recLoc,
            "Name" => "Test Event",
            "Address" => "1632 Richmond Road Williamsburg, VA 23185",
            "StartDate" => $this->formatDate($dateStr),
            "EventType" => $eventtype,
            "GuestCount" => 2,
        ];
    }

    private function createRental($recLoc, $location, $days = 1, $dateStr = "tomorrow")
    {
        return [
            "UserID" => $this->userId,
            "Number" => $recLoc,
            "PickupLocation" => $location,
            "PickupDatetime" => $this->formatDate($dateStr),
            "DropoffLocation" => $location,
            "DropoffDatetime" => $this->formatDate($dateStr, $days),
            "CarModel" => "Nissan Altima",
            "RentalCompanyName" => "National",
        ];
    }

    private function createTaxi($recLoc, $depDate = 'tomorrow 16:00:00', $arrDate = 'tomorrow 17:00:00')
    {
        return [
            "UserID" => $this->userId,
            "Number" => $recLoc,
            "PickupLocation" => "LAX",
            "PickupDatetime" => $this->formatDate($depDate),
            "DropoffLocation" => "1100 Palomino Road, Santa Barbara, CA 93105, United States",
            "DropoffDatetime" => $this->formatDate($arrDate),
            "CarModel" => "Nissan Altima",
            "RentalCompanyName" => "National",
            "Type" => "taxi_ride",
        ];
    }

    private function createReservation($recLoc, $days = 1, $dateStr = "tomorrow")
    {
        return [
            "UserID" => $this->userId,
            "ConfirmationNumber" => $recLoc,
            "HotelName" => "Santa Barbara",
            "Address" => "1100 Palomino Road, Santa Barbara, CA 93105, United States",
            "CheckInDate" => $this->formatDate($dateStr),
            "CheckOutDate" => $this->formatDate($dateStr, $days),
            'Rooms' => "a:0:{}",
        ];
    }

    private function formatDate($dateStr, $daysafter = 0)
    {
        if ($daysafter > 0) {
            return date("Y-m-d", strtotime("+ {$daysafter} days", strtotime($dateStr)));
        } else {
            return date("Y-m-d H:i:s", strtotime($dateStr));
        }
    }
}
