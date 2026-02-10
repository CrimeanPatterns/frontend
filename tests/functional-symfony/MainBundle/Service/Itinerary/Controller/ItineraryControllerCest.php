<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Service\Itinerary\Controller;

use AwardWallet\Tests\FunctionalSymfony\BaseTraitCest;
use Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
use AwardWallet\Tests\Modules\DbBuilder\GeoTag;
use AwardWallet\Tests\Modules\DbBuilder\Trip;
use AwardWallet\Tests\Modules\DbBuilder\TripSegment;
use AwardWallet\Tests\Modules\DbBuilder\User;
use Symfony\Component\Routing\RouterInterface;

/**
 * @group frontend-functional
 */
class ItineraryControllerCest extends BaseTraitCest
{
    use AutoVerifyMocksTrait;

    private ?RouterInterface $router;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);

        $this->router = $I->grabService('router');
    }

    public function _after(\TestSymfonyGuy $I)
    {
        parent::_after($I);

        $this->router = null;
    }

    public function testNotificationResetOnManualFlightEdit(\TestSymfonyGuy $I)
    {
        $I->makeTrip($trip = new Trip(
            'TEST001',
            [
                ($ts = new TripSegment(
                    'DME',
                    'DME',
                    date_create('+1 day'),
                    'PEE',
                    'PEE',
                    date_create('+2 day'),
                    null,
                    [
                        'AirlineName' => 'Test Airline',
                        'FlightNumber' => '001',
                        'PreCheckinNotificationDate' => date('Y-m-d H:i:s'),
                        'CheckinNotificationDate' => date('Y-m-d H:i:s'),
                        'FlightDepartureNotificationDate' => date('Y-m-d H:i:s'),
                        'FlightBoardingNotificationDate' => date('Y-m-d H:i:s'),
                    ]
                ))
                    ->setDepGeoTag(new GeoTag())
                    ->setArrGeoTag(new GeoTag()),
            ],
            $user = new User(),
            ['Category' => TRIP_CATEGORY_AIR]
        ));
        $I->amOnRoute('itinerary_edit', [
            'itineraryId' => $trip->getId(),
            'type' => 'flight',
            '_switch_user' => $user->getFields()['Login'],
        ]);
        $I->seeResponseCodeIs(200);
        $I->fillField('flight[segments][0][departureDate][date]', date('Y-m-d', strtotime('+2 day')));
        $I->fillField('flight[segments][0][arrivalDate][date]', date('Y-m-d', strtotime('+3 day')));
        $I->click('Save');
        $I->seeInCurrentRoute('aw_timeline');
        $I->seeInDatabase('TripSegment', [
            'TripSegmentID' => $ts->getId(),
            'PreCheckinNotificationDate' => null,
            'CheckinNotificationDate' => null,
            'FlightDepartureNotificationDate' => null,
            'FlightBoardingNotificationDate' => null,
        ]);
    }

    public function testManualFlightEditWithoutResettingDates(\TestSymfonyGuy $I)
    {
        $now = date('Y-m-d H:i:s');
        $I->makeTrip($trip = new Trip(
            'TEST001',
            [
                ($ts = new TripSegment(
                    'DME',
                    'DME',
                    date_create('+2 day'),
                    'PEE',
                    'PEE',
                    date_create('+3 day'),
                    null,
                    [
                        'AirlineName' => 'Test Airline',
                        'FlightNumber' => '001',
                        'PreCheckinNotificationDate' => $now,
                        'CheckinNotificationDate' => $now,
                        'FlightDepartureNotificationDate' => $now,
                        'FlightBoardingNotificationDate' => $now,
                    ]
                ))
                    ->setDepGeoTag(new GeoTag())
                    ->setArrGeoTag(new GeoTag()),
            ],
            $user = new User(),
            ['Category' => TRIP_CATEGORY_AIR]
        ));
        $I->amOnRoute('itinerary_edit', [
            'itineraryId' => $trip->getId(),
            'type' => 'flight',
            '_switch_user' => $user->getFields()['Login'],
        ]);
        $I->seeResponseCodeIs(200);
        $I->fillField('flight[segments][0][departureDate][date]', date('Y-m-d', strtotime('+1 day')));
        $I->fillField('flight[segments][0][arrivalDate][date]', date('Y-m-d', strtotime('+2 day')));
        $I->click('Save');
        $I->seeInCurrentRoute('aw_timeline');
        $I->seeInDatabase('TripSegment', [
            'TripSegmentID' => $ts->getId(),
            'PreCheckinNotificationDate' => $now,
            'CheckinNotificationDate' => $now,
            'FlightDepartureNotificationDate' => $now,
            'FlightBoardingNotificationDate' => $now,
        ]);
    }

    public function testNotificationDatesNotResetForPastFlightDate(\TestSymfonyGuy $I)
    {
        $notificationDate = date('Y-m-d H:i:s', strtotime('-1 day'));
        $I->makeTrip($trip = new Trip(
            'TEST001',
            [
                ($ts = new TripSegment(
                    'DME',
                    'DME',
                    date_create('-2 day'),
                    'PEE',
                    'PEE',
                    date_create('-1 day'),
                    null,
                    [
                        'AirlineName' => 'Test Airline',
                        'FlightNumber' => '001',
                        'PreCheckinNotificationDate' => $notificationDate,
                        'CheckinNotificationDate' => $notificationDate,
                        'FlightDepartureNotificationDate' => $notificationDate,
                        'FlightBoardingNotificationDate' => $notificationDate,
                    ]
                ))
                    ->setDepGeoTag(new GeoTag())
                    ->setArrGeoTag(new GeoTag()),
            ],
            $user = new User(),
            ['Category' => TRIP_CATEGORY_AIR]
        ));
        $I->amOnRoute('itinerary_edit', [
            'itineraryId' => $trip->getId(),
            'type' => 'flight',
            '_switch_user' => $user->getFields()['Login'],
        ]);
        $I->seeResponseCodeIs(200);
        $I->fillField('flight[segments][0][departureDate][date]', date('Y-m-d', strtotime('-40 hour')));
        $I->fillField('flight[segments][0][arrivalDate][date]', date('Y-m-d', strtotime('-1 day')));
        $I->click('Save');
        $I->seeInCurrentRoute('aw_timeline');
        $I->seeInDatabase('TripSegment', [
            'TripSegmentID' => $ts->getId(),
            'PreCheckinNotificationDate' => $notificationDate,
            'CheckinNotificationDate' => $notificationDate,
            'FlightDepartureNotificationDate' => $notificationDate,
            'FlightBoardingNotificationDate' => $notificationDate,
        ]);
    }
}
