<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\ItineraryController;

use AwardWallet\Common\Entity\Geotag;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Rental;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use AwardWallet\Tests\FunctionalSymfony\Traits\StaffUser;

/**
 * @group frontend-functional
 */
class CreateTaxiRideFormCest extends CreateItineraryFormCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use StaffUser;
    use LoggedIn;

    public const ADD_PATH = '/taxi-ride/add';
    public const TABLE = 'Rental';
    public const ID_FIELD = 'RentalID';
    public const RECORD_LOCATOR = 'Number';
    public const REPOSITORY = Rental::class;

    public function addToSelf(\TestSymfonyGuy $I)
    {
        /** @var Rental $rental */
        $rental = parent::addToSelf($I);
        $I->assertEquals('TEST_COMPANY', $rental->getRentalCompanyName());
        $I->assertEquals('Empire State Building', $rental->getPickuplocation());
        $I->assertEquals('Statue of Liberty', $rental->getDropofflocation());
        $I->assertEquals(new \DateTime('2017-01-01 16:00'), $rental->getPickupdatetime());
        $I->assertEquals(new \DateTime('2017-01-02 11:00'), $rental->getDropoffdatetime());
        $I->assertEquals('TEST_PHONE', $rental->getPhone());
        $departureGeoTag = $I->grabService('doctrine')->getRepository(Geotag::class)->find(FindGeoTag('Empire State Building')['GeoTagID']);
        $I->assertSame($departureGeoTag, $rental->getPickupgeotagid());
        $arrivalGeoTag = $I->grabService('doctrine')->getRepository(Geotag::class)->find(FindGeoTag("Statue of Liberty")['GeoTagID']);
        $I->assertSame($arrivalGeoTag, $rental->getDropoffgeotagid());
        $I->assertSame(Rental::TYPE_TAXI, $rental->getType());
    }

    public function checkErrors(\TestSymfonyGuy $I)
    {
        $I->stopFollowingRedirects();
        $I->amOnPage(self::ADD_PATH);

        $I->submitForm('form', [
            "taxi[confirmationNumber]" => $I->grabRandomString(101),
            "taxi[taxiCompany]" => $I->grabRandomString(81),
            "taxi[pickUpAddress]" => $I->grabRandomString(161),
            "taxi[pickUpDate][date]" => "INVALID_DATE",
            "taxi[pickUpDate][time]" => "INVALID_TIME",
            "taxi[dropOffAddress]" => $I->grabRandomString(161),
            "taxi[dropOffDate][date]" => "INVALID_DATE",
            "taxi[dropOffDate][time]" => "INVALID_TIME",
            "taxi[phone]" => $I->grabRandomString(21),
            "taxi[notes]" => $I->grabRandomString(4001),
        ]);
        $I->seeResponseCodeIs(200);

        $I->comment('Confirmation number validation error');
        $I->see('This value is too long. It should have 100 characters or less.');
        $I->comment('Company name validation error');
        $I->see('This value is too long. It should have 80 characters or less.', '#row-taxiCompany');
        $I->comment('Pick-Up address validation error');
        $I->see('This value is too long. It should have 160 characters or less.', '#row-pickUpAddress');
        $I->comment('Drop-Off address validation error');
        $I->see('This value is too long. It should have 160 characters or less.', '#row-dropOffAddress');
        $I->comment('PickUp date validation error');
        $I->see('Please, enter valid date and time.', '#row-pickUpDate');
        $I->comment('DropOff date validation error');
        $I->see('Please, enter valid date and time.', '#row-dropOffDate');
        $I->comment('Phone validation error');
        $I->see('This value is too long. It should have 20 characters or less.', '.row-phone');
        $I->comment('Notes validation error');
        $I->see('This value is too long. It should have 4000 characters or less.');
    }

    protected function doSubmitForm(\TestSymfonyGuy $I, string $recordLocator)
    {
        $I->submitForm('form', [
            "taxi[confirmationNumber]" => $recordLocator,
            "taxi[taxiCompany]" => "TEST_COMPANY",
            "taxi[pickUpAddress]" => "Empire State Building",
            "taxi[pickUpDate][date]" => "2017-01-01",
            "taxi[pickUpDate][time]" => "16:00",
            "taxi[dropOffAddress]" => "Statue of Liberty",
            "taxi[dropOffDate][date]" => "2017-01-02",
            "taxi[dropOffDate][time]" => "11:00",
            "taxi[phone]" => "TEST_PHONE",
            "taxi[notes]" => "some notes",
        ]);
    }

    protected function assertRedirect(\TestSymfonyGuy $I, Itinerary $itinerary)
    {
        $I->seeRedirectTo($this->router->generate('aw_timeline_show', ['segmentId' => "PU." . $itinerary->getId()]));
    }
}
