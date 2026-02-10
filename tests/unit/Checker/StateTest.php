<?php

namespace AwardWallet\Tests\Unit\Checker;

use AwardWallet\Tests\Unit\BaseUserTest;

/**
 * @group selenium
 */
class StateTest extends BaseUserTest
{
    /**
     * @dataProvider checkStrategy
     */
    public function testLogin($local)
    {
        return;
        // Chrome.Simple will try to login to awardwallet.dev site with invalid credentials
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), "testprovider", "Checker.BrowserState");

        $this->aw->checkAccount($accountId, true, $local);
        $this->db->seeInDatabase("Account", ["AccountID" => $accountId, "ErrorCode" => ACCOUNT_QUESTION, "ErrorMessage" => "Why?"]);

        $this->db->haveInDatabase("Answer", ["AccountID" => $accountId, "Question" => "Why?", "Answer" => "Because!"]);
        $this->aw->checkAccount($accountId);
        $this->db->seeInDatabase("Account", ["AccountID" => $accountId, "ErrorCode" => ACCOUNT_CHECKED, "Balance" => "198"]);
    }

    public function checkStrategy()
    {
        return [
            [true],
            [false],
        ];
    }
}
