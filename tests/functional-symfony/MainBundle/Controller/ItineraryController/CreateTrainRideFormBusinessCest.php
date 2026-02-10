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
class CreateTrainRideFormBusinessCest extends CreateItineraryBusinessCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use StaffUser;
    use LoggedIn;

    public const ADD_PATH = '/train-ride/add';
    public const FORM_NAME = 'train';
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
            "train[owner]" => $owner,
            "train[confirmationNumber]" => $recordLocator,
            "train[segments][0][carrier]" => "TEST_CARRIER",
            "train[segments][0][route]" => "SOME_ROUTE",
            "train[segments][0][departureStationCode]" => "TES",
            "train[segments][0][departureStation]" => "Moscow, Russia",
            "train[segments][0][departureDate][date]" => "2017-06-03",
            "train[segments][0][departureDate][time]" => "11:11",
            "train[segments][0][arrivalStationCode]" => "EST",
            "train[segments][0][arrivalStation]" => "Perm, Russia",
            "train[segments][0][arrivalDate][date]" => "2017-06-05",
            "train[segments][0][arrivalDate][time]" => "11:11",
            "train[phone]" => "TEST_PHONE",
            "train[notes]" => "some notes",
        ];
    }
}
