<?php

namespace AwardWallet\Tests\FunctionalSymfony\User;

/**
 * @group frontend-functional
 */
class ProfileRegionalCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function testLanguage(\TestSymfonyGuy $I)
    {
        $I->wantTo("test language change");

        $login = 'test' . $I->grabRandomString(5);
        $I->createAwUser($login, null, [], false, true);

        $I->setCookie('NDEnabled', '1');

        $page = $I->grabService('router')->generate('aw_profile_regional');

        $I->comment("language ru");
        $I->amOnPage($page . "?_switch_user=" . $login);
        $I->selectOption(['name' => 'profile_regional[language]'], 'ru');
        $I->click('button[type=submit]');
        $I->see('Аккаунты');

        $I->comment("language en");
        $I->amOnPage($page);
        $I->selectOption(['name' => 'profile_regional[language]'], 'en');
        $I->click('button[type=submit]');
        $I->see('Accounts');
    }

    public function testRegion(\TestSymfonyGuy $I)
    {
        $I->wantTo("test region change");

        $login = 'test' . $I->grabRandomString(5);
        $I->createAwUser($login, null, [], false, true);

        $I->setCookie('NDEnabled', '1');

        $page = $I->grabService('router')->generate('aw_profile_regional');

        $I->comment("auto region -- en");
        $I->amOnPage($page . "?_switch_user=" . $login);
        $I->see('Accounts');
        $I->see('1/31/' . date("y"));

        $I->comment("auto region -- ru");
        $I->selectOption(['name' => 'profile_regional[language]'], 'ru');
        $I->click('button[type=submit]');
        $I->see('Аккаунты');
        $I->see('31.01.' . date("Y"));

        $I->comment("region US on ru: ru_US -> ru");
        $I->amOnPage($page);
        $I->selectOption(['name' => 'profile_regional[region]'], 'US');
        $I->click('button[type=submit]');
        $I->see('Аккаунты');
        $I->see('31.01.' . date("Y"));

        $I->comment("region GB on ru: ru_GB -> ru");
        $I->amOnPage($page);
        $I->selectOption(['name' => 'profile_regional[region]'], 'GB');
        $I->click('button[type=submit]');
        $I->see('Аккаунты');
        $I->see('31.01.' . date("Y"));

        $I->comment("region GB on en: en_GB");
        $I->amOnPage($page);
        $I->selectOption(['name' => 'profile_regional[language]'], 'en');
        $I->click('button[type=submit]');
        $I->see('Accounts');
        $I->see('31/01/' . date("Y"));

        $I->comment("region US on en: en_US");
        $I->amOnPage($page);
        $I->selectOption(['name' => 'profile_regional[region]'], 'US');
        $I->click('button[type=submit]');
        $I->see('Accounts');
        $I->see('1/31/' . date("y"));
    }

    public function testQueryAttributes(\TestSymfonyGuy $I)
    {
        $I->amOnPage('/page/about');
        $I->see('About us');

        $I->amOnPage('/ru/page/about');
        $I->see('О нас');

        $I->amOnPage('/page/about');
        $I->see('About us');

        $I->resetCookie('Locale2', '/', '');
        $I->amOnPage('/page/about');
        $I->see('About us');
    }
}
