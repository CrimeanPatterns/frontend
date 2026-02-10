<?php

namespace AwardWallet\Tests\Unit\Checker;

use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\Tests\Unit\BaseUserTest;

class SeleniumStateTest extends BaseUserTest
{
    public function testState()
    {
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), "testprovider", "Checker.SeleniumState");
        $this->db->haveInDatabase("Answer", ["AccountID" => $accountId, "Question" => "Cookie1", "Answer" => StringHandler::getRandomCode(20)]);
        $this->db->haveInDatabase("Answer", ["AccountID" => $accountId, "Question" => "Cookie2", "Answer" => StringHandler::getRandomCode(20)]);

        $this->aw->checkAccount($accountId, false);
        $this->assertEquals(ACCOUNT_CHECKED, $this->db->grabFromDatabase("Account", "ErrorCode", ["AccountID" => $accountId]));
        $this->assertEquals(1, $this->db->grabFromDatabase("Account", "Balance", ["AccountID" => $accountId]));

        $this->aw->checkAccount($accountId, false);
        $this->assertEquals(ACCOUNT_CHECKED, $this->db->grabFromDatabase("Account", "ErrorCode", ["AccountID" => $accountId]));
        $this->assertEquals(2, $this->db->grabFromDatabase("Account", "Balance", ["AccountID" => $accountId]));
    }
}
