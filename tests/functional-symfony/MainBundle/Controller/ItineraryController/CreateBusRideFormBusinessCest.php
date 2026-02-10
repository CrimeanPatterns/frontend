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
class CreateBusRideFormBusinessCest extends CreateItineraryBusinessCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use StaffUser;
    use LoggedIn;

    public const ADD_PATH = '/bus-ride/add';
    public const FORM_NAME = 'bus';
    public const TABLE = 'Trip';
    public const ID_FIELD = 'TripID';
    public const RECORD_LOCATOR = 'recordLocator';
    public const REPOSITORY = Trip::class;

    /**
     * @param Itinerary|Trip $itinerary
     */
    protected function assertRedirect(\TestSymfonyGuy $I, Itinerary $itinerary)
    {
        $I->seeRedirectTo($this->router->generate('aw_timeline_show_trip', ['tripId' => $itinerary->getId()]));
    }

    protected function getFields(string $recordLocator, string $owner)
    {
        return [
            "bus[owner]" => $owner,
            "bus[confirmationNumber]" => $recordLocator,
            "bus[segments][0][carrier]" => "TEST_CARRIER",
            "bus[segments][0][route]" => "SOME_ROUTE",
            "bus[segments][0][departureStation]" => "Moscow",
            "bus[segments][0][departureDate][date]" => "2017-06-03",
            "bus[segments][0][departureDate][time]" => "11:11",
            "bus[segments][0][arrivalStation]" => "Perm, Russia",
            "bus[segments][0][arrivalDate][date]" => "2017-06-05",
            "bus[segments][0][arrivalDate][time]" => "11:11",
            "bus[phone]" => "TEST_PHONE",
            "bus[notes]" => "some notes",
        ];
    }
}
