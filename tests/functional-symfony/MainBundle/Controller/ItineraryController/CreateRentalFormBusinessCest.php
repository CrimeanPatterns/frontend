<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\ItineraryController;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Rental;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use AwardWallet\Tests\FunctionalSymfony\Traits\StaffUser;

/**
 * @group frontend-functional
 */
class CreateRentalFormBusinessCest extends CreateItineraryBusinessCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use StaffUser;
    use LoggedIn;

    public const ADD_PATH = '/rental/add';
    public const FORM_NAME = 'rental';
    public const TABLE = 'Rental';
    public const ID_FIELD = 'RentalID';
    public const RECORD_LOCATOR = 'Number';
    public const REPOSITORY = Rental::class;

    protected function assertRedirect(\TestSymfonyGuy $I, Itinerary $itinerary)
    {
        $I->seeRedirectTo($this->router->generate('aw_timeline_show', ['segmentId' => "PU." . $itinerary->getId()]));
    }

    protected function getFields(string $recordLocator, string $owner)
    {
        return [
            'rental[owner]' => $owner,
            "rental[confirmationNumber]" => $recordLocator,
            "rental[rentalCompany]" => "TEST_COMPANY",
            "rental[pickUpAddress]" => "TEST_ADDRESS",
            "rental[pickUpDate][date]" => "2017-01-01",
            "rental[pickUpDate][time]" => "16:00",
            "rental[dropOffAddress]" => "TEST_ADDRESS2",
            "rental[dropOffDate][date]" => "2017-01-02",
            "rental[dropOffDate][time]" => "11:00",
            "rental[phone]" => "TEST_PHONE",
            "rental[notes]" => "some notes",
        ];
    }
}
