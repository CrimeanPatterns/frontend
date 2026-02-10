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
class CreateCruiseFormBusinessCest extends CreateItineraryBusinessCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use StaffUser;
    use LoggedIn;

    public const ADD_PATH = '/cruise/add';
    public const FORM_NAME = 'cruise';
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
            "cruise[owner]" => $owner,
            "cruise[confirmationNumber]" => $recordLocator,
            "cruise[segments][0][cruiseShip]" => "TEST_SHIP",
            "cruise[segments][0][route]" => "SOME_ROUTE",
            "cruise[segments][0][departurePort]" => "Moscow, Russia",
            "cruise[segments][0][departureDate][date]" => "2017-06-03",
            "cruise[segments][0][departureDate][time]" => "11:11",
            "cruise[segments][0][arrivalPort]" => "Perm, Russia",
            "cruise[segments][0][arrivalDate][date]" => "2017-06-05",
            "cruise[segments][0][arrivalDate][time]" => "11:11",
            "cruise[phone]" => "TEST_PHONE",
            "cruise[notes]" => "some notes",
        ];
    }
}
