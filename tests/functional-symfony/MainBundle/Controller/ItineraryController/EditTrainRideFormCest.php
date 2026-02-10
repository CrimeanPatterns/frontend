<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\ItineraryController;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use AwardWallet\Tests\FunctionalSymfony\Traits\StaffUser;

/**
 * @group frontend-functional
 * @group moscow
 */
class EditTrainRideFormCest extends EditItineraryFormCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use StaffUser;
    use LoggedIn;

    public const EDIT_PATH = '/train-ride/{id}/edit';
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
            'Phone' => 'NEW_PHONE',
            'notes' => 'some other notes',
        ]);
        /** @var Trip $trip */
        $trip = $this->itinerary;
        $I->seeInDatabase('TripSegment', [
            'TripSegmentID' => $trip->getSegments()[0]->getTripsegmentid(),
            'AirlineName' => 'NEW_CARRIER',
            'FlightNumber' => 'NEW_ROUTE',
            'DepCode' => 'SEC',
            'DepDate' => '2018-06-03 10:10:00',
            'ScheduledDepDate' => '2018-06-03 10:10:00',
            'ArrCode' => 'ECO',
            'ArrDate' => '2018-06-05 10:10:00',
            'ScheduledArrDate' => '2018-06-05 10:10:00',
        ]);
        $I->seeOptionIsSelected('Travel Timeline Of', $this->user->getFullName());
    }

    public function addSegment(\TestSymfonyGuy $I)
    {
        $I->stopFollowingRedirects();
        $I->amOnPage($this->getEditPath($this->itinerary->getId()));
        $I->submitForm('form', array_merge($this->getFields(), [
            "train[segments][1][carrier]" => "NEW_CARRIER",
            "train[segments][1][route]" => "NEW_ROUTE",
            "train[segments][1][departureStationCode]" => "SEC",
            "train[segments][1][departureStation]" => "Sochi, Russia",
            "train[segments][1][departureDate][date]" => "2018-06-03",
            "train[segments][1][departureDate][time]" => "10:10 AM",
            "train[segments][1][arrivalStationCode]" => "ECO",
            "train[segments][1][arrivalStation]" => "Murmansk, Russia",
            "train[segments][1][arrivalDate][date]" => "2018-06-05",
            "train[segments][1][arrivalDate][time]" => "10:10 AM",
            "train[phone]" => "NEW_PHONE",
        ]));
        $this->assertRedirect($I, $this->itinerary);
        $I->amOnPage($this->getEditPath($this->itinerary->getId()));
        $I->seeInFormFields('form', array_merge($this->getFields(), [
            "train[segments][1][carrier]" => "NEW_CARRIER",
            "train[segments][1][route]" => "NEW_ROUTE",
            "train[segments][1][departureStationCode]" => "SEC",
            "train[segments][1][departureStation]" => "Sochi, Russia",
            "train[segments][1][departureDate][date]" => "2018-06-03",
            "train[segments][1][departureDate][time]" => "10:10 AM",
            "train[segments][1][arrivalStationCode]" => "ECO",
            "train[segments][1][arrivalStation]" => "Murmansk, Russia",
            "train[segments][1][arrivalDate][date]" => "2018-06-05",
            "train[segments][1][arrivalDate][time]" => "10:10 AM",
            "train[phone]" => "NEW_PHONE",
        ]));
        $I->seeInDatabase('TripSegment', [
            'TripId' => $this->itinerary->getId(),
            'AirlineName' => 'TEST_CARRIER',
            'FlightNumber' => 'TEST_ROUTE',
            'DepCode' => 'TES',
            "DepName" => "Moscow, Russia",
            'DepDate' => '2017-06-03 11:11:00',
            'ScheduledDepDate' => '2017-06-03 11:11:00',
            'ArrCode' => 'EST',
            "ArrName" => "Perm, Russia",
            'ArrDate' => '2017-06-05 11:11:00',
            'ScheduledArrDate' => '2017-06-05 11:11:00',
        ]);
        $I->seeInDatabase('TripSegment', [
            'TripId' => $this->itinerary->getId(),
            'AirlineName' => 'NEW_CARRIER',
            'FlightNumber' => 'NEW_ROUTE',
            'DepCode' => 'SEC',
            'DepName' => 'Sochi, Russia',
            'DepDate' => '2018-06-03 10:10:00',
            'ScheduledDepDate' => '2018-06-03 10:10:00',
            'ArrCode' => 'ECO',
            "ArrName" => "Murmansk, Russia",
            'ArrDate' => '2018-06-05 10:10:00',
            'ScheduledArrDate' => '2018-06-05 10:10:00',
        ]);
    }

    protected function createItineraryRow(\TestSymfonyGuy $I)
    {
        $tripId = $I->haveInDatabase(self::TABLE, [
            self::RECORD_LOCATOR => $this->testRecordLocator,
            "Hidden" => false,
            "Parsed" => false,
            'UserID' => $this->user->getUserid(),
            "Phone" => 'TEST_PHONE',
            "Notes" => "some notes",
            "Moved" => false,
            "UpdateDate" => date('Y-m-d H:i:s'),
            "Category" => Trip::CATEGORY_TRAIN,
            "CreateDate" => date('Y-m-d H:i:s'),
            "Cancelled" => false,
            "Modified" => true,
        ]);
        $I->createTripSegment(
            [
                "TripID" => $tripId,
                "DepDate" => "2017-06-03 11:11:00",
                "ArrDate" => "2017-06-05 11:11:00",
                "DepCode" => "TES",
                "ArrCode" => "EST",
                "DepName" => "Moscow, Russia",
                "ArrName" => "Perm, Russia",
                "FlightNumber" => "TEST_ROUTE",
                "AirlineName" => "TEST_CARRIER",
                "ScheduledDepDate" => "2017-08-03 10:11:00",
                "ScheduledArrDate" => "2017-08-05 10:11:00",
            ]
        );

        return $tripId;
    }

    protected function getFields()
    {
        return [
            "train[confirmationNumber]" => $this->itinerary->getConfirmationNumber(),
            "train[segments][0][carrier]" => "TEST_CARRIER",
            "train[segments][0][route]" => "TEST_ROUTE",
            "train[segments][0][departureStationCode]" => "TES",
            "train[segments][0][departureStation]" => "Moscow, Russia",
            "train[segments][0][departureDate][date]" => "2017-06-03",
            "train[segments][0][departureDate][time]" => "11:11 AM",
            "train[segments][0][arrivalStationCode]" => "EST",
            "train[segments][0][arrivalStation]" => "Perm, Russia",
            "train[segments][0][arrivalDate][date]" => "2017-06-05",
            "train[segments][0][arrivalDate][time]" => "11:11 AM",
            "train[phone]" => "TEST_PHONE",
            "train[notes]" => "some notes",
        ];
    }

    protected function getNewFields(string $recordLocator)
    {
        return [
            "train[confirmationNumber]" => $recordLocator,
            "train[segments][0][carrier]" => "NEW_CARRIER",
            "train[segments][0][route]" => "NEW_ROUTE",
            "train[segments][0][departureStationCode]" => "SEC",
            "train[segments][0][departureStation]" => "Sochi, Russia",
            "train[segments][0][departureDate][date]" => "2018-06-03",
            "train[segments][0][departureDate][time]" => "10:10 AM",
            "train[segments][0][arrivalStationCode]" => "ECO",
            "train[segments][0][arrivalStation]" => "Murmansk, Russia",
            "train[segments][0][arrivalDate][date]" => "2018-06-05",
            "train[segments][0][arrivalDate][time]" => "10:10 AM",
            "train[phone]" => "NEW_PHONE",
            "train[notes]" => "some other notes",
        ];
    }

    /**
     * @param Itinerary|Trip $itinerary
     */
    protected function assertRedirect(\TestSymfonyGuy $I, Itinerary $itinerary, ?Useragent $userAgent = null)
    {
        $I->seeRedirectTo($this->router->generate('aw_timeline_show_trip', ['tripId' => $itinerary->getId()]));
    }
}
