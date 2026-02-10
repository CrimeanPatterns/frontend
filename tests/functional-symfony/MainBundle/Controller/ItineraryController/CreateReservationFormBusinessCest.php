<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\ItineraryController;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use AwardWallet\Tests\FunctionalSymfony\Traits\StaffUser;

/**
 * @group frontend-functional
 */
class CreateReservationFormBusinessCest extends CreateItineraryBusinessCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use StaffUser;
    use LoggedIn;

    public const ADD_PATH = '/reservation/add';
    public const FORM_NAME = 'reservation';
    public const TABLE = 'Reservation';
    public const ID_FIELD = 'ReservationID';
    public const RECORD_LOCATOR = 'confirmationNumber';
    public const REPOSITORY = Reservation::class;

    protected function assertRedirect(\TestSymfonyGuy $I, Itinerary $itinerary)
    {
        $I->seeRedirectTo($this->router->generate('aw_timeline_show', ['segmentId' => "CI." . $itinerary->getId()]));
    }

    protected function getFields(string $recordLocator, string $owner)
    {
        return [
            "reservation[owner]" => $owner,
            "reservation[confirmationNumber]" => $recordLocator,
            "reservation[hotelName]" => "HOTEL_NAME",
            "reservation[address]" => "ADDRESS",
            "reservation[checkInDate][date]" => "2017-01-01",
            "reservation[checkInDate][time]" => "4:00 AM",
            "reservation[checkOutDate][date]" => "2017-01-02",
            "reservation[checkOutDate][time]" => "12:00 PM",
            "reservation[phone]" => "PHONE",
            "reservation[notes]" => "some notes",
        ];
    }
}
