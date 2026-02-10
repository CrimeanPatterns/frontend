<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\ItineraryController;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use AwardWallet\Tests\FunctionalSymfony\Traits\StaffUser;

/**
 * @group frontend-functional
 */
class EditReservationFormCest extends EditItineraryFormCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use StaffUser;
    use LoggedIn;

    public const EDIT_PATH = '/reservation/{id}/edit';
    public const TABLE = 'Reservation';
    public const ID_FIELD = 'ReservationID';
    public const RECORD_LOCATOR = 'confirmationNumber';
    public const REPOSITORY = Reservation::class;

    protected function createItineraryRow(\TestSymfonyGuy $I)
    {
        return $I->haveInDatabase(self::TABLE, [
            self::RECORD_LOCATOR => $this->testRecordLocator,
            "hotelName" => "HOTEL_NAME",
            "checkInDate" => "2017-01-01 04:00",
            "checkOutDate" => "2017-01-02 12:00",
            "Address" => "ADDRESS",
            "Phone" => "PHONE",
            "Hidden" => false,
            "Parsed" => false,
            'UserID' => $this->user->getUserid(),
            "Notes" => "some notes",
            "Moved" => false,
            "UpdateDate" => date('Y-m-d H:i:s'),
            "CreateDate" => date('Y-m-d H:i:s'),
            "Cancelled" => false,
            "Modified" => true,
            'Rooms' => "a:0:{}",
        ]);
    }

    protected function getFields()
    {
        return [
            "reservation[confirmationNumber]" => $this->itinerary->getConfirmationNumber(),
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

    protected function getNewFields(string $recordLocator)
    {
        return [
            "reservation[confirmationNumber]" => $recordLocator,
            "reservation[hotelName]" => "NEW_HOTEL_NAME",
            "reservation[address]" => "NEW_ADDRESS",
            "reservation[checkInDate][date]" => "2017-02-01",
            "reservation[checkInDate][time]" => "5:00 AM",
            "reservation[checkOutDate][date]" => "2017-02-02",
            "reservation[checkOutDate][time]" => "2:00 PM",
            "reservation[phone]" => "NEW_PHONE",
            "reservation[notes]" => "some other notes",
        ];
    }

    protected function assertRedirect(\TestSymfonyGuy $I, Itinerary $itinerary)
    {
        $I->seeRedirectTo($this->router->generate('aw_timeline_show', ['segmentId' => "CI." . $itinerary->getId()]));
    }
}
