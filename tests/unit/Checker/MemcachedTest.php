<?php

namespace AwardWallet\Tests\Unit\Checker;

use AwardWallet\Tests\Unit\BaseUserTest;

/**
 * @group frontend-unit
 */
class MemcachedTest extends BaseUserTest
{
    public function testMemcached()
    {
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), "testprovider", "Checker.Memcached");
        $this->aw->checkAccount($accountId);
        $this->db->seeInDatabase("Account", ["AccountID" => $accountId, "ErrorCode" => ACCOUNT_CHECKED]);
    }
}
