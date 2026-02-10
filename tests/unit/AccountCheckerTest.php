<?php

namespace AwardWallet\Tests\Unit;

use Codeception\Module\Aw;

/**
 * @group frontend-unit
 */
class AccountCheckerTest extends BaseUserTest
{
    public function testCurlState()
    {
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), Aw::TEST_PROVIDER_ID, "Checker.State", "log-me-in");
        $this->aw->checkAccount($accountId);
        $this->db->seeInDatabase("Account", ["AccountID" => $accountId, "ErrorCode" => ACCOUNT_CHECKED]);

        $this->db->executeQuery("UPDATE Account SET Pass = 'check-logged-in' WHERE AccountID = $accountId");
        $this->aw->checkAccount($accountId);
        $this->db->seeInDatabase("Account", ["AccountID" => $accountId, "ErrorCode" => ACCOUNT_CHECKED]);

        $this->db->executeQuery("UPDATE Account SET BrowserState = null WHERE AccountID = $accountId");
        $this->aw->checkAccount($accountId);
        $this->db->seeInDatabase("Account", ["AccountID" => $accountId, "ErrorCode" => ACCOUNT_ENGINE_ERROR]);
    }

    public function testMultiField()
    {
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), Aw::TEST_PROVIDER_ID, "Checker.MultiField");
        $this->aw->checkAccount($accountId);
        $this->db->seeInDatabase("Account", ["AccountID" => $accountId, "ErrorCode" => ACCOUNT_QUESTION]);

        $this->db->haveInDatabase("Answer", ["AccountID" => $accountId, "Question" => "Test question", "Answer" => "Test answer"]);
        $this->aw->checkAccount($accountId);
        $this->db->seeInDatabase("Account", ["AccountID" => $accountId, "ErrorCode" => ACCOUNT_CHECKED]);
    }
}
