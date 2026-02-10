<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\ItineraryController;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Rental;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use AwardWallet\Tests\FunctionalSymfony\Traits\StaffUser;

/**
 * @group frontend-functional
 */
class EditRentalFormCest extends EditItineraryFormCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use StaffUser;
    use LoggedIn;

    public const EDIT_PATH = '/rental/{id}/edit';
    public const TABLE = 'Rental';
    public const ID_FIELD = 'RentalID';
    public const RECORD_LOCATOR = 'Number';
    public const REPOSITORY = Rental::class;

    protected function createItineraryRow(\TestSymfonyGuy $I)
    {
        return $I->haveInDatabase(self::TABLE, [
            self::RECORD_LOCATOR => $this->testRecordLocator,
            "RentalCompanyName" => "TEST_COMPANY",
            "PickupDatetime" => "2017-01-01 16:00",
            "DropoffDatetime" => "2017-01-02 11:00",
            "PickupLocation" => "TEST_ADDRESS",
            "DropoffLocation" => "TEST_ADDRESS2",
            "PickupPhone" => "TEST_PHONE",
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
            "rental[confirmationNumber]" => $this->itinerary->getConfirmationNumber(),
            "rental[rentalCompany]" => "TEST_COMPANY",
            "rental[pickUpAddress]" => "TEST_ADDRESS",
            "rental[pickUpDate][date]" => "2017-01-01",
            "rental[pickUpDate][time]" => "4:00 PM",
            "rental[dropOffAddress]" => "TEST_ADDRESS2",
            "rental[dropOffDate][date]" => "2017-01-02",
            "rental[dropOffDate][time]" => "11:00 AM",
            "rental[phone]" => "TEST_PHONE",
            "rental[notes]" => "some notes",
        ];
    }

    protected function getNewFields(string $recordLocator)
    {
        return [
            "rental[confirmationNumber]" => $recordLocator,
            "rental[rentalCompany]" => "NEW_TEST_COMPANY",
            "rental[pickUpAddress]" => "NEW_TEST_ADDRESS",
            "rental[pickUpDate][date]" => "2017-01-03",
            "rental[pickUpDate][time]" => "5:00 PM",
            "rental[dropOffAddress]" => "NEW_TEST_ADDRESS2",
            "rental[dropOffDate][date]" => "2017-01-04",
            "rental[dropOffDate][time]" => "10:00 AM",
            "rental[phone]" => "NEW_TEST_PHONE",
            "rental[notes]" => "some other notes",
        ];
    }

    protected function assertRedirect(\TestSymfonyGuy $I, Itinerary $itinerary)
    {
        $I->seeRedirectTo($this->router->generate('aw_timeline_show', ['segmentId' => "PU." . $itinerary->getId()]));
    }
}
