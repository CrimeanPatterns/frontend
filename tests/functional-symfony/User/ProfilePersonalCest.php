<?php

namespace AwardWallet\Tests\FunctionalSymfony\User;

/**
 * @group frontend-functional
 */
class ProfilePersonalCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function testLogin(\TestSymfonyGuy $I)
    {
        $I->wantTo("test login change");

        $login = 'test' . $I->grabRandomString(5);
        $I->createAwUser($login, null, [
            'FirstName' => 'First',
            'LastName' => 'Last',
        ], false, true);

        $page = $I->grabService('router')->generate('aw_profile_personal');
        $dashboard = $I->grabService('router')->generate('aw_profile_overview');

        $I->comment("invalid login");
        $I->amOnPage($dashboard . "?_switch_user=" . $login);
        $I->see($login);

        $I->amOnPage($page);
        $I->fillField(['name' => 'profile_personal[login]'], 'a');
        $I->click('button[type=submit]');
        $I->see('This value is too short. It should have 4 characters or more.');

        $I->fillField(['name' => 'profile_personal[login]'], str_repeat('a', 31));
        $I->click('button[type=submit]');
        $I->see('This value is too long. It should have 30 characters or less.');

        $I->fillField(['name' => 'profile_personal[login]'], '******');
        $I->click('button[type=submit]');
        $I->see('Please use only English letters or numbers. No Spaces.');

        $I->fillField(['name' => 'profile_personal[login]'], '******');
        $I->click('button[type=submit]');
        $I->see('Please use only English letters or numbers. No Spaces.');

        $I->fillField(['name' => 'profile_personal[login]'], 'SiteAdmin');
        $I->click('button[type=submit]');
        $I->see('This user name is already taken');

        $I->comment("valid login");
        $newLogin = 'newtest' . $I->grabRandomString();

        $I->fillField(['name' => 'profile_personal[login]'], $newLogin);
        $I->click('button[type=submit]');
        $I->amOnPage($dashboard);
        $I->see($newLogin);
    }

    public function testFirstLastNames(\TestSymfonyGuy $I)
    {
        $I->wantTo("test first and last names change");

        $login = 'test' . $I->grabRandomString(5);
        $I->createAwUser($login, null, [
            'FirstName' => 'First',
            'LastName' => 'Last',
        ], false, true);

        $page = $I->grabService('router')->generate('aw_profile_personal');

        $newFirstname = $I->grabRandomString();
        $newLastname = $I->grabRandomString();

        $I->amOnPage($page . "?_switch_user=" . $login);

        $I->see('First Last', 'div.info');

        $I->fillField(['name' => 'profile_personal[firstname]'], $newFirstname);
        $I->fillField(['name' => 'profile_personal[lastname]'], $newLastname);
        $I->click('button[type=submit]');

        $I->see($newFirstname . ' ' . $newLastname, 'div.info');
    }
}
