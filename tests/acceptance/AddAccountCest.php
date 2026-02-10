<?php

use Codeception\Scenario;

class AddAccountCest
{
    public function searchProvider(WebGuy $I, Scenario $scenario)
    {
        $I->wantTo("choice of provider to adding");
        //        $userSteps = new \AwardWallet\Tests\Acceptance\_steps\UserSteps($scenario);
        // login as siteadmin
        //        $userSteps->login(CommonUser::$admin_login, CommonUser::$admin_password);
        $I->amOnPage($I->grabService('router')->generate('aw_select_provider', ['_switch_user' => CommonUser::$admin_login]));
        $this->checkProviderList($I);
    }

    public function supportedPrograms(WebGuy $I)
    {
        $I->wantTo("view supported programs");
        $I->amOnPage($I->grabService('router')->generate('aw_supported'));
        $this->checkProviderList($I);
        $I->canSee("Search for a program");
        $I->wait(5);
        $I->click('Alamo');
        $I->wait(5);
        $I->canSee('AwardWallet Member Reviews');
    }

    private function checkProviderList(WebGuy $I)
    {
        $I->waitForElement(AddAccountPage::$selector_searchResultsRows);
        $I->fillField(AddAccountPage::$selector_searchProviderInput, 'xxxxxxxxxxx');
        $I->wait(5);
        $I->seeElement(AddAccountPage::$selector_searchNoResults);
        $I->fillField(AddAccountPage::$selector_searchProviderInput, 'American Airlines');
        $I->wait(5);
        $I->see('American Airlines', AddAccountPage::$selector_searchResultsRows);
        $I->click('Rentals');
        $I->wait(5);
        $I->dontSee('American Airlines', AddAccountPage::$selector_searchResultsRows);
    }
}
