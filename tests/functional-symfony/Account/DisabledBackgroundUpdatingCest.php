<?php

namespace AwardWallet\Tests\FunctionalSymfony\Account;

use AwardWallet\Tests\FunctionalSymfony\Traits\JsonForm;

/**
 * @group frontend-functional
 */
class DisabledBackgroundUpdatingCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use JsonForm;

    private $accountId;

    public function _before(\TestSymfonyGuy $I)
    {
        $userId = $I->createAwUser(null, null, [
            'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
        ], true, true);
        $login = $I->grabFromDatabase("Usr", "Login", ["UserID" => $userId]);

        $providerId = $I->createAwProvider();
        $this->accountId = $I->createAwAccount($userId, $providerId, "balance.random", "pass1");
        $I->assertEquals(0, $I->grabFromDatabase("Account", "DisableBackgroundUpdating", ["AccountID" => $this->accountId]));

        $page = $I->grabService('router')->generate('aw_account_edit', ['accountId' => $this->accountId]);
        $I->amOnPage($page . "?_switch_user=" . $login);
        $I->assertEquals(0, $I->grabFromDatabase("Account", "DisableBackgroundUpdating", ["AccountID" => $this->accountId]));
        $I->dontSeeCheckboxIsChecked("#account_disable_background_updating");
        $I->assertEquals(1, $I->grabFromDatabase("Account", "BackgroundCheck", ["AccountID" => $this->accountId]));
        $I->followRedirects(false);
    }

    public function testDisable(\TestSymfonyGuy $I)
    {
        $I->checkOption("#account_disable_background_updating");
        $I->click("Update Account");
        $I->assertEquals(1, $I->grabFromDatabase("Account", "DisableBackgroundUpdating", ["AccountID" => $this->accountId]));
        $I->assertEquals(0, $I->grabFromDatabase("Account", "BackgroundCheck", ["AccountID" => $this->accountId]));

        $page = $I->grabService('router')->generate('aw_account_edit', ['accountId' => $this->accountId]);
        $I->amOnPage($page);
        $I->seeCheckboxIsChecked("#account_disable_background_updating");
        $I->uncheckOption("#account_disable_background_updating");
        $I->click("Update Account");
        $I->assertEquals(0, $I->grabFromDatabase("Account", "DisableBackgroundUpdating", ["AccountID" => $this->accountId]));
        $I->assertEquals(1, $I->grabFromDatabase("Account", "BackgroundCheck", ["AccountID" => $this->accountId]));
    }

    public function testEnableDisableAction(\TestSymfonyGuy $I)
    {
        $I->saveCsrfToken();
        $I->sendPOST($I->grabService('router')->generate('aw_account_json_enable_disable_background_updating'), [
            'ids' => [$this->accountId],
        ]);

        $I->canSeeInDatabase("Account", ['AccountID' => $this->accountId, 'DisableBackgroundUpdating' => 0]);
        $result = $I->grabDataFromJsonResponse('accounts')[0];

        $I->assertEquals($result['Disabled'], false);
        $I->sendPOST($I->grabService('router')->generate('aw_account_json_enable_disable_background_updating'), [
            'ids' => [$this->accountId],
            'disabled' => 'true',
        ]);

        $I->canSeeInDatabase("Account", ['AccountID' => $this->accountId, 'DisableBackgroundUpdating' => 1]);
        $result = $I->grabDataFromJsonResponse('accounts')[0];

        $I->assertEquals($result['DisableBackgroundUpdating'], 1);
    }
}
