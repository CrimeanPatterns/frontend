<?php

namespace AwardWallet\Tests\FunctionalSymfony\User;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Security\Reauthentication\ReauthenticatorWrapper;
use Codeception\Module\Aw;

/**
 * @group frontend-functional
 */
class ProfileChangePasswordCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function testRecover(\TestSymfonyGuy $I)
    {
        $I->wantTo("check new recover password");

        $login = 'tcp' . $I->grabRandomString();
        $password = 'tup' . $I->grabRandomString();
        $code = str_repeat("6", 32);
        $userId = $I->createAwUser($login, $password, [
            'FirstName' => 'First',
            'LastName' => 'Last',
            'ResetPasswordCode' => $code,
            'ResetPasswordDate' => date("Y-m-d H:i:s"),
        ], true, true);

        $I->amOnRoute('aw_profile_change_password_feedback', [
            'id' => $userId,
            'code' => $code,
        ]);

        $I->see("New password");
        $I->fillField("New password", "12345");
        $I->fillField("Confirm password", "54321");
        $I->click("Save");
        $I->see("Passwords must match");

        $I->fillField("New password", "");
        $I->click("Save");
        $I->see("field is required");

        $I->fillField("New password", $password);
        $I->fillField("Confirm password", $password);
        $I->click("Save");
        $I->see("Your new password should not be the same as your old password");

        $I->fillField("New password", "awdeveloper");
        $I->fillField("Confirm password", "awdeveloper");
        $I->assertEmpty($I->grabFromDatabase("Usr", "ChangePasswordDate", ["Login" => $login]));
        $I->assertEmpty($I->grabFromDatabase("Usr", "ChangePasswordMethod", ["Login" => $login]));
        $I->click("Save");
        $I->see("All your Rewards & Travel in One Place");
        $I->assertNotEmpty($I->grabFromDatabase("Usr", "ChangePasswordDate", ["Login" => $login]));
        $I->assertEquals(Usr::CHANGE_PASSWORD_METHOD_LINK, $I->grabFromDatabase("Usr", "ChangePasswordMethod", ["Login" => $login]));
    }

    public function testChangePassword(\TestSymfonyGuy $I)
    {
        $I->wantTo("check change password");

        $login = 'tcp' . $I->grabRandomString(10);
        $password = 'tup' . $I->grabRandomString();
        $I->createAwUser($login, $password, [
            'FirstName' => 'First',
            'LastName' => 'Last',
            'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
            'PlusExpirationDate' => (new \DateTime('+1 year'))->format('Y-m-d H:i:s'),
        ]);
        $I->mockService(ReauthenticatorWrapper::class, $I->stubMake(ReauthenticatorWrapper::class, [
            'isReauthenticated' => true,
            'reset' => null,
        ]));

        $I->amOnRoute('aw_profile_change_password', ['_switch_user' => $login]);
        $I->fillField("New password", $password);
        $I->fillField("Confirm password", $password);
        $I->click("Save");
        $I->see("Your new password should not be the same as your old password");

        $oldSessionId = $I->grabCookie('MOCKSESSID');
        $I->fillField("New password", "awdeveloper");
        $I->fillField("Confirm password", "awdeveloper");
        $I->assertEmpty($I->grabFromDatabase("Usr", "ChangePasswordDate", ["Login" => $login]));
        $I->assertEmpty($I->grabFromDatabase("Usr", "ChangePasswordMethod", ["Login" => $login]));
        $I->click("Save");
        $I->see("successfully changed your password");
        $I->assertNotEmpty($I->grabFromDatabase("Usr", "ChangePasswordDate", ["Login" => $login]));
        $I->assertEquals(Usr::CHANGE_PASSWORD_METHOD_PROFILE, $I->grabFromDatabase("Usr", "ChangePasswordMethod", ["Login" => $login]));

        $newSessionId = $I->grabCookie('MOCKSESSID');
        $I->assertNotEquals($oldSessionId, $newSessionId);

        $I->comment("logout");
        $I->amOnPage("/security/logout");
        $I->sendGET('/m/api/login_status');
        $I->assertFalse($I->grabDataFromJsonResponse('authorized'));

        $I->amOnPage("/");
        $I->saveCsrfToken();

        $I->sendPOST("/user/check", []);
        $csrf = $I->grabDataFromJsonResponse("csrf_token");

        $clientCheck = $I->grabService('session')->get('client_check');
        $I->haveHttpHeader("X-Scripted", $clientCheck['result']);
        $I->sendAjaxPostRequest("/login_check", ["login" => $login, "password" => "awdeveloper", "_remember_me" => "false", "_csrf_token" => $csrf]);
        $I->see('"success":true');
    }

    public function testSetPassword(\TestSymfonyGuy $I)
    {
        $I->wantTo("set password");

        $login = 'setPass' . $I->grabRandomString(5);
        $userId = $I->createAwUser($login);
        $I->updateInDatabase('Usr', ['Pass' => null], ['UserID' => $userId]);
        $I->mockService(ReauthenticatorWrapper::class, $I->stubMake(ReauthenticatorWrapper::class, [
            'isReauthenticated' => true,
            'reset' => null,
        ]));

        $password = Aw::DEFAULT_PASSWORD;
        $I->amOnRoute('aw_profile_change_password', ['_switch_user' => $login]);
        $I->fillField("New password", $password);
        $I->fillField("Confirm password", $password);
        $I->click("Save");
        $I->see("successfully changed your password");
    }
}
