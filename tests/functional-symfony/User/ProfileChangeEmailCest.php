<?php

namespace AwardWallet\Tests\FunctionalSymfony\User;

use AwardWallet\MainBundle\Security\Reauthentication\ReauthenticatorWrapper;

/**
 * @group frontend-functional
 */
class ProfileChangeEmailCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function testChangeEmail(\TestSymfonyGuy $I)
    {
        $I->wantTo("check change email");
        $I->resetLockout('check_login', '127.0.0.1');
        $I->resetLockout('check_email', '127.0.0.1');
        $I->resetLockout('forgot', '127.0.0.1');
        $I->resetLockout('connection_search', '127.0.0.1');
        $I->resetLockout('ip', '127.0.0.1');
        $I->resetLockout('ip');

        $login = 'testce' . $I->grabRandomString();
        $password = 'tup' . $I->grabRandomString();
        $code = str_repeat("6", 32);
        $userId = $I->createAwUser($login, $password, [
            'FirstName' => 'First',
            'LastName' => 'Last',
            'ResetPasswordCode' => $code,
            'ResetPasswordDate' => date("Y-m-d H:i:s"),
            'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
            'PlusExpirationDate' => (new \DateTime('+1 year'))->format('Y-m-d H:i:s'),
        ], true, true);
        $userEmail = $I->grabFromDatabase('Usr', 'Email', ['UserID' => $userId]);
        $I->mockService(ReauthenticatorWrapper::class, $I->stubMake(ReauthenticatorWrapper::class, [
            'isReauthenticated' => true,
            'reset' => null,
        ]));

        $resetPasswordPage = $I->grabService('router')->generate('aw_profile_change_password_feedback', [
            'id' => $userId,
            'code' => $code,
        ]);
        $I->amOnPage($resetPasswordPage);
        $I->see("New password");

        $page = $I->grabService('router')->generate('aw_user_change_email');
        $I->amOnPage($page . "?_switch_user=" . $login);
        $I->see("New email");
        $I->fillField("New email", $I->grabFromDatabase("Usr", "Email", ["Login" => "siteadmin"]));
        $I->click("Change");
        $I->see("This email is already taken");
        $I->fillField("New email", "test" . $I->grabRandomString(10) . time() . "@awardwallet.com");
        $I->click("Change");
        $I->see("successfully changed your email");
        $I->seeEmailTo($userEmail, 'Email Change', 'This is just a courtesy notification that the email address');

        $I->assertEmpty($I->grabFromDatabase("Usr", "ResetPasswordCode", ["UserID" => $userId]));
        $I->assertEmpty($I->grabFromDatabase("Usr", "ResetPasswordDate", ["UserID" => $userId]));
        $I->resetCookie("MOCKSESSID");
        $I->grabService("session")->clear();
        $I->amOnPage($resetPasswordPage);
        $I->dontSee("New password");
    }
}
