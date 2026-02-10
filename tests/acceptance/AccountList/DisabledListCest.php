<?php

use Codeception\Module\Aw;
use Codeception\Scenario;

/**
 * @group frontend-acceptance
 */
class DisabledListCest
{
    public function _before(WebGuy $I)
    {
    }

    public function updateRow(WebGuy $I, Scenario $scenario)
    {
        $login = 'test' . $I->grabRandomString(5);

        $user = $I->createAwUser($login, null, ['InBeta' => 1, 'BetaApproved' => 1], true);
        $accountId = $I->createAwAccount($user, Aw::TEST_PROVIDER_ID, "balance.increase", null, ['Disabled' => 1]); // ACCOUNT_PREVENT_LOCKOUT

        $I->wantTo("Account list disabled elements");
        $I->amOnPage($I->grabService('router')->generate(AccountListPage::$router, ['_switch_user' => $login]));
        $I->waitForElementVisible(AccountListPage::$is_successInitialized, 25);
        $accountRows = $I->grabNumberOfElements(AccountListPage::$selector_accountDisabledRow);
        $I->assertGreaterThan(0, $accountRows);
        $updatingAccountRows = $I->grabNumberOfElements(AccountListPage::$selector_accountDisabledRow . AccountListPage::$selector_updaterIcon);
        $I->assertGreaterThan(0, $updatingAccountRows);
        $I->assertEquals($accountRows, $updatingAccountRows);

        //		$firstRowId = $I->grabAttributeFrom(AccountListPage::$selector_accountDisabledRow . '/..', 'id');

        $I->performOn('#tipjsOverlay', ['click' => '#tipjsOverlay'], 5);

        $I->click(AccountListPage::$selector_accountDisabledRow . AccountListPage::$selector_updaterIcon . '[1]/..');
        $I->wait(10);
        $I->see('This account is marked as "Disabled"');
    }
}
