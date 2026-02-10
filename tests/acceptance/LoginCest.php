<?php

class LoginCest
{
    /**
     * @group frontend-acceptance
     * @group acceptance1
     */
    public function index(WebGuy $I)
    {
        $login = "u" . bin2hex(random_bytes(8));
        $password = "uP1" . bin2hex(random_bytes(8));
        $name = "Name" . bin2hex(random_bytes(4));
        $I->createAwUser($login, $password, ["FirstName" => $name]);
        $I->wantTo("check new login");
        $I->amOnPage($I->grabService('router')->generate(\LoginPage::$_new_route));
        $ip = $I->getClientIp();
        $I->click(\LoginPage::$_new_selector_button);
        $I->waitForElementVisible(\LoginPage::$_new_selector_popup, 20);
        $I->fillField(\LoginPage::$_new_selector_login, $login);
        $I->fillField(\LoginPage::$_new_selector_password, '12345');
        $I->click(\LoginPage::$_new_selector_submit);
        $I->waitForText('Invalid user name or password');
        $I->fillField(\LoginPage::$_new_selector_password, $password);
        $I->click(\LoginPage::$_new_selector_remember);
        $I->click(\LoginPage::$_new_selector_submit);
        $I->waitForText($name, 30);
    }
}
