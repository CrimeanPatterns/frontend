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
class CreateFerryRideFormBusinessCest extends CreateItineraryBusinessCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use StaffUser;
    use LoggedIn;

    public const ADD_PATH = '/ferry-ride/add';
    public const FORM_NAME = 'ferry';
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
            "ferry[owner]" => $owner,
            "ferry[confirmationNumber]" => $recordLocator,
            "ferry[segments][0][ferryCompany]" => "TEST_COMPANY",
            "ferry[segments][0][route]" => "SOME_ROUTE",
            "ferry[segments][0][departurePort]" => "Moscow, Russia",
            "ferry[segments][0][departureDate][date]" => "2017-06-03",
            "ferry[segments][0][departureDate][time]" => "11:11",
            "ferry[segments][0][arrivalPort]" => "Perm, Russia",
            "ferry[segments][0][arrivalDate][date]" => "2017-06-05",
            "ferry[segments][0][arrivalDate][time]" => "11:11",
            "ferry[phone]" => "TEST_PHONE",
            "ferry[notes]" => "some notes",
        ];
    }
}
