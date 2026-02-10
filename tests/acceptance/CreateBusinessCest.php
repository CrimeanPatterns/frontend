<?php

use Codeception\Scenario;

class CreateBusinessCest
{
    public function test(WebGuy $I, Scenario $scenario)
    {
        $I->wantTo("register new user");
        $userSteps = new \AwardWallet\Tests\Acceptance\_steps\UserSteps($scenario);
        $userSteps->register();

        $I->wantTo('test existing company name');
        $I->amOnPage($I->grabService('router')->generate('aw_user_convert_to_business'));
        $I->fillField('Company name', 'BookYourAward');
        $I->click('Proceed');
        $I->waitForText('This company name already taken.');

        $I->wantTo('create business');
        $I->fillField('Company name', 'Test Business Account');
        $I->click('Proceed');
        $I->waitForText('Proceed to business interface');
        $I->click('Proceed to business interface');
        $I->seeLink('Business');
    }
}
