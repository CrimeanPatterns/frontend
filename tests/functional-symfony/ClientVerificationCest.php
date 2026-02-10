<?php

namespace AwardWallet\Tests\FunctionalSymfony;

/**
 * @group frontend-functional
 */
class ClientVerificationCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function _before(\TestSymfonyGuy $I)
    {
        $I->resetLockout('ip', '127.0.0.1');
        $I->resetLockout('ip');
        $I->setServerParameter("whiteListedIp", "");
    }

    public function testValidHeaderLoginSuccess(\TestSymfonyGuy $I)
    {
        $I->wantTo("test login success if X-Scripted header is present and valid");

        $login = 'test' . $I->grabRandomString(6);
        $password = $I->grabRandomString(10);
        $I->createAwUser($login, $password);

        $I->amOnPage("/");

        $I->saveCsrfToken();

        $I->sendPOST("/user/check", []);

        $session = $I->grabService('session');
        $clientCheck = $session->get('client_check');

        $I->haveHttpHeader("X-Scripted", $clientCheck['result']);

        $I->sendPOST("/login_check", ["login" => $login, "password" => $password, "_csrf_token" => $I->grabDataFromJsonResponse("csrf_token")]);
        $I->see('"success":true');
    }

    public function testNoHeaderLoginFail(\TestSymfonyGuy $I)
    {
        $I->wantTo("test login failure if X-Scripted header is not present");

        $login = 'test' . $I->grabRandomString(6);
        $password = $I->grabRandomString(10);
        $I->createAwUser($login, $password);

        $I->amOnPage("/");

        $I->saveCsrfToken();

        $I->sendPOST("/user/check", []);

        $I->sendPOST("/login_check", ["login" => $login, "password" => $password, "_csrf_token" => $I->grabDataFromJsonResponse("csrf_token")]);
        $I->see('"success":false');
    }

    public function testInvalidHeaderLoginFail(\TestSymfonyGuy $I)
    {
        $I->wantTo("test login failure if X-Scripted header is invalid");

        $login = 'test' . $I->grabRandomString(6);
        $password = $I->grabRandomString(10);
        $I->createAwUser($login, $password);

        $I->amOnPage("/");

        $I->saveCsrfToken();

        $I->sendPOST("/user/check", []);

        $I->haveHttpHeader("X-Scripted", rand(300, 500));
        $I->sendAjaxPostRequest("/login_check", ["login" => $login, "password" => $password, "_csrf_token" => $I->grabDataFromJsonResponse("csrf_token")]);
        $I->see('"success":false');
    }
}
