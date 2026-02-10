<?php

namespace AwardWallet\Tests\FunctionalSymfony;

/**
 * @group frontend-functional
 */
class LockoutCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    protected $host = '127.0.0.1';
    protected $session;

    public function _before(\TestSymfonyGuy $I)
    {
        //        $I->sendGET('/test/client-info');
        //        $this->host = $I->grabDataFromJsonResponse('host_ip');
        $this->resetLockouts($I);
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $this->resetLockouts($I);
    }

    public function testRegistrationLoginIpLockout(\TestSymfonyGuy $I)
    {
        $I->wantTo("test ip lockout after 10 login checks");

        $login = 'test' . $I->grabRandomString(5);

        $I->amOnPage("/");

        $I->saveCsrfToken();

        for ($i = 0; $i <= 10; $i++) {
            $I->sendAjaxPostRequest("/user/check_login", ["value" => $login]);

            if ($i == 10) {
                $I->see('locked');
            } else {
                $I->dontSee('locked');
            }
        }
    }

    // testUserPasswordLockout should be the first test in suite
    public function testUserPasswordLockout(\TestSymfonyGuy $I)
    {
        $I->wantTo("test lockout after 20 failed login attempts with same password");
        $lockerMessage = '"message":"Invalid user name or password"';

        $password = 'tup' . $I->grabRandomString();

        for ($i = 0; $i <= 20; $i++) {
            $login = 'test' . $i . '_' . $I->grabRandomString();
            $I->createAwUser($login, $password);

            $I->amOnPage("/");

            $I->saveCsrfToken();

            $I->sendPOST("/user/check", []);

            $session = $I->grabService('session');
            $clientCheck = $I->grabService('session')->get('client_check');
            $I->haveHttpHeader("X-Scripted", $clientCheck['result']);

            $I->sendAjaxPostRequest("/login_check", ["login" => $login, "password" => $password, "_csrf_token" => $I->grabDataFromJsonResponse("csrf_token")]);

            if ($i == 20) {
                $I->see($lockerMessage);
            } else {
                $I->see('"success":true');
            }
        }
    }

    public function testRegistrationEmailIpLockout(\TestSymfonyGuy $I)
    {
        $I->wantTo("test ip lockout after 10 email checks");

        $email = $I->grabRandomString() . '@test.com';

        $I->amOnPage("/");

        $I->saveCsrfToken();

        for ($i = 0; $i <= 10; $i++) {
            $I->sendAjaxPostRequest("/user/check_email_2", ["value" => $email, "token" => "fo32jge"]);

            if ($i == 10) {
                $I->see('locked');
            } else {
                $I->dontSee('locked');
            }
        }
    }

    public function testRestorePasswordIpLockout(\TestSymfonyGuy $I)
    {
        $I->wantTo("test ip lockout after 10 forgotten login checks");

        $username = $I->grabRandomString() . '@test.com';

        $I->amOnPage("/");

        $I->saveCsrfToken();

        for ($i = 0; $i <= 5; $i++) {
            $I->sendAjaxPostRequest("/user/restore", ["username" => $username]);

            if ($i == 5) {
                $I->see('"error":"Please wait 5 minutes before next attempt."');
            } else {
                $I->dontSee('"error":"Please wait 5 minutes before next attempt."');
            }
        }
    }

    public function testUserLoginIpLockout(\TestSymfonyGuy $I)
    {
        $I->wantTo("test ip lockout after 20 failed login attempts");
        $lockerMessage = '"message":"You have been locked out from AwardWallet for 1 hour, due to a large number of invalid login attempts."';

        $I->amOnPage("/");

        $I->saveCsrfToken();

        $I->sendPOST("/user/check", []);
        $csrf = $I->grabDataFromJsonResponse("csrf_token");

        for ($i = 0; $i <= 20; $i++) {
            $username = $I->grabRandomString();
            $password = $I->grabRandomString();
            $I->sendAjaxPostRequest("/login_check", ["login" => $username, "password" => $password, "_remember_me" => "false", "_csrf_token" => $csrf]);

            if ($i == 20) {
                $I->see($lockerMessage);
            } else {
                $I->dontSee($lockerMessage);
            }
        }
    }

    public function testUserLoginNameLockout(\TestSymfonyGuy $I)
    {
        $I->wantTo("test lockout after 10 failed login attempts with same existing login");
        $lockerMessage = '"message":"Your account has been locked out from AwardWallet for 1 hour, due to a large number of invalid login attempts."';

        $login = 'test' . $I->grabRandomString(5);
        $password = 'tup' . $I->grabRandomString();
        $I->createAwUser($login, $password);

        $I->amOnPage("/");

        $I->saveCsrfToken();

        $I->sendPOST("/user/check", []);
        $csrf = $I->grabDataFromJsonResponse("csrf_token");

        for ($i = 0; $i <= 10; $i++) {
            $password = $I->grabRandomString();
            $I->sendAjaxPostRequest("/login_check", ["login" => $login, "password" => $password, "_csrf_token" => $csrf]);

            if ($i == 10) {
                $I->see($lockerMessage);
            } else {
                $I->dontSee($lockerMessage);
            }
        }
    }

    public function testVerifyEmailLockout(\TestSymfonyGuy $I)
    {
        $I->wantTo("test lockout after 3 verify email messages");

        $userId = $I->createAwUser();
        $email = $I->grabFromDatabase("Usr", "Email", ["UserID" => $userId]);
        $login = $I->grabFromDatabase("Usr", "Login", ["UserID" => $userId]);
        $I->resetLockout('email', $email);

        $I->amOnPage("/?_switch_user=" . $login);

        $I->saveCsrfToken();
        $lockerMessage = "Please wait 1 hour before next attempt.";

        for ($i = 0; $i <= 2; $i++) {
            $I->sendAjaxPostRequest("/email/verify/send");

            if ($i == 2) {
                $I->see($lockerMessage);
            } else {
                $I->dontSee($lockerMessage);
            }
        }
    }

    private function resetLockouts(\TestSymfonyGuy $I)
    {
        $I->resetLockout('check_login', $this->host);
        $I->resetLockout('check_email', $this->host);
        $I->resetLockout('forgot', $this->host);
        $I->resetLockout('connection_search', $this->host);
        $I->resetLockout('ip', $this->host);
        $I->resetLockout('ip');
    }
}
