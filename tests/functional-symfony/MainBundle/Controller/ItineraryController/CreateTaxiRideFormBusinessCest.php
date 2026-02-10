<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\ItineraryController;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Rental;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use AwardWallet\Tests\FunctionalSymfony\Traits\StaffUser;

/**
 * @group frontend-functional
 */
class CreateTaxiRideFormBusinessCest extends CreateItineraryBusinessCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use StaffUser;
    use LoggedIn;

    public const ADD_PATH = '/taxi-ride/add';
    public const FORM_NAME = 'taxi';
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
            'taxi[owner]' => $owner,
            "taxi[confirmationNumber]" => $recordLocator,
            "taxi[taxiCompany]" => "TEST_COMPANY",
            "taxi[pickUpAddress]" => "TEST_ADDRESS",
            "taxi[pickUpDate][date]" => "2017-01-01",
            "taxi[pickUpDate][time]" => "16:00",
            "taxi[dropOffAddress]" => "TEST_ADDRESS2",
            "taxi[dropOffDate][date]" => "2017-01-02",
            "taxi[dropOffDate][time]" => "11:00",
            "taxi[phone]" => "TEST_PHONE",
            "taxi[notes]" => "some notes",
        ];
    }
}
