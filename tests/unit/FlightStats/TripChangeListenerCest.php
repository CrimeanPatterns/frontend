<?php

namespace AwardWallet\Tests\Unit\FlightStats;

use Codeception\Util\Stub;
use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManagerInterface;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;

/**
 * @group frontend-unit
 */
class TripChangeListenerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function testTripAddedAndNotUpdated(\CodeGuy $I)
    {
        $userId = $I->createAwUser(null, null, [], true);
        $mock = $I->stubMakeEmpty(Producer::class, [
            // one from TripChangeListener and two from TripsegmentListener
            'publish' => Stub::exactly(4, function ($msgBody, $routingKey = '', $additionalProperties = []) use ($I, $userId) {
                $I->assertSame(1, json_decode($msgBody, true)["version"]);
                $I->assertSame($userId, json_decode($msgBody, true)["userId"]);
            }),
        ], $this);
        $I->mockService("old_sound_rabbit_mq.trip_alerts_updater_producer", $mock);
        $itineraryUpdateEventDispatchedCount = 0;
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $I->grabService('event_dispatcher');
        $dispatcher->addListener('aw.itinerary.update', function () use (&$itineraryUpdateEventDispatchedCount) {
            $itineraryUpdateEventDispatchedCount++;
        });
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $I->grabService('doctrine.orm.entity_manager');
        /** @var EventManager $eventManager */
        $eventManager = $entityManager->getEventManager();
        $listener = new class() {
            private $invoked = false;

            public function postPersist(): void
            {
                $this->invoked = true;
            }

            public function wasInvoked(): bool
            {
                return $this->invoked;
            }
        };
        $eventManager->addEventListener(['postPersist'], $listener);

        $accountId = $I->createAwAccount($userId, "testprovider", "future.trip");
        $I->checkAccount($accountId);
        $I->checkAccount($accountId);

        assertSame(1, $itineraryUpdateEventDispatchedCount);
        assertTrue($listener->wasInvoked());
        $I->verifyMocks();
    }

    public function testTripAddedAndUpdated(\CodeGuy $I)
    {
        $userId = $I->createAwUser(null, null, [], true);
        $mock = $I->stubMakeEmpty(Producer::class, [
            // two from TripChangeListener and two from TripsegmentListener
            'publish' => Stub::exactly(5, function ($msgBody, $routingKey = '', $additionalProperties = []) use ($I, $userId) {
                $I->assertSame($userId, json_decode($msgBody, true)["userId"]);
            }),
        ]);
        $I->mockService("old_sound_rabbit_mq.trip_alerts_updater_producer", $mock);
        $itineraryUpdateEventDispatchedCount = 0;
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $I->grabService('event_dispatcher');
        $dispatcher->addListener('aw.itinerary.update', function () use (&$itineraryUpdateEventDispatchedCount) {
            $itineraryUpdateEventDispatchedCount++;
        });
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $I->grabService('doctrine.orm.entity_manager');
        /** @var EventManager $eventManager */
        $eventManager = $entityManager->getEventManager();
        $listener = new class() {
            private $invoked = false;

            public function postPersist(): void
            {
                $this->invoked = true;
            }

            public function wasInvoked(): bool
            {
                return $this->invoked;
            }
        };
        $eventManager->addEventListener(['postPersist'], $listener);

        $accountId = $I->createAwAccount($userId, "testprovider", "future.trip.random.seats");
        $I->checkAccount($accountId);
        $I->checkAccount($accountId);

        assertSame(2, $itineraryUpdateEventDispatchedCount);
        assertTrue($listener->wasInvoked());
        $I->verifyMocks();
    }

    public function testRental(\CodeGuy $I)
    {
        $userId = $I->createAwUser(null, null, [], true);
        $mock = $I->stubMakeEmpty(Producer::class, [
            'publish' => Stub::never(),
        ]);
        $I->mockService("old_sound_rabbit_mq.trip_alerts_updater_producer", $mock);
        $accountId = $I->createAwAccount($userId, "testprovider", "future.rental");
        $I->checkAccount($accountId);
        $I->verifyMocks();
    }
}
