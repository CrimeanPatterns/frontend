<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\ItineraryController;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use AwardWallet\Tests\FunctionalSymfony\Traits\StaffUser;

/**
 * @group frontend-functional
 * @group moscow
 */
class EditFerryRideFormCest extends EditItineraryFormCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use StaffUser;
    use LoggedIn;

    public const EDIT_PATH = '/ferry-ride/{id}/edit';
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
            'AirlineName' => 'NEW_COMPANY',
            'FlightNumber' => 'NEW_ROUTE',
            'DepDate' => '2018-06-03 10:10:00',
            'ScheduledDepDate' => '2018-06-03 10:10:00',
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
            "ferry[segments][1][ferryCompany]" => "NEW_COMPANY",
            "ferry[segments][1][route]" => "NEW_ROUTE",
            "ferry[segments][1][departurePort]" => "Sochi, Russia",
            "ferry[segments][1][departureDate][date]" => "2018-06-03",
            "ferry[segments][1][departureDate][time]" => "10:10 AM",
            "ferry[segments][1][arrivalPort]" => "Murmansk, Russia",
            "ferry[segments][1][arrivalDate][date]" => "2018-06-05",
            "ferry[segments][1][arrivalDate][time]" => "10:10 AM",
            "ferry[phone]" => "NEW_PHONE",
        ]));
        $this->assertRedirect($I, $this->itinerary);
        $I->amOnPage($this->getEditPath($this->itinerary->getId()));
        $I->seeInFormFields('form', array_merge($this->getFields(), [
            "ferry[segments][1][ferryCompany]" => "NEW_COMPANY",
            "ferry[segments][1][route]" => "NEW_ROUTE",
            "ferry[segments][1][departurePort]" => "Sochi, Russia",
            "ferry[segments][1][departureDate][date]" => "2018-06-03",
            "ferry[segments][1][departureDate][time]" => "10:10 AM",
            "ferry[segments][1][arrivalPort]" => "Murmansk, Russia",
            "ferry[segments][1][arrivalDate][date]" => "2018-06-05",
            "ferry[segments][1][arrivalDate][time]" => "10:10 AM",
            "ferry[phone]" => "NEW_PHONE",
        ]));
        $I->seeInDatabase('TripSegment', [
            'TripId' => $this->itinerary->getId(),
            'AirlineName' => 'TEST_COMPANY',
            'FlightNumber' => 'TEST_ROUTE',
            "DepName" => "Moscow, Russia",
            'DepDate' => '2017-06-03 11:11:00',
            'ScheduledDepDate' => '2017-06-03 11:11:00',
            "ArrName" => "Perm, Russia",
            'ArrDate' => '2017-06-05 11:11:00',
            'ScheduledArrDate' => '2017-06-05 11:11:00',
        ]);
        $I->seeInDatabase('TripSegment', [
            'TripId' => $this->itinerary->getId(),
            'AirlineName' => 'NEW_COMPANY',
            'FlightNumber' => 'NEW_ROUTE',
            'DepName' => 'Sochi, Russia',
            'DepDate' => '2018-06-03 10:10:00',
            'ScheduledDepDate' => '2018-06-03 10:10:00',
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
            "Category" => Trip::CATEGORY_FERRY,
            "CreateDate" => date('Y-m-d H:i:s'),
            "Cancelled" => false,
            "Modified" => true,
        ]);
        $I->createTripSegment(
            [
                "TripID" => $tripId,
                "DepDate" => "2017-06-03 11:11:00",
                "ArrDate" => "2017-06-05 11:11:00",
                "DepName" => "Moscow, Russia",
                "ArrName" => "Perm, Russia",
                "FlightNumber" => "TEST_ROUTE",
                "AirlineName" => "TEST_COMPANY",
                "ScheduledDepDate" => "2017-08-03 10:11:00",
                "ScheduledArrDate" => "2017-08-05 10:11:00",
            ]
        );

        return $tripId;
    }

    protected function getFields()
    {
        return [
            "ferry[confirmationNumber]" => $this->itinerary->getConfirmationNumber(),
            "ferry[segments][0][ferryCompany]" => "TEST_COMPANY",
            "ferry[segments][0][route]" => "TEST_ROUTE",
            "ferry[segments][0][departurePort]" => "Moscow, Russia",
            "ferry[segments][0][departureDate][date]" => "2017-06-03",
            "ferry[segments][0][departureDate][time]" => "11:11 AM",
            "ferry[segments][0][arrivalPort]" => "Perm, Russia",
            "ferry[segments][0][arrivalDate][date]" => "2017-06-05",
            "ferry[segments][0][arrivalDate][time]" => "11:11 AM",
            "ferry[phone]" => "TEST_PHONE",
            "ferry[notes]" => "some notes",
        ];
    }

    protected function getNewFields(string $recordLocator)
    {
        return [
            "ferry[confirmationNumber]" => $recordLocator,
            "ferry[segments][0][ferryCompany]" => "NEW_COMPANY",
            "ferry[segments][0][route]" => "NEW_ROUTE",
            "ferry[segments][0][departurePort]" => "Sochi, Russia",
            "ferry[segments][0][departureDate][date]" => "2018-06-03",
            "ferry[segments][0][departureDate][time]" => "10:10 AM",
            "ferry[segments][0][arrivalPort]" => "Murmansk, Russia",
            "ferry[segments][0][arrivalDate][date]" => "2018-06-05",
            "ferry[segments][0][arrivalDate][time]" => "10:10 AM",
            "ferry[phone]" => "NEW_PHONE",
            "ferry[notes]" => "some other notes",
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
