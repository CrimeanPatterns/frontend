<?php

namespace AwardWallet\Tests\Unit\Updater;

use AwardWallet\MainBundle\Updater\Event\ErrorEvent;
use AwardWallet\MainBundle\Updater\Event\StartProgressEvent;
use AwardWallet\MainBundle\Updater\Option;

/**
 * @group frontend-unit
 * @group slow
 */
class FraudTest extends UpdaterBase
{
    public function _before()
    {
        parent::_before();
        $this->getUpdater()->setOption(Option::CHECK_PROVIDER_GROUP, false);
    }

    public function testFraud()
    {
        $this->user->setFraud(true);
        $this->em->persist($this->user);
        $this->em->flush();

        $accountId = $this->aw->createAwAccount($this->user->getUserid(), "testprovider", 'balance.random', 'test');
        $this->assertEquals(ACCOUNT_ENGINE_ERROR, $this->db->grabFromDatabase("Account", "ErrorCode", ["AccountID" => $accountId]));

        $result = $this->getUpdater()->start([$accountId]);
        $expected = [
            new StartProgressEvent($accountId, 30, null),
        ];
        $this->waitEvents($result, $expected);
        $memcached = $this->container->get(\Memcached::class);
        $this->assertNotEmpty($memcached->get("fraud_check_" . $accountId));
        $memcached->delete("fraud_check_" . $accountId);
        $expected = [
            new StartProgressEvent($accountId, 30, null),
            new ErrorEvent($accountId, ACCOUNT_INVALID_PASSWORD),
        ];
        $this->waitEvents($result, $expected);
        $this->assertEquals(ACCOUNT_INVALID_PASSWORD, $this->db->grabFromDatabase("Account", "ErrorCode", ["AccountID" => $accountId]));
    }
}
