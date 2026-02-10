<?php

namespace AwardWallet\tests\FunctionalSymfony\Mobile;

use AwardWallet\Tests\FunctionalSymfony\_steps\Mobile\UserSteps;
use Codeception\Scenario;

/**
 * @group mobile
 * @group frontend-functional
 */
class MobileLockoutCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function testUserMobileLoginIpLockout(\TestSymfonyGuy $I, Scenario $scenario)
    {
        $I->wantTo("test ip lockout after 20 failed login attempts in mobile interface");
        $lockerMessage = 'You have been locked out from AwardWallet for 1 hour, due to a large number of invalid login attempts.';

        $I->resetLockout('ip', '127.0.0.1');

        $userSteps = new UserSteps($scenario);

        $userSteps->sendStatus();

        for ($i = 0; $i <= 20; $i++) {
            $username = $I->grabRandomString();
            $password = $I->grabRandomString();
            $I->sendPOST("/m/api/login_check", [
                "login" => $username,
                "password" => $password,
                "_remember_me" => "1",
                "_otc" => "",
            ]);
            $message = $I->grabDataFromJsonResponse('message');

            if ($i == 20) {
                $I->assertEquals($lockerMessage, $message);
            } else {
                $I->assertNotEquals($lockerMessage, $message);
            }
        }
    }

    public function testUserMobileLoginNameLockout(\TestSymfonyGuy $I, Scenario $scenario)
    {
        $I->wantTo("test lockout after 10 failed login attempts with same existing login in mobile interface");
        $lockerMessage = 'Your account has been locked out from AwardWallet for 1 hour, due to a large number of invalid login attempts.';

        $I->resetLockout('ip', '127.0.0.1');

        $login = 'test' . $I->grabRandomString(5);
        $password = 'tup' . $I->grabRandomString();
        $I->createAwUser($login, $password);

        $userSteps = new UserSteps($scenario);

        $userSteps->sendStatus();

        for ($i = 0; $i <= 10; $i++) {
            $password = $I->grabRandomString();
            $I->sendPOST("/m/api/login_check", ["login" => $login, "password" => $password, "_remember_me" => "false"]);
            $message = $I->grabDataFromJsonResponse('message');

            if ($i == 10) {
                $I->assertEquals($lockerMessage, $message);
            } else {
                $I->assertNotEquals($lockerMessage, $message);
            }
        }
    }
}
