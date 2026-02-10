<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\ItineraryController;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use AwardWallet\Tests\FunctionalSymfony\Traits\StaffUser;

/**
 * @group frontend-functional
 */
class CreateFlightFormBusinessCest extends CreateItineraryBusinessCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use StaffUser;
    use LoggedIn;

    public const ADD_PATH = '/flight/add';
    public const FORM_NAME = 'flight';
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
            "flight[owner]" => $owner,
            "flight[confirmationNumber]" => $recordLocator,
            "flight[segments][0][airlineName]" => "American Airlines",
            "flight[segments][0][flightNumber]" => "2331",
            "flight[segments][0][departureAirport]" => "ORD",
            "flight[segments][0][departureDate][date]" => "2017-06-03",
            "flight[segments][0][departureDate][time]" => "11:11",
            "flight[segments][0][arrivalAirport]" => "STB",
            "flight[segments][0][arrivalDate][date]" => "2017-06-05",
            "flight[segments][0][arrivalDate][time]" => "11:11",
            "flight[notes]" => "some notes",
        ];
    }
}
