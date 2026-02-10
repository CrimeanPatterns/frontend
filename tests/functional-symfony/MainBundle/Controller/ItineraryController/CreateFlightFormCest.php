<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\ItineraryController;

use AwardWallet\Common\Entity\Geotag;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Service\FlightStats\AirlineConverter;
use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\UpdateWorker;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use AwardWallet\Tests\FunctionalSymfony\Traits\StaffUser;
use Codeception\Example;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * @group frontend-functional
 */
class CreateFlightFormCest extends CreateItineraryFormCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use StaffUser;
    use LoggedIn;

    public const ADD_PATH = '/flight/add';
    public const TABLE = 'Trip';
    public const ID_FIELD = 'TripID';
    public const RECORD_LOCATOR = 'recordLocator';
    public const REPOSITORY = Trip::class;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);
        $I->mockService(AirlineConverter::class, $I->stubMakeEmpty(AirlineConverter::class, [
            'IataToFSCode' => 'AA',
            'FSCodeToIata' => 'AA',
            'FSCodeToName' => 'American Airlines',
        ]));
        // tripalerts subscriptions handled asynchronously, mock producer to handle in sync
        $I->mockService("old_sound_rabbit_mq.trip_alerts_updater_producer", $I->stubMakeEmpty(ProducerInterface::class, [
            'publish' => function ($msgBody, $routingKey = '', $additionalProperties = []) use ($I) {
                // patch message time to force immediate update
                $msg = json_decode($msgBody, true);
                $msg['time'] = time() - 60;
                $I->grabService(UpdateWorker::class)->execute(new AMQPMessage(json_encode($msg)));
            },
        ]));
    }

    public function addToSelf(\TestSymfonyGuy $I)
    {
        /** @var Trip $trip */
        $trip = parent::addToSelf($I);
        $I->assertEquals(TRIP_CATEGORY_AIR, $trip->getCategory());
        $I->assertCount(1, $trip->getSegments());
        $tripsegment = $trip->getSegments()[0];
        $I->assertEquals($trip->getId(), $tripsegment->getTripid()->getId());
        $I->assertEquals("ORD", $tripsegment->getDepcode());
        $I->assertEquals("Chicago O'Hare International Airport", $tripsegment->getDepname());
        $I->assertEquals(new \DateTime('+1 day 11:11'), $tripsegment->getDepdate());
        $I->assertEquals("STB", $tripsegment->getArrcode());
        $I->assertEquals("Miguel Urdaneta Fernandez Airport", $tripsegment->getArrname());
        $I->assertEquals(new \DateTime('+2 days 11:11'), $tripsegment->getArrdate());
        $I->assertEquals("American Airlines", $tripsegment->getAirlineName());
        $I->assertEquals("2331", $tripsegment->getFlightNumber());
        $departureGeoTag = $I->grabService('doctrine')->getRepository(Geotag::class)->find(FindGeoTag(
            "ORD",
            null,
            GEOTAG_TYPE_AIRPORT
        )['GeoTagID']);
        $I->assertSame($departureGeoTag, $tripsegment->getDepgeotagid());
        $arrivalGeoTag = $I->grabService('doctrine')->getRepository(Geotag::class)->find(FindGeoTag(
            "STB",
            null,
            GEOTAG_TYPE_AIRPORT
        )['GeoTagID']);
        $I->assertSame($arrivalGeoTag, $tripsegment->getArrgeotagid());
        $I->assertEqualsWithDelta(new \DateTime(), $tripsegment->getChangeDate(), 10);
        $I->assertFalse($tripsegment->getHidden());
        $I->assertEqualsWithDelta(new \DateTime('+1 day 11:11'), $tripsegment->getScheduledDepDate(), 10);
        $I->assertEqualsWithDelta(new \DateTime('+2 days 11:11'), $tripsegment->getScheduledArrDate(), 10);
    }

    /**
     * @dataProvider mobileDeviceProvider
     */
    public function testTripAlertsWithMobileDevice(\TestSymfonyGuy $I, Example $example)
    {
        $I->haveInDatabase('MobileDevice', [
            'DeviceKey' => $I->grabRandomString(10),
            'DeviceType' => $example['type'],
            'Lang' => 'en',
            'UserID' => $this->user->getUserid(),
            'CreationDate' => date('Y-m-d H:i:s'),
            'UpdateDate' => date('Y-m-d H:i:s'),
        ]);
        $I->amOnPage(static::ADD_PATH);
        $recordLocator = $I->grabRandomString(3);
        $I->submitForm('form', [
            "flight[confirmationNumber]" => $recordLocator,
            "flight[segments][0][airlineName]" => "American Airlines",
            "flight[segments][0][flightNumber]" => "2331",
            "flight[segments][0][departureAirport]" => "ORD",
            "flight[segments][0][departureDate][date]" => (new \DateTime('+1 day 11:11'))->format('Y-m-d'),
            "flight[segments][0][departureDate][time]" => "11:11",
            "flight[segments][0][arrivalAirport]" => "STB",
            "flight[segments][0][arrivalDate][date]" => (new \DateTime('+2 days 11:11'))->format('Y-m-d'),
            "flight[segments][0][arrivalDate][time]" => "11:11",
            "flight[notes]" => "some notes",
        ]);
        $I->seeInDatabase('Trip', ['recordLocator' => $recordLocator]);
        $tripId = $I->grabFromDatabase('Trip', 'TripID', ['recordLocator' => $recordLocator]);
        $I->seeInDatabase('TripSegment', [
            'TripID' => $tripId,
        ]);

        if ($example['shouldBeMonitored']) {
            $I->assertNotEmpty($I->grabFromDatabase("TripSegment", "TripAlertsUpdateDate", ["TripID" => $tripId]));
        } else {
            $I->assertEmpty($I->grabFromDatabase("TripSegment", "TripAlertsUpdateDate", ["TripID" => $tripId]));
        }
    }

    public function testTripAlertsWithUnrecognizedAirline(\TestSymfonyGuy $I)
    {
        $I->amOnPage(static::ADD_PATH);
        $recordLocator = $I->grabRandomString(3);
        $I->submitForm('form', [
            "flight[confirmationNumber]" => $recordLocator,
            "flight[segments][0][airlineName]" => "NOT A REAL AIRLINE",
            "flight[segments][0][flightNumber]" => "2331",
            "flight[segments][0][departureAirport]" => "ORD",
            "flight[segments][0][departureDate][date]" => (new \DateTime('+1 day 11:11'))->format('Y-m-d'),
            "flight[segments][0][departureDate][time]" => "11:11",
            "flight[segments][0][arrivalAirport]" => "STB",
            "flight[segments][0][arrivalDate][date]" => (new \DateTime('+2 days 11:11'))->format('Y-m-d'),
            "flight[segments][0][arrivalDate][time]" => "11:11",
            "flight[notes]" => "some notes",
        ]);
        $I->seeInDatabase('Trip', ['recordLocator' => $recordLocator]);
        $tripId = $I->grabFromDatabase('Trip', 'TripID', ['recordLocator' => $recordLocator]);
        $I->seeInDatabase('TripSegment', ['TripID' => $tripId, 'TripAlertsUpdateDate' => null]);
    }

    public function mobileDeviceProvider()
    {
        return [
            ['type' => MobileDevice::TYPE_ANDROID, 'shouldBeMonitored' => 1],
            ['type' => MobileDevice::TYPE_IOS, 'shouldBeMonitored' => 1],
            ['type' => MobileDevice::TYPE_SAFARI, 'shouldBeMonitored' => 0],
            ['type' => MobileDevice::TYPE_CHROME, 'shouldBeMonitored' => 0],
            ['type' => MobileDevice::TYPE_FIREFOX, 'shouldBeMonitored' => 0],
            ['type' => MobileDevice::TYPE_PUSHY_ANDROID, 'shouldBeMonitored' => 1],
        ];
    }

    public function checkErrors(\TestSymfonyGuy $I)
    {
        $I->stopFollowingRedirects();
        $I->amOnPage(self::ADD_PATH);

        $I->submitForm('form', [
            "flight[owner]" => "",
            "flight[confirmationNumber]" => $I->grabRandomString(101),
            "flight[notes]" => $I->grabRandomString(4001),
        ]);
        $I->seeResponseCodeIs(200);

        $I->comment('Confirmation number validation error');
        $I->see('This value is too long. It should have 100 characters or less.');
        $I->comment('Notes validation error');
        $I->see('This value is too long. It should have 4000 characters or less.');

        $I->submitForm('form', [
            "flight[owner]" => "",
            "flight[confirmationNumber]" => "",
            "flight[segments][0][airlineName]" => $I->grabRandomString(251),
            "flight[segments][0][flightNumber]" => $I->grabRandomString(21),
            "flight[segments][0][departureAirport]" => "ORDO",
            "flight[segments][0][departureDate][date]" => "rubbish",
            "flight[segments][0][departureDate][time]" => "rubbish",
            "flight[segments][0][arrivalAirport]" => "ORDO",
            "flight[segments][0][arrivalDate][date]" => "rubbish",
            "flight[segments][0][arrivalDate][time]" => "rubbish",
            "flight[notes]" => "some notes",
        ]);
        $I->seeResponseCodeIs(200);

        $I->comment('Airline name validation error');
        $I->see('This value is too long. It should have 250 characters or less.');
        $I->comment('Flight number length validation error');
        $I->see('This value is too long. It should have 20 characters or less.');
        $I->comment('Flight number digits validation error');
        $I->see('This field should only contain digits');
        $I->comment('Departure airport validation error');
        $I->see('Invalid airport code', 'div.row-departureAirport');
        $I->comment('Arrival airport validation error');
        $I->see('Invalid airport code', 'div.row-arrivalAirport');
        $I->see('Please, enter valid date and time.');

        $I->submitForm('form', [
            "flight[owner]" => "",
            "flight[confirmationNumber]" => "",
            "flight[segments][0][airlineName]" => $I->grabRandomString(251),
            "flight[segments][0][flightNumber]" => $I->grabRandomString(21),
            "flight[segments][0][departureAirport]" => "ORD",
            "flight[segments][0][departureDate][date]" => "2015-01-01",
            "flight[segments][0][departureDate][time]" => "00:00",
            "flight[segments][0][arrivalAirport]" => "ORD",
            "flight[segments][0][arrivalDate][date]" => "2014-12-31",
            "flight[segments][0][arrivalDate][time]" => "00:00",
            "flight[notes]" => "some notes",
        ]);
        $I->seeResponseCodeIs(200);

        $I->see('Departure and arrival airports can not be the same');
    }

    protected function doSubmitForm(\TestSymfonyGuy $I, string $recordLocator)
    {
        $I->submitForm('form', [
            "flight[confirmationNumber]" => $recordLocator,
            "flight[segments][0][airlineName]" => "American Airlines",
            "flight[segments][0][flightNumber]" => "2331",
            "flight[segments][0][departureAirport]" => "ORD",
            "flight[segments][0][departureDate][date]" => (new \DateTime('+1 day 11:11'))->format('Y-m-d'),
            "flight[segments][0][departureDate][time]" => "11:11",
            "flight[segments][0][arrivalAirport]" => "STB",
            "flight[segments][0][arrivalDate][date]" => (new \DateTime('+2 days 11:11'))->format('Y-m-d'),
            "flight[segments][0][arrivalDate][time]" => "11:11",
            "flight[notes]" => "some notes",
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
