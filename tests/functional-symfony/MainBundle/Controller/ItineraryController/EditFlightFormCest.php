<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\ItineraryController;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use AwardWallet\Tests\FunctionalSymfony\Traits\StaffUser;

/**
 * @group frontend-functional
 */
class EditFlightFormCest extends EditItineraryFormCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use StaffUser;
    use LoggedIn;

    public const EDIT_PATH = '/flight/{id}/edit';
    public const TABLE = 'Trip';
    public const ID_FIELD = 'TripID';
    public const RECORD_LOCATOR = 'recordLocator';
    public const REPOSITORY = Trip::class;

    public function editSegment(\TestSymfonyGuy $I)
    {
        $newRecordLocator = parent::editSegment($I);
        $I->seeInDatabase('Trip', [
            'TripID' => $this->itinerary->getId(),
            'RecordLocator' => $newRecordLocator,
            'UserId' => $this->user->getUserid(),
            'UserAgentId' => null,
            'notes' => 'some other notes',
        ]);
        /** @var Trip $trip */
        $trip = $this->itinerary;
        $I->seeInDatabase('TripSegment', [
            'TripSegmentID' => $trip->getSegments()[0]->getTripsegmentid(),
            'AirlineName' => 'NEW AIRLINE NAME',
            'FlightNumber' => '777',
            'DepCode' => 'ORD',
            'DepDate' => '2018-01-01 10:00:00',
            'ScheduledDepDate' => '2018-01-01 10:00:00',
            'ArrCode' => 'SVO',
            'ArrDate' => '2018-01-02 11:00:00',
            'ScheduledArrDate' => '2018-01-02 11:00:00',
        ]);
        $I->seeOptionIsSelected('Travel Timeline Of', $this->user->getFullName());
    }

    public function addSegment(\TestSymfonyGuy $I)
    {
        $I->stopFollowingRedirects();
        $I->amOnPage($this->getEditPath($this->itinerary->getId()));
        $I->submitForm('form', array_merge($this->getFields(), [
            "flight[segments][1][airlineName]" => "NEW AIRLINE NAME",
            "flight[segments][1][flightNumber]" => "777",
            "flight[segments][1][departureAirport]" => "ORD",
            "flight[segments][1][departureDate][date]" => "2018-01-01",
            "flight[segments][1][departureDate][time]" => "10:00 AM",
            "flight[segments][1][arrivalAirport]" => "SVO",
            "flight[segments][1][arrivalDate][date]" => "2018-01-02",
            "flight[segments][1][arrivalDate][time]" => "11:00 AM",
        ]));
        $this->assertRedirect($I, $this->itinerary);
        $I->amOnPage($this->getEditPath($this->itinerary->getId()));
        $I->seeInFormFields('form', array_merge($this->getFields(), [
            "flight[segments][1][airlineName]" => "NEW AIRLINE NAME",
            "flight[segments][1][flightNumber]" => "777",
            "flight[segments][1][departureAirport]" => "ORD",
            "flight[segments][1][departureDate][date]" => "2018-01-01",
            "flight[segments][1][departureDate][time]" => "10:00 AM",
            "flight[segments][1][arrivalAirport]" => "SVO",
            "flight[segments][1][arrivalDate][date]" => "2018-01-02",
            "flight[segments][1][arrivalDate][time]" => "11:00 AM",
        ]));
        $I->seeInDatabase('TripSegment', [
            'TripId' => $this->itinerary->getId(),
            'AirlineName' => 'Aeroflot',
            'FlightNumber' => '100',
            'DepCode' => 'SVO',
            "DepName" => "Moscow Sheremetyevo International Airport",
            'DepDate' => '2017-01-01 00:00:00',
            'ScheduledDepDate' => '2017-01-01 00:00:00',
            'ArrCode' => 'JFK',
            "ArrName" => "New York John F. Kennedy International Airport",
            'ArrDate' => '2017-01-01 01:00:00',
            'ScheduledArrDate' => '2017-01-01 01:00:00',
        ]);
        $I->seeInDatabase('TripSegment', [
            'TripId' => $this->itinerary->getId(),
            'AirlineName' => 'NEW AIRLINE NAME',
            'FlightNumber' => '777',
            'DepCode' => 'ORD',
            'DepName' => 'Chicago O\'Hare International Airport',
            'DepDate' => '2018-01-01 10:00:00',
            'ScheduledDepDate' => '2018-01-01 10:00:00',
            'ArrCode' => 'SVO',
            "ArrName" => "Moscow Sheremetyevo International Airport",
            'ArrDate' => '2018-01-02 11:00:00',
            'ScheduledArrDate' => '2018-01-02 11:00:00',
        ]);
    }

    // //    TODO fix removeSegment test
    //    public function removeSegment(\TestSymfonyGuy $I)
    //    {
    //        $I->stopFollowingRedirects();
    //        $I->createTripSegment([
    //                'TripId' => $this->itinerary->getId(),
    //                'AirlineName' => 'NEW AIRLINE NAME',
    //                'FlightNumber' => '777',
    //                'DepCode' => 'ORD',
    //                'DepName' => 'O\'Hare International Airport',
    //                'DepDate' => '2018-01-01 10:00:00',
    //                'ScheduledDepDate' => '2018-01-01 10:00:00',
    //                'ArrCode' => 'SVO',
    //                "ArrName" => "Sheremetyevo International Airport",
    //                'ArrDate' => '2018-01-01 11:00:00',
    //                'ScheduledArrDate' => '2018-01-01 11:00:00'
    //            ]
    //        );
    //        $I->amOnPage($this->getEditPath($this->itinerary->getId()));
    //        $I->seeInFormFields('form', array_merge($this->getFields(), $this->getNewFields(1)));
    //        $token = $I->grabValueFrom('#trip__token');
    //        $I->sendPOST($this->getEditPath($this->itinerary->getId()), http_build_query([
    //            "flight[owner]"                         => 0,
    //            "flight[confirmationNumber]"            => $this->testRecordLocator,
    //            "flight[segments][0][airlineName]"      => "Aeroflot",
    //            "flight[segments][0][flightNumber]"     => "100",
    //            "flight[segments][0][departureAirport]" => "SVO",
    //            "flight[segments][0][departureDate][date]"    => "2017-01-01",
    //            "flight[segments][0][departureDate][time]"    => "00:00",
    //            "flight[segments][0][arrivalAirport]"   => "JFK",
    //            "flight[segments][0][arrivalDate][date]"    => "2017-01-01",
    //            "flight[segments][0][arrivalDate][time]"    => "01:00",
    //            "flight[notes]"                         => 'some notes',
    //            "flight[_token]"                        => $token
    //        ]));
    //        $this->assertRedirect($I, $this->itinerary);
    //        $I->amOnPage($this->getEditPath($this->itinerary->getId()));
    //        $I->dontSeeInFormFields('form', [
    //            "flight[segments][1][airlineName]"      => "NEW AIRLINE NAME",
    //            "flight[segments][1][flightNumber]"     => "777",
    //            "flight[segments][1][departureAirport]" => "ORD",
    //            "flight[segments][1][departureDate][date]"    => "2018-01-01",
    //            "flight[segments][1][departureDate][time]"    => "10:00",
    //            "flight[segments][1][arrivalAirport]"   => "SVO",
    //            "flight[segments][1][arrivalDate][date]"    => "2018-01-01",
    //            "flight[segments][1][arrivalDate][time]"    => "11:00"
    //        ]);
    //    }

    protected function createItineraryRow(\TestSymfonyGuy $I)
    {
        $tripId = $I->haveInDatabase(self::TABLE, [
            self::RECORD_LOCATOR => $this->testRecordLocator,
            "Hidden" => false,
            "Parsed" => false,
            'UserID' => $this->user->getUserid(),
            "Notes" => "some notes",
            "Moved" => false,
            "UpdateDate" => date('Y-m-d H:i:s'),
            "Category" => TRIP_CATEGORY_AIR,
            "CreateDate" => date('Y-m-d H:i:s'),
            "Cancelled" => false,
            "Modified" => true,
        ]);
        $I->createTripSegment(
            [
                "TripID" => $tripId,
                "DepDate" => "2017-01-01 00:00:00",
                "ArrDate" => "2017-01-01 01:00:00",
                "DepCode" => "SVO",
                "ArrCode" => "JFK",
                "DepName" => "Moscow Sheremetyevo International Airport",
                "ArrName" => "New York John F. Kennedy International Airport",
                "FlightNumber" => "100",
                "AirlineName" => "Aeroflot",
                "ScheduledDepDate" => "2017-02-01 02:00:00",
                "ScheduledArrDate" => "2017-02-01 03:00:00",
                'MarketingAirlineConfirmationNumber' => $this->testRecordLocator,
            ]
        );

        return $tripId;
    }

    protected function getFields()
    {
        return [
            "flight[confirmationNumber]" => $this->itinerary->getConfirmationNumber(),
            "flight[segments][0][airlineName]" => "Aeroflot",
            "flight[segments][0][flightNumber]" => "100",
            "flight[segments][0][departureAirport]" => "SVO",
            "flight[segments][0][departureDate][date]" => "2017-01-01",
            "flight[segments][0][departureDate][time]" => "12:00 AM",
            "flight[segments][0][arrivalAirport]" => "JFK",
            "flight[segments][0][arrivalDate][date]" => "2017-01-01",
            "flight[segments][0][arrivalDate][time]" => "1:00 AM",
            "flight[notes]" => "some notes",
        ];
    }

    protected function getNewFields(string $recordLocator)
    {
        return [
            "flight[confirmationNumber]" => $recordLocator,
            "flight[segments][0][airlineName]" => "NEW AIRLINE NAME",
            "flight[segments][0][flightNumber]" => "777",
            "flight[segments][0][departureAirport]" => "ORD",
            "flight[segments][0][departureDate][date]" => "2018-01-01",
            "flight[segments][0][departureDate][time]" => "10:00 AM",
            "flight[segments][0][arrivalAirport]" => "SVO",
            "flight[segments][0][arrivalDate][date]" => "2018-01-02",
            "flight[segments][0][arrivalDate][time]" => "11:00 AM",
            "flight[notes]" => "some other notes",
        ];
    }

    /**
     * @param Itinerary|Trip $itinerary
     */
    protected function assertRedirect(\TestSymfonyGuy $I, Itinerary $itinerary)
    {
        $I->seeRedirectTo($this->router->generate('aw_timeline_show_trip', ['tripId' => $itinerary->getId()]));
    }
}
