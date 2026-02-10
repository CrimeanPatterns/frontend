<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\ItineraryController;

use AwardWallet\Common\Entity\Geotag;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Restaurant;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use AwardWallet\Tests\FunctionalSymfony\Traits\StaffUser;

/**
 * @group frontend-functional
 */
class CreateEventFormCest extends CreateItineraryFormCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use StaffUser;
    use LoggedIn;

    public const ADD_PATH = '/event/add';
    public const TABLE = 'Restaurant';
    public const ID_FIELD = 'RestaurantID';
    public const RECORD_LOCATOR = 'ConfNo';
    public const REPOSITORY = Restaurant::class;

    public function addToSelf(\TestSymfonyGuy $I)
    {
        /** @var Restaurant $event */
        $event = parent::addToSelf($I);
        $I->assertEquals('1', $event->getEventtype());
        $I->assertEquals('TEST_EVENT', $event->getName());
        $I->assertEquals('Statue of Liberty', $event->getAddress());
        $I->assertEquals(new \DateTime('2017-01-01 16:00'), $event->getStartdate());
        $I->assertEquals(new \DateTime('2017-01-02 11:00'), $event->getEnddate());
        $I->assertEquals('TEST_PHONE', $event->getPhone());
        $arrivalGeoTag = $I->grabService('doctrine')->getRepository(Geotag::class)->find(FindGeoTag("Statue of Liberty")['GeoTagID']);
        $I->assertSame($arrivalGeoTag, $event->getGeotagid());
    }

    public function checkErrors(\TestSymfonyGuy $I)
    {
        $I->stopFollowingRedirects();
        $I->amOnPage(self::ADD_PATH);

        $I->submitForm('form', [
            "event[confirmationNumber]" => $I->grabRandomString(101),
            "event[eventType]" => "100",
            "event[title]" => $I->grabRandomString(81),
            "event[startDate][date]" => "INVALID_DATE",
            "event[startDate][time]" => "INVALID_TIME",
            "event[endDate][date]" => "INVALID_DATE",
            "event[endDate][time]" => "INVALID_TIME",
            "event[address]" => $I->grabRandomString(161),
            "event[phone]" => $I->grabRandomString(81),
            "event[notes]" => $I->grabRandomString(4001),
        ]);
        $I->seeResponseCodeIs(200);

        $I->comment('Confirmation number validation error');
        $I->see('This value is too long. It should have 100 characters or less.');
        $I->comment('Event type validation error');
        $I->see('This value is not valid.', '.row-eventType');
        $I->comment('Title validation error');
        $I->see('This value is too long. It should have 80 characters or less.', '.row-title');
        $I->comment('Start date validation error');
        $I->see('Please, enter valid date and time.', '#row-startDate');
        $I->comment('End date validation error');
        $I->see('Please, enter valid date and time.', '#row-endDate');
        $I->comment('Address validation error');
        $I->see('This value is too long. It should have 160 characters or less.', '.row-address');
        $I->comment('Phone validation error');
        $I->see('This value is too long. It should have 80 characters or less.', '.row-phone');
        $I->comment('Notes validation error');
        $I->see('This value is too long. It should have 4000 characters or less.', '.row-notes');
    }

    protected function doSubmitForm(\TestSymfonyGuy $I, string $recordLocator)
    {
        $I->submitForm('form', [
            "event[confirmationNumber]" => $recordLocator,
            "event[eventType]" => "1",
            "event[title]" => "TEST_EVENT",
            "event[startDate][date]" => "2017-01-01",
            "event[startDate][time]" => "16:00",
            "event[endDate][date]" => "2017-01-02",
            "event[endDate][time]" => "11:00",
            "event[address]" => "Statue of Liberty",
            "event[phone]" => "TEST_PHONE",
            "event[notes]" => "some notes",
        ]);
    }

    protected function assertRedirect(\TestSymfonyGuy $I, Itinerary $itinerary)
    {
        $I->seeRedirectTo($this->router->generate('aw_timeline_show', ['segmentId' => "E." . $itinerary->getId()]));
    }
}
