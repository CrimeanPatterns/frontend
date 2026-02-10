<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\ItineraryController;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Restaurant;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use AwardWallet\Tests\FunctionalSymfony\Traits\StaffUser;

/**
 * @group frontend-functional
 */
class CreateEventFormBusinessCest extends CreateItineraryBusinessCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use StaffUser;
    use LoggedIn;

    public const ADD_PATH = '/event/add';
    public const FORM_NAME = 'event';
    public const TABLE = 'Restaurant';
    public const ID_FIELD = 'RestaurantID';
    public const RECORD_LOCATOR = 'ConfNo';
    public const REPOSITORY = Restaurant::class;

    protected function assertRedirect(\TestSymfonyGuy $I, Itinerary $itinerary)
    {
        $I->seeRedirectTo($this->router->generate('aw_timeline_show', ['segmentId' => "E." . $itinerary->getId()]));
    }

    protected function getFields(string $recordLocator, string $owner)
    {
        return [
            "event[owner]" => $owner,
            "event[confirmationNumber]" => $recordLocator,
            "event[eventType]" => "1",
            "event[title]" => "TEST_EVENT",
            "event[startDate][date]" => "2017-01-01",
            "event[startDate][time]" => "16:00",
            "event[endDate][date]" => "2017-01-02",
            "event[endDate][time]" => "11:00",
            "event[address]" => "Statue of Liberty",
            "event[phone]" => "TEST_PHONE",
            "event[notes]" => "some notes",
        ];
    }
}
