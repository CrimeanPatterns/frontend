<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Service\Timeline\Diff;

use AwardWallet\MainBundle\Event\ItineraryUpdateEvent;
use AwardWallet\MainBundle\Timeline\Diff\Tracker;
use Codeception\Example;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @group frontend-functional
 */
class TrackerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    /**
     * @dataProvider reservationDataProvider
     */
    public function testReservation(\TestSymfonyGuy $I, Example $example)
    {
        $userId = $I->createAwUser();
        $accountId = $I->createAwAccount($userId, "testprovider", "some");

        $checkinDate = strtotime($example['Date']);
        $checkoutDate = strtotime("+1 day", $checkinDate);

        $reservationId = $I->haveInDatabase("Reservation", [
            'ProviderID' => $I->grabFromDatabase("Provider", "ProviderID", ["Code" => "testprovider"]),
            'UserID' => $userId,
            'TravelAgencyConfirmationNumbers' => 'CONF1',
            'HotelName' => 'Hotel1',
            'CheckInDate' => date("Y-m-d 14:00", $checkinDate),
            'CheckOutDate' => date("Y-m-d 11:00", $checkoutDate),
            'AccountID' => $accountId,
            'CancellationPolicy' => $example['OldPolicy'],
        ]);

        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = $I->grabService("event_dispatcher");
        $eventWasSent = false;
        $eventDispatcher->addListener(ItineraryUpdateEvent::NAME, function (ItineraryUpdateEvent $event) use (&$eventWasSent) {
            $eventWasSent = true;
        });

        /** @var Tracker $tracker */
        $tracker = $I->grabService("aw.diff.tracker");
        $old = $tracker->getProperties($accountId);
        $I->updateInDatabase("Reservation", ["CancellationPolicy" => $example['NewPolicy']], ["ReservationID" => $reservationId]);
        $changeCount = $tracker->recordChanges($old, $accountId, $userId);

        $I->assertEquals($example['ExpectedChangeCount'], $changeCount);
        $I->assertEquals($example['ExpectEvent'], $eventWasSent);
    }

    public function reservationDataProvider()
    {
        return [
            // we track only first 250 bytes, that is 125 chars in utf
            ['OldPolicy' => str_repeat('я', 125) . 'OldPol', 'NewPolicy' => str_repeat('я', 125) . 'NewPol', 'ExpectedChangeCount' => 0, "Date" => "-7 day", "ExpectEvent" => false],
            ['OldPolicy' => null, 'NewPolicy' => null, 'ExpectedChangeCount' => 0, "Date" => "tomorrow", "ExpectEvent" => false],
            ['OldPolicy' => null, 'NewPolicy' => 'NewPol', 'ExpectedChangeCount' => 0, "Date" => "tomorrow", "ExpectEvent" => false],
            ['OldPolicy' => 'OldPol', 'NewPolicy' => null, 'ExpectedChangeCount' => 0, "Date" => "tomorrow", "ExpectEvent" => false],
            ['OldPolicy' => 'OldPol', 'NewPolicy' => 'NewPol', 'ExpectedChangeCount' => 1, "Date" => "tomorrow", "ExpectEvent" => true],
            ['OldPolicy' => 'OldPol', 'NewPolicy' => 'NewPol', 'ExpectedChangeCount' => 0, "Date" => "-7 day", "ExpectEvent" => false],
            // test long string
            ['OldPolicy' => str_repeat('z', 250) . 'OldPol', 'NewPolicy' => 'NewPol', 'ExpectedChangeCount' => 1, "Date" => "tomorrow", "ExpectEvent" => true],
            // we track only first 250 bytes
            ['OldPolicy' => str_repeat('z', 250) . 'OldPol', 'NewPolicy' => str_repeat('z', 250) . 'NewPol', 'ExpectedChangeCount' => 0, "Date" => "tomorrow", "ExpectEvent" => false],
        ];
    }
}
