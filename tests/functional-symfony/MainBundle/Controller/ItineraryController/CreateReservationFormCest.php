<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\ItineraryController;

use AwardWallet\Common\Entity\Geotag;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use AwardWallet\Tests\FunctionalSymfony\Traits\StaffUser;

/**
 * @group frontend-functional
 */
class CreateReservationFormCest extends CreateItineraryFormCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use StaffUser;
    use LoggedIn;

    public const ADD_PATH = '/reservation/add';
    public const TABLE = 'Reservation';
    public const ID_FIELD = 'ReservationID';
    public const RECORD_LOCATOR = 'ConfirmationNumber';
    public const REPOSITORY = Reservation::class;

    public function addToSelf(\TestSymfonyGuy $I)
    {
        /** @var Reservation $reservation */
        $reservation = parent::addToSelf($I);
        $I->assertEquals('TEST_HOTEL', $reservation->getHotelname());
        $I->assertEquals('Empire State Building', $reservation->getAddress());
        $I->assertEquals(new \DateTime('2017-01-01 16:00'), $reservation->getCheckindate());
        $I->assertEquals(new \DateTime('2017-01-02 11:00'), $reservation->getCheckoutdate());
        $I->assertEquals('TEST_PHONE', $reservation->getPhone());
        $geoTag = $I->grabService('doctrine')->getRepository(Geotag::class)->find(FindGeoTag('Empire State Building')['GeoTagID']);
        $I->assertSame($geoTag, $reservation->getGeotagid());
    }

    public function checkErrors(\TestSymfonyGuy $I)
    {
        $I->stopFollowingRedirects();
        $I->amOnPage(self::ADD_PATH);

        $I->submitForm('form', [
            "reservation[confirmationNumber]" => $I->grabRandomString(101),
            "reservation[hotelName]" => $I->grabRandomString(81),
            "reservation[address]" => $I->grabRandomString(251),
            "reservation[checkInDate][date]" => 'INVALID_DATE',
            "reservation[checkInDate][time]" => 'INVALID_TIME',
            "reservation[checkOutDate][date]" => 'INVALID_DATE',
            "reservation[checkOutDate][time]" => 'INVALID_TIME',
            "reservation[phone]" => $I->grabRandomString(81),
            "reservation[notes]" => $I->grabRandomString(4001),
        ]);
        $I->seeResponseCodeIs(200);

        $I->comment('Confirmation number validation error');
        $I->see('This value is too long. It should have 100 characters or less.');
        $I->comment('Hotel name validation error');
        $I->see('This value is too long. It should have 80 characters or less.', '#row-hotelName');
        $I->comment('Address validation error');
        $I->see('This value is too long. It should have 250 characters or less.');
        $I->comment('Date validation error');
        $I->see('Please, enter valid date and time.');
        $I->comment('Phone validation error');
        $I->see('This value is too long. It should have 80 characters or less.', '.row-phone');
        $I->comment('Notes validation error');
        $I->see('This value is too long. It should have 4000 characters or less.');

        $I->submitForm('form', [
            "reservation[confirmationNumber]" => $I->grabRandomString(100),
            "reservation[hotelName]" => $I->grabRandomString(80),
            "reservation[address]" => $I->grabRandomString(250),
            "reservation[checkInDate][date]" => '2017-01-02',
            "reservation[checkInDate][time]" => '04:00',
            "reservation[checkOutDate][date]" => '2017-01-01',
            "reservation[checkOutDate][time]" => '12:00',
            "reservation[phone]" => $I->grabRandomString(80),
            "reservation[notes]" => $I->grabRandomString(4000),
        ]);
        $I->seeResponseCodeIs(200);
        $I->see('Check-out date cannot precede the check-in date.');
    }

    protected function doSubmitForm(\TestSymfonyGuy $I, string $recordLocator)
    {
        $I->submitForm('form', [
            "reservation[confirmationNumber]" => $recordLocator,
            "reservation[hotelName]" => "TEST_HOTEL",
            "reservation[address]" => "Empire State Building",
            "reservation[checkInDate][date]" => "2017-01-01",
            "reservation[checkInDate][time]" => "16:00",
            "reservation[checkOutDate][date]" => "2017-01-02",
            "reservation[checkOutDate][time]" => "11:00",
            "reservation[phone]" => "TEST_PHONE",
            "reservation[notes]" => "some notes",
        ]);
    }

    protected function assertRedirect(\TestSymfonyGuy $I, Itinerary $itinerary, ?Useragent $userAgent = null)
    {
        $I->seeRedirectTo($this->router->generate('aw_timeline_show', ['segmentId' => "CI." . $itinerary->getId()]));
    }
}
