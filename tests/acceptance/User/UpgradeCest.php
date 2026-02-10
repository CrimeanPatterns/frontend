<?php

use Codeception\Scenario;

class UpgradeCest
{
    public function upgradeAccount(WebGuy $I, Scenario $scenario)
    {
        $I->wantTo("upgrade account to AW Plus");
        $userSteps = new \AwardWallet\Tests\Acceptance\_steps\UserSteps($scenario);
        $userSteps->register();
        $I->amOnPage($I->grabService('router')->generate(CartPage::$pay_route));
        $I->waitForElementVisible(\CartPage::$selector_one_card, 10);
        $I->click(CartPage::$selector_submit);

        $I->waitForText('Select Payment Type');
        $I->click(CartPage::$selector_test_credit_card);
        $I->click(CartPage::$selector_input_submit);

        $I->wait(5);
        $I->waitForText('Order Details');
        $I->fillField(CartPage::$selector_billing_address1, 'test address');
        $I->fillField(CartPage::$selector_billing_city, 'test city');
        $I->selectOption(CartPage::$selector_billing_country, 'United States');
        $I->waitForElement(CartPage::$selector_billing_state, 10);
        $I->fillField(CartPage::$selector_billing_zip, '12345');
        $I->fillField(CartPage::$selector_card_number, '449283187232335');
        $I->fillField(CartPage::$selector_card_security_code, 'aaa');
        $I->selectOption(CartPage::$selector_card_expiration_month, '1');
        $I->selectOption(CartPage::$selector_card_expiration_year, date('Y') + 1);
        $I->click(CartPage::$selector_input_submit);
        $I->waitForText('Your credit card number is invalid', 30);
        $I->waitForText('Invalid security code. Use numeric characters only.', 30);

        $I->fillField(CartPage::$selector_card_number, '4492831872323352');
        $I->fillField(CartPage::$selector_card_security_code, '123');
        $I->selectOption(CartPage::$selector_card_expiration_month, '1');
        $I->selectOption(CartPage::$selector_card_expiration_year, date('Y') + 1);
        $I->click(CartPage::$selector_input_submit);

        $I->wait(5);
        $I->waitForText('Order Preview', 30);
        $I->click(CartPage::$selector_pay);
        $I->waitForText('You have successfully paid', 30);

        $userSteps->delete(\CommonUser::$user_password);
    }
}
