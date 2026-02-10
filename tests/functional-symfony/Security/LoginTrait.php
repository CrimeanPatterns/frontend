<?php

namespace AwardWallet\Tests\FunctionalSymfony\Security;

use Google\Authenticator\GoogleAuthenticator;

trait LoginTrait
{
    private function loadCSRF(\TestSymfonyGuy $I)
    {
        $I->resetCookie("MOCKSESSID");
        $I->sendGET("/test/client-info");
        $I->seeResponseCodeIs(200);
        $I->saveCsrfToken();
    }

    private function loginUser(array $user, \TestSymfonyGuy $I, $code = null, bool $rememberMe = false)
    {
        $this->loadCSRF($I);
        $I->sendPOST("/user/check", []);
        $clientCheck = $I->grabService('session')->get('client_check');
        $I->haveHttpHeader("X-Scripted", $clientCheck['result']);
        $params = ["login" => $user['login'], "password" => $user['password'], "_csrf_token" => $I->grabDataFromJsonResponse("csrf_token")];

        if (!empty($code)) {
            $params['_otc'] = $code;
        }

        if ($rememberMe) {
            $params['_remember_me'] = 'true';
        }
        $I->sendPOST("/login_check", $params);
    }

    private function createUser(\TestSymfonyGuy $I, array $userFields = [], bool $staff = false)
    {
        $login = 'test' . $I->grabRandomString(5);
        $password = 'tup' . $I->grabRandomString();
        $userId = $I->createAwUser($login, $password, $userFields, $staff);

        return \array_merge(
            [
                'userId' => $userId,
                'login' => $login,
                'password' => $password,
                'email' => $I->grabFromDatabase('Usr', 'Email', ['UserID' => $userId]),
            ],
            $staff ?
                ['oneTimeCodeByApp' => (new GoogleAuthenticator())->getCode($I->grabFromDatabase('Usr', 'GoogleAuthSecret', ['UserID' => $userId]))] :
                []
        );
    }
}
