<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\FlightStats\TripAlerts;

use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\SubscriptionManager;
use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\UpdateWorker;
use Codeception\Util\Stub;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * @group frontend-unit
 */
class UpdateWorkerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function testNoTime(\TestSymfonyGuy $I)
    {
        $userId = $I->createAwUser();
        $I->createAwMobileDevice($userId);
        $subscriptionManager = $I->stubMakeEmpty(SubscriptionManager::class, [
            'update' => Stub::once(),
        ]);
        $I->mockService(SubscriptionManager::class, $subscriptionManager);
        $worker = $I->grabService(UpdateWorker::class);
        $worker->execute(new AMQPMessage(json_encode(["version" => 1, "userId" => $userId])));
    }

    public function testBackoff(\TestSymfonyGuy $I)
    {
        $userId = $I->createAwUser();
        $I->createAwMobileDevice($userId);
        $subscriptionManager = $I->stubMakeEmpty(SubscriptionManager::class, [
            'update' => Stub::never(),
        ]);
        $I->mockService(SubscriptionManager::class, $subscriptionManager);
        $worker = $I->grabService(UpdateWorker::class);
        $worker->execute(new AMQPMessage(json_encode(["version" => 1, "userId" => $userId, "time" => time()])));
    }

    public function testValidTime(\TestSymfonyGuy $I)
    {
        $userId = $I->createAwUser();
        $I->createAwMobileDevice($userId);
        $subscriptionManager = $I->stubMakeEmpty(SubscriptionManager::class, [
            'update' => Stub::once(),
        ]);
        $I->mockService(SubscriptionManager::class, $subscriptionManager);
        $worker = $I->grabService(UpdateWorker::class);
        $worker->execute(new AMQPMessage(json_encode(["version" => 1, "userId" => $userId, "time" => time() - 60])));
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $I->verifyMocks();
    }
}
