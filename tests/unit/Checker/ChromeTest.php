<?php

namespace AwardWallet\Tests\Unit\Checker;

use AwardWallet\Tests\Unit\BaseUserTest;

/**
 * @group selenium
 */
class ChromeTest extends BaseUserTest
{
    public function testLogin()
    {
        // Chrome.Simple will try to login to awardwallet.dev site with invalid credentials
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), "testprovider", "Chrome.Simple");
        $this->aw->checkAccount($accountId);

        $this->db->seeInDatabase("Account", ["AccountID" => $accountId, "ErrorMessage" => "Invalid user name or password"]);
    }
}
