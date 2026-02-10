<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\ItineraryController;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Restaurant;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use AwardWallet\Tests\FunctionalSymfony\Traits\StaffUser;

/**
 * @group frontend-functional
 */
class EditEventFormCest extends EditItineraryFormCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use StaffUser;
    use LoggedIn;

    public const EDIT_PATH = '/event/{id}/edit';
    public const TABLE = 'Restaurant';
    public const ID_FIELD = 'RestaurantID';
    public const RECORD_LOCATOR = 'ConfNo';
    public const REPOSITORY = Restaurant::class;

    protected function createItineraryRow(\TestSymfonyGuy $I)
    {
        return $I->haveInDatabase(self::TABLE, [
            self::RECORD_LOCATOR => $this->testRecordLocator,
            "Name" => "TEST_EVENT",
            "EventType" => "1",
            "StartDate" => "2017-01-01 16:00",
            "EndDate" => "2017-01-02 11:00",
            "Address" => "Statue of Liberty",
            "Phone" => "TEST_PHONE",
            "Hidden" => false,
            "Parsed" => false,
            'UserID' => $this->user->getUserid(),
            "Notes" => "some notes",
            "Moved" => false,
            "UpdateDate" => date('Y-m-d H:i:s'),
            "CreateDate" => date('Y-m-d H:i:s'),
            "Cancelled" => false,
            "Modified" => true,
        ]);
    }

    protected function getFields()
    {
        return [
            "event[confirmationNumber]" => $this->itinerary->getConfirmationNumber(),
            "event[eventType]" => "1",
            "event[title]" => "TEST_EVENT",
            "event[startDate][date]" => "2017-01-01",
            "event[startDate][time]" => "4:00 PM",
            "event[endDate][date]" => "2017-01-02",
            "event[endDate][time]" => "11:00 AM",
            "event[address]" => "Statue of Liberty",
            "event[phone]" => "TEST_PHONE",
            "event[notes]" => "some notes",
        ];
    }

    protected function getNewFields(string $recordLocator)
    {
        return [
            "event[confirmationNumber]" => $recordLocator,
            "event[eventType]" => "2",
            "event[title]" => "NEW_EVENT",
            "event[startDate][date]" => "2018-01-01",
            "event[startDate][time]" => "5:00 PM",
            "event[endDate][date]" => "2018-01-02",
            "event[endDate][time]" => "12:00 AM",
            "event[address]" => "new address",
            "event[phone]" => "NEW_PHONE",
            "event[notes]" => "some other notes",
        ];
    }

    protected function assertRedirect(\TestSymfonyGuy $I, Itinerary $itinerary)
    {
        $I->seeRedirectTo($this->router->generate('aw_timeline_show', ['segmentId' => "E." . $itinerary->getId()]));
    }
}
