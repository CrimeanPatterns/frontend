<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\ItineraryController;

use AwardWallet\Common\Entity\Geotag;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use AwardWallet\Tests\FunctionalSymfony\Traits\StaffUser;

/**
 * @group frontend-functional
 * @group moscow
 */
class CreateBusRideFormCest extends CreateItineraryFormCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use StaffUser;
    use LoggedIn;

    public const ADD_PATH = '/bus-ride/add';
    public const TABLE = 'Trip';
    public const ID_FIELD = 'TripID';
    public const RECORD_LOCATOR = 'recordLocator';
    public const REPOSITORY = Trip::class;

    public function addToSelf(\TestSymfonyGuy $I)
    {
        /** @var Trip $trip */
        $trip = parent::addToSelf($I);
        $I->assertEquals(TRIP::CATEGORY_BUS, $trip->getCategory());
        $I->assertCount(1, $trip->getSegments());
        $tripsegment = $trip->getSegments()[0];
        $I->assertEquals($trip->getId(), $tripsegment->getTripid()->getId());
        $I->assertEquals("Moscow, Russia", $tripsegment->getDepname());
        $I->assertEquals(new \DateTime("2017-06-03 11:11:00"), $tripsegment->getDepdate());
        $I->assertEquals("Perm, Russia", $tripsegment->getArrname());
        $I->assertEquals(new \DateTime("2017-06-05 11:11:00"), $tripsegment->getArrdate());
        $I->assertEquals("TEST_CARRIER", $tripsegment->getAirlineName());
        $I->assertEquals("SOME_ROUTE", $tripsegment->getFlightNumber());
        $departureGeoTag = $I->grabService('doctrine')->getRepository(Geotag::class)->find(FindGeoTag("Moscow, Russia")['GeoTagID']);
        $I->assertSame($departureGeoTag, $tripsegment->getDepgeotagid());
        $arrivalGeoTag = $I->grabService('doctrine')->getRepository(Geotag::class)->find(FindGeoTag("Perm, Russia")['GeoTagID']);
        $I->assertSame($arrivalGeoTag, $tripsegment->getArrgeotagid());
        $I->assertEqualsWithDelta(new \DateTime(), $tripsegment->getChangeDate(), 10);
        $I->assertFalse($tripsegment->getHidden());
        $I->assertEqualsWithDelta(new \DateTime("2017-06-03 11:11:00"), $tripsegment->getScheduledDepDate(), 10);
        $I->assertEqualsWithDelta(new \DateTime("2017-06-05 11:11:00"), $tripsegment->getScheduledArrDate(), 10);
        $I->assertEquals('TEST_PHONE', $trip->getPhone());
    }

    public function checkErrors(\TestSymfonyGuy $I)
    {
        $I->stopFollowingRedirects();
        $I->amOnPage(self::ADD_PATH);

        $I->submitForm('form', [
            "bus[owner]" => "",
            "bus[confirmationNumber]" => $I->grabRandomString(101),
            "bus[notes]" => $I->grabRandomString(4001),
        ]);
        $I->seeResponseCodeIs(200);

        $I->comment('Confirmation number validation error');
        $I->see('This value is too long. It should have 100 characters or less.');
        $I->comment('Notes validation error');
        $I->see('This value is too long. It should have 4000 characters or less.');

        $I->submitForm('form', [
            "bus[owner]" => "",
            "bus[confirmationNumber]" => "",
            "bus[segments][0][carrier]" => $I->grabRandomString(251),
            "bus[segments][0][route]" => $I->grabRandomString(21),
            "bus[segments][0][departureStation]" => $I->grabRandomString(251),
            "bus[segments][0][departureDate][date]" => "rubbish",
            "bus[segments][0][departureDate][time]" => "rubbish",
            "bus[segments][0][arrivalStation]" => $I->grabRandomString(251),
            "bus[segments][0][arrivalDate][date]" => "rubbish",
            "bus[segments][0][arrivalDate][time]" => "rubbish",
            "bus[notes]" => "some notes",
        ]);
        $I->seeResponseCodeIs(200);

        $I->comment('Carrier length validation error');
        $I->see('This value is too long. It should have 250 characters or less.');
        $I->comment('Route number length validation error');
        $I->see('This value is too long. It should have 20 characters or less.');
        $I->comment('Departure station address validation error');
        $I->see('This value is too long. It should have 250 characters or less.', 'div.row-departureStation');
        $I->comment('Arrival station address validation error');
        $I->see('This value is too long. It should have 250 characters or less.', 'div.row-arrivalStation');
        $I->see('Please, enter valid date and time.');
    }

    protected function doSubmitForm(\TestSymfonyGuy $I, string $recordLocator)
    {
        $I->submitForm('form', [
            "bus[confirmationNumber]" => $recordLocator,
            "bus[segments][0][carrier]" => "TEST_CARRIER",
            "bus[segments][0][route]" => "SOME_ROUTE",
            "bus[segments][0][departureStation]" => "Moscow, Russia",
            "bus[segments][0][departureDate][date]" => "2017-06-03",
            "bus[segments][0][departureDate][time]" => "11:11",
            "bus[segments][0][arrivalStation]" => "Perm, Russia",
            "bus[segments][0][arrivalDate][date]" => "2017-06-05",
            "bus[segments][0][arrivalDate][time]" => "11:11",
            "bus[phone]" => "TEST_PHONE",
            "bus[notes]" => "some notes",
        ]);
    }

    /**
     * @param Itinerary|Trip $itinerary
     */
    protected function assertRedirect(\TestSymfonyGuy $I, Itinerary $itinerary)
    {
        $I->seeRedirectTo($this->router->generate('aw_timeline_show_trip', ['tripId' => $itinerary->getId()]));
    }
}
