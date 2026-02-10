<?php

namespace AwardWallet\Tests\Unit\Updater;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Updater\Event\DisabledEvent;
use AwardWallet\MainBundle\Updater\Event\ErrorEvent;
use AwardWallet\MainBundle\Updater\Event\StartProgressEvent;
use AwardWallet\MainBundle\Updater\Option;

/**
 * @group frontend-unit
 * @group slow
 */
class LockoutTest extends UpdaterBase
{
    public function _before()
    {
        parent::_before();
        $this->getUpdater()->setOption(Option::CHECK_PROVIDER_GROUP, false);
    }

    public function testLockout()
    {
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), "testprovider", 'lockout', 'test');

        $result = $this->getUpdater()->start([$accountId]);
        $expected = [
            new StartProgressEvent($accountId, 30, null),
            new DisabledEvent($accountId),
        ];
        $this->waitEvents($result, $expected);
        $this->db->seeInDatabase('Account', [
            'AccountID' => $accountId,
            'ErrorCode' => ACCOUNT_LOCKOUT,
            'Disabled' => 1,
            'DisableReason' => Account::DISABLE_REASON_LOCKOUT,
        ]);
    }

    public function testPreventLockoutAfter3InvalidLogons()
    {
        $this->user->setAccountlevel(ACCOUNT_LEVEL_AWPLUS);
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), "testprovider", 'invalid.logon', 'test');

        $result = $this->getUpdater()->start([$accountId]);
        $expected = [
            new StartProgressEvent($accountId, 30, null),
            new ErrorEvent($accountId, ACCOUNT_INVALID_PASSWORD),
        ];
        $this->waitEvents($result, $expected);
        $result = $this->getUpdater()->start([$accountId]);
        $expected = [
            new StartProgressEvent($accountId, 30, null),
            new ErrorEvent($accountId, ACCOUNT_INVALID_PASSWORD),
        ];
        $this->waitEvents($result, $expected);
        $result = $this->getUpdater()->start([$accountId]);
        $expected = [
            new StartProgressEvent($accountId, 30, null),
            new DisabledEvent($accountId),
        ];
        $this->waitEvents($result, $expected);

        $this->assertEquals(ACCOUNT_PREVENT_LOCKOUT, $this->db->grabFromDatabase("Account", "ErrorCode", ["AccountID" => $accountId]));
        $this->assertEquals(Account::DISABLE_REASON_PREVENT_LOCKOUT, $this->db->grabFromDatabase("Account", "DisableReason", ["AccountID" => $accountId]));
    }

    /**
     * @dataProvider repeatedErrors
     */
    public function testNotDisableAfterErrorsWithinDay($errorCount, $errorCode, $login, $days, $disableReason)
    {
        $this->user->setAccountlevel(ACCOUNT_LEVEL_AWPLUS);
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), "testprovider", $login, 'test');

        for ($n = 0; $n < $errorCount; $n++) {
            $result = $this->getUpdater()->start([$accountId]);
            $expected = [
                new StartProgressEvent($accountId, 30, null),
                new ErrorEvent($accountId, $errorCode),
            ];
            $this->waitEvents($result, $expected);
        }

        $this->db->seeInDatabase("Account", ["AccountID" => $accountId, "ErrorCode" => $errorCode, "Disabled" => 0]);
    }

    /**
     * @dataProvider repeatedErrors
     */
    public function testDisableAfterErrorsAndTime($errorCount, $errorCode, $login, $days, $disableReason)
    {
        $this->user->setAccountlevel(ACCOUNT_LEVEL_AWPLUS);
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), "testprovider", $login, 'test', ['ErrorCode' => ACCOUNT_UNCHECKED]);

        $update = function () use ($accountId, $errorCode) {
            $expected = [
                new ErrorEvent($accountId, $errorCode),
            ];
            $this->updateAccount($accountId, $expected);
        };

        $update();

        $this->db->executeQuery("update Account set ErrorDate = adddate(now(), -" . ($days + 1) . ") where AccountID = $accountId");

        for ($n = 2; $n < $errorCount; $n++) {
            codecept_debug("try $n");
            $update();
        }

        $expected = [
            !is_null($disableReason) ? new DisabledEvent($accountId) : new ErrorEvent($accountId, $errorCode),
        ];
        $this->updateAccount($accountId, $expected);

        $this->db->seeInDatabase('Account', [
            'AccountID' => $accountId,
            'ErrorCode' => $errorCode,
            'Disabled' => !is_null($disableReason),
            'DisableReason' => $disableReason,
        ]);
    }

    public function repeatedErrors()
    {
        return [
            [10, ACCOUNT_PROVIDER_ERROR, "provider.error", 90, null],
            [10, ACCOUNT_ENGINE_ERROR, "unknown.error", 180, null],
        ];
    }
}
