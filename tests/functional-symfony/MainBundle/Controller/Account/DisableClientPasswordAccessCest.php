<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\Account;

use Symfony\Bundle\FrameworkBundle\Routing\Router;

/**
 * @group frontend-functional
 */
class DisableClientPasswordAccessCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use \AwardWallet\Tests\FunctionalSymfony\Security\LoginTrait;

    public const PROVIDER = 155; // S7

    private $userId;
    private $username;

    /**
     * @var Router
     */
    private $router;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->userId = $I->createAwUser(null, null, [], false, true);
        $this->username = $I->grabFromDatabase('Usr', 'Login', ['UserID' => $this->userId]);
        $this->router = $I->grabService('router');
    }

    public function checkDisableAutologinOption(\TestSymfonyGuy $I)
    {
        $accountId = $this->createFormAccount($I);
        $I->wantTo('Disable autologin');
        $I->amOnPage($this->router->generate('aw_account_edit', ['accountId' => $accountId]));
        $I->checkOption('#account_disableclientpasswordaccess');
        $I->submitForm('#account-form', []);

        $isDisableAutologin = $I->grabFromDatabase('Account', 'DisableClientPasswordAccess', ['AccountID' => $accountId]);
        $I->assertEquals(1, $isDisableAutologin);

        $I->wantTo('Enable autologin without master password');
        $I->amOnPage($this->router->generate('aw_account_edit', ['accountId' => $accountId]));
        $I->uncheckOption('#account_disableclientpasswordaccess');
        $I->submitForm('#account-form', []);

        $isDisableAutologin = $I->grabFromDatabase('Account', 'DisableClientPasswordAccess', ['AccountID' => $accountId]);
        $I->assertEquals(1, $isDisableAutologin);
    }

    public function redirectDisabledTest(\TestSymfonyGuy $I)
    {
        $accountId = $this->createFormAccount($I);
        $I->amOnPage($this->router->generate('aw_account_edit', ['accountId' => $accountId]));
        $I->checkOption('#account_disableclientpasswordaccess');
        $I->submitForm('#account-form', []);

        $I->followRedirects(false);
        $I->amOnPage($this->router->generate('aw_account_redirect', ['ID' => $accountId]));
        $I->dontSeeResponseCodeIs(403);
        $I->dontSee('Access to this account is denied');
        // $loginUrl = $I->grabFromDatabase('Provider', 'LoginURL', ['ProviderID' => self::PROVIDER]);
        // $I->seeRedirectTo($loginUrl);
    }

    public function browserCheckRequest(\TestSymfonyGuy $I)
    {
        $providerId = $I->createAwProvider($providerCode = 'test' . $I->grabRandomString(8), $providerCode, [
            'AutoLogin' => AUTOLOGIN_MIXED,
            'CheckInBrowser' => CHECK_IN_MIXED,
        ]);
        $user_1 = $this->createUser($I);
        $user_2 = $this->createUser($I);

        $passw = $I->grabRandomString(8);
        $accountUser_1 = $I->createAwAccount($user_1['userId'], $providerId, 'somelogin1', $passw);
        $accountUser_2 = $I->createAwAccount($user_2['userId'], $providerId, 'somelogin2', strrev($passw));

        $I->amOnPage($this->router->generate('aw_account_list', ['_switch_user' => $user_1['login']]));
        $I->saveCsrfToken();

        $I->amOnPage($this->router->generate('aw_account_extension_browsercheck', ['accountId' => $accountUser_1]));
        $I->seeResponseCodeIs(405);

        $I->sendPOST($this->router->generate('aw_account_extension_browsercheck', ['accountId' => $accountUser_1]));
        $I->seeResponseContainsJson(['receiveFromBrowser' => true, 'password' => $passw]);

        $I->executeQuery('UPDATE Account SET DisableClientPasswordAccess = 1 WHERE AccountID = ' . $accountUser_1);
        $I->sendPOST($this->router->generate('aw_account_extension_browsercheck', ['accountId' => $accountUser_1]));
        $I->dontSeeResponseContainsJson(['password']);

        $I->sendPOST($this->router->generate('aw_account_extension_browsercheck', ['accountId' => $accountUser_2]));
        $I->seeResponseContainsJson(['error' => 'Access denied']);
    }

    private function createFormAccount(\TestSymfonyGuy $I, $checkedDisabled = false)
    {
        $I->amOnPage($this->router->generate('aw_account_add', ['providerId' => self::PROVIDER]) . '?_switch_user=' . $this->username);
        $I->see('S7 Priority');
        $I->fillField('#account_login', '1234567890');
        $I->fillField('#account_pass', 'password');
        $I->checkOption('#account_notrelated');
        $I->submitForm('#account-form', []);

        $I->seeInDatabase('Account', ['UserID' => $this->userId, 'ProviderID' => self::PROVIDER]);

        return $I->grabFromDatabase('Account', 'AccountID', ['UserID' => $this->userId]);
    }
}
