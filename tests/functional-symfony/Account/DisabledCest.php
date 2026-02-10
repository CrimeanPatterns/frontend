<?php

namespace AwardWallet\Tests\FunctionalSymfony\Account;

use AwardWallet\Tests\FunctionalSymfony\Traits\JsonForm;

/**
 * @group frontend-functional
 */
class DisabledCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use JsonForm;

    private $accountId;

    public function _before(\TestSymfonyGuy $I)
    {
        $userId = $I->createAwUser(null, null, [], true, true);
        $login = $I->grabFromDatabase("Usr", "Login", ["UserID" => $userId]);

        $this->accountId = $I->createAwAccount($userId, "aeroflot", "balance.random", "pass1", ["Disabled" => 1]);

        $page = $I->grabService('router')->generate('aw_account_edit', ['accountId' => $this->accountId]);
        $I->amOnPage($page . "?_switch_user=" . $login);
        $I->seeCheckboxIsChecked("#account_disabled");
        $I->followRedirects(false);
    }

    public function testResetDisabledOnPasswordChange(\TestSymfonyGuy $I)
    {
        $I->fillField("#account_pass_password", "pass2");
        $oldPass = $I->grabFromDatabase("Account", "Pass", ["AccountID" => $this->accountId]);
        $I->click("Update Account");
        $I->see("Retrieving. Please Wait");
        $I->assertNotEquals($oldPass, $I->grabFromDatabase("Account", "Pass", ["AccountID" => $this->accountId]));
        $I->assertEquals(0, $I->grabFromDatabase("Account", "Disabled", ["AccountID" => $this->accountId]));
    }

    public function testDoNotResetDisabled(\TestSymfonyGuy $I)
    {
        $I->fillField("#account_comment", "some comment");
        $I->click("Update Account");
        $I->seeRedirectTo($I->grabService('router')->generate('aw_account_list') . '?account=' . $this->accountId);
        $I->assertEquals(1, $I->grabFromDatabase("Account", "Disabled", ["AccountID" => $this->accountId]));
        $I->assertEquals("some comment", $I->grabFromDatabase("Account", "Comment", ["AccountID" => $this->accountId]));
    }

    public function testManualResetDisabled(\TestSymfonyGuy $I)
    {
        $I->uncheckOption("#account_disabled");
        $I->click("Update Account");
        $I->see("Retrieving. Please Wait");
        $I->assertEquals(0, $I->grabFromDatabase("Account", "Disabled", ["AccountID" => $this->accountId]));
    }

    public function testEnableDisableAction(\TestSymfonyGuy $I)
    {
        $I->saveCsrfToken();
        $I->sendPOST($I->grabService('router')->generate('aw_account_json_enable_disable'), [
            'ids' => [$this->accountId],
        ]);

        $I->canSeeInDatabase("Account", ['AccountID' => $this->accountId, 'Disabled' => false]);
        $result = $I->grabDataFromJsonResponse('accounts')[0];

        $I->assertEquals($result['Disabled'], false);
        $I->sendPOST($I->grabService('router')->generate('aw_account_json_enable_disable'), [
            'ids' => [$this->accountId],
            'disabled' => 'true',
        ]);

        $I->canSeeInDatabase("Account", ['AccountID' => $this->accountId, 'Disabled' => true]);
        $result = $I->grabDataFromJsonResponse('accounts')[0];

        $I->assertEquals($result['Disabled'], true);
    }
}
