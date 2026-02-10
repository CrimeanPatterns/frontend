<?php

use Codeception\Scenario;

class RecoverPasswordCest
{
    /**
     * @group auth
     */
    public function index(WebGuy $I, Scenario $scenario)
    {
        $I->wantTo("check new recover password");

        $I->amOnPage($I->grabService('router')->generate(\RecoverPage::$route));
        $I->click(\LoginPage::$_new_selector_button);
        $I->waitForElementVisible(\LoginPage::$_new_selector_popup, 10);
        $I->click(\RecoverPage::$selector_button);
        $I->waitForElementVisible(\RecoverPage::$selector_popup, 10);
        $I->fillField(\RecoverPage::$selector_emailOrLogin, '$%&');
        $I->waitForText('enter at least 4');
        $I->fillField(\RecoverPage::$selector_emailOrLogin, 'no_such_user');
        $I->click(\RecoverPage::$selector_submit);
        $I->waitForText('no user with this email');
        $I->fillField(\RecoverPage::$selector_emailOrLogin, CommonUser::$admin_login);
        $I->waitForElementVisible(\RecoverPage::$selector_submit);
        $I->click(\RecoverPage::$selector_submit);
        $I->waitForText('password has been sent to the email');
        $I->comment('check email');
        $I->waitForSubject('reset password', 10, '-10 seconds');
    }
}
