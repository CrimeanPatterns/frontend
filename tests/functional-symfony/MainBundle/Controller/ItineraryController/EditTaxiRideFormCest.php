<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\ItineraryController;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Rental;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use AwardWallet\Tests\FunctionalSymfony\Traits\StaffUser;

/**
 * @group frontend-functional
 */
class EditTaxiRideFormCest extends EditItineraryFormCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use StaffUser;
    use LoggedIn;

    public const EDIT_PATH = '/taxi-ride/{id}/edit';
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
            "taxi[confirmationNumber]" => $this->itinerary->getConfirmationNumber(),
            "taxi[taxiCompany]" => "TEST_COMPANY",
            "taxi[pickUpAddress]" => "TEST_ADDRESS",
            "taxi[pickUpDate][date]" => "2017-01-01",
            "taxi[pickUpDate][time]" => "4:00 PM",
            "taxi[dropOffAddress]" => "TEST_ADDRESS2",
            "taxi[dropOffDate][date]" => "2017-01-02",
            "taxi[dropOffDate][time]" => "11:00 AM",
            "taxi[phone]" => "TEST_PHONE",
            "taxi[notes]" => "some notes",
        ];
    }

    protected function getNewFields(string $recordLocator)
    {
        return [
            "taxi[confirmationNumber]" => $recordLocator,
            "taxi[taxiCompany]" => "NEW_TEST_COMPANY",
            "taxi[pickUpAddress]" => "NEW_TEST_ADDRESS",
            "taxi[pickUpDate][date]" => "2017-01-03",
            "taxi[pickUpDate][time]" => "5:00 PM",
            "taxi[dropOffAddress]" => "NEW_TEST_ADDRESS2",
            "taxi[dropOffDate][date]" => "2017-01-04",
            "taxi[dropOffDate][time]" => "10:00 AM",
            "taxi[phone]" => "NEW_TEST_PHONE",
            "taxi[notes]" => "some other notes",
        ];
    }

    protected function assertRedirect(\TestSymfonyGuy $I, Itinerary $itinerary)
    {
        $I->seeRedirectTo($this->router->generate('aw_timeline_show', ['segmentId' => "PU." . $itinerary->getId()]));
    }
}
