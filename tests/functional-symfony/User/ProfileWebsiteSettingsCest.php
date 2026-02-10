<?php

namespace AwardWallet\Tests\FunctionalSymfony\User;

/**
 * @group frontend-functional
 */
class ProfileWebsiteSettingsCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function testWebsiteSettings(\TestSymfonyGuy $I)
    {
        $I->wantTo("test website settings");

        $login = 'testsettings' . $I->grabRandomString(5);
        $userId = $I->createAwUser($login, null, [
            'FirstName' => 'First',
            'LastName' => 'Last',
        ], true, true);

        $page = $I->grabService('router')->generate('aw_profile_settings');
        $I->amOnPage($page . "?_switch_user=" . $login);
        $I->see("Edit Website Settings");
        $I->seeOptionIsSelected("Default Password Storage", "With AwardWallet.com");

        $I->seeCheckboxIsChecked("Show splash screen ads and promos immediately after logging in (AwardWallet Plus Only)");
        $I->seeElement('#websettings_splashadsdisabled:disabled');

        $I->seeCheckboxIsChecked("Use affiliate links during auto-login (AwardWallet Plus Only)");
        $I->seeElement('#websettings_linkadsdisabled:disabled');

        $I->seeCheckboxIsChecked("Show credit card ads in the list of accounts (AwardWallet Plus Only)");
        $I->seeElement('#websettings_listadsdisabled:disabled');

        $I->seeCheckboxIsChecked("Show ads in the AwardWallet blog posts (AwardWallet Plus Only)");
        $I->seeElement('#websettings_isBlogPostAds:disabled');

        $I->seeInDatabase("Usr", ["UserID" => $userId, "SplashAdsDisabled" => 0, "LinkAdsDisabled" => 0, "ListAdsDisabled" => 0, "IsBlogPostAds" => 1]);

        // trying to change disabled fields
        $I->uncheckOption('#websettings_splashadsdisabled');
        $I->uncheckOption('#websettings_linkadsdisabled');
        $I->uncheckOption('#websettings_listadsdisabled');
        $I->uncheckOption('#websettings_isBlogPostAds');

        $I->selectOption("Default Password Storage", "Locally on this computer");
        $I->click("Submit");
        $I->see("settings have been updated");

        // no disabled field should be changed
        $I->seeInDatabase("Usr", ["UserID" => $userId, "SplashAdsDisabled" => 0, "LinkAdsDisabled" => 0, "ListAdsDisabled" => 0, "IsBlogPostAds" => 1]);

        $I->amOnPage($page);
        $I->click("Submit");
        $I->see("settings have been updated");
    }

    public function testPlus(\TestSymfonyGuy $I)
    {
        $login = 'testsettings' . $I->grabRandomString(5);
        $userId = $I->createAwUser($login, null, [
            'FirstName' => 'First',
            'LastName' => 'Last',
            'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
            'PlusExpirationDate' => (new \DateTime('+1 year'))->format('Y-m-d H:i:s'),
        ], true, true);

        $page = $I->grabService('router')->generate('aw_profile_settings');
        $I->amOnPage($page . "?_switch_user=" . $login);

        $I->seeCheckboxIsChecked("Show splash screen ads and promos immediately after logging in (AwardWallet Plus Only)");
        $I->seeElement('#websettings_splashadsdisabled:enabled');

        $I->seeCheckboxIsChecked("Use affiliate links during auto-login (AwardWallet Plus Only)");
        $I->seeElement('#websettings_linkadsdisabled:enabled');

        $I->seeCheckboxIsChecked("Show credit card ads in the list of accounts (AwardWallet Plus Only)");
        $I->seeElement('#websettings_listadsdisabled:enabled');

        $I->seeCheckboxIsChecked("Show ads in the AwardWallet blog posts (AwardWallet Plus Only)");
        $I->seeElement('#websettings_isBlogPostAds:enabled');

        $I->seeInDatabase("Usr", ["UserID" => $userId, "SplashAdsDisabled" => 0, "LinkAdsDisabled" => 0, "ListAdsDisabled" => 0, "IsBlogPostAds" => 1]);

        $I->uncheckOption('#websettings_splashadsdisabled');
        $I->uncheckOption('#websettings_linkadsdisabled');
        $I->uncheckOption('#websettings_listadsdisabled');
        $I->uncheckOption('#websettings_isBlogPostAds');

        $I->click("Submit");
        $I->see("settings have been updated");

        $I->seeInDatabase("Usr", ["UserID" => $userId, "SplashAdsDisabled" => 1, "LinkAdsDisabled" => 1, "ListAdsDisabled" => 1, "IsBlogPostAds" => 0]);
    }

    public function testPlusOptionsShownAsFree(\TestSymfonyGuy $I)
    {
        $login = 'testsettings' . $I->grabRandomString(5);
        $userId = $I->createAwUser($login, null, [
            'FirstName' => 'First',
            'LastName' => 'Last',
            "SplashAdsDisabled" => 1,
            "LinkAdsDisabled" => 1,
            "ListAdsDisabled" => 1,
            "IsBlogPostAds" => 0,
        ], true, true);

        $page = $I->grabService('router')->generate('aw_profile_settings');
        $I->amOnPage($page . "?_switch_user=" . $login);

        $I->seeCheckboxIsChecked("Show splash screen ads and promos immediately after logging in (AwardWallet Plus Only)");
        $I->seeElement('#websettings_splashadsdisabled:disabled');

        $I->seeCheckboxIsChecked("Use affiliate links during auto-login (AwardWallet Plus Only)");
        $I->seeElement('#websettings_linkadsdisabled:disabled');

        $I->seeCheckboxIsChecked("Show credit card ads in the list of accounts (AwardWallet Plus Only)");
        $I->seeElement('#websettings_listadsdisabled:disabled');

        $I->seeCheckboxIsChecked("Show ads in the AwardWallet blog posts (AwardWallet Plus Only)");
        $I->seeElement('#websettings_isBlogPostAds:disabled');

        $I->seeInDatabase("Usr", ["UserID" => $userId, "SplashAdsDisabled" => 1, "LinkAdsDisabled" => 1, "ListAdsDisabled" => 1, "IsBlogPostAds" => 0]);

        $I->click("Submit");
        $I->see("settings have been updated");

        // should not be changed
        $I->seeInDatabase("Usr", ["UserID" => $userId, "SplashAdsDisabled" => 1, "LinkAdsDisabled" => 1, "ListAdsDisabled" => 1, "IsBlogPostAds" => 0]);
    }
}
