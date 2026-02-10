<?php

namespace AwardWallet\Tests\FunctionalSymfony\_steps;

class JsonSteps extends \TestSymfonyGuy
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function login($login, $password)
    {
        $I = $this;
        $I->wantTo('login');
        $this->sendStatus();
        $this->sendLoginForm($login, $password);
        $I->seeResponseContainsJson(['success' => true]);
    }

    public function sendStatus($authorized = false)
    {
        $I = $this;
        $I->sendGET('/m/api/login_status');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['authorized' => $authorized]);
        $I->saveCsrfToken();
    }

    public function sendLoginForm($login, $password, $rememberMe = '1', $otc = null, $otcRecovery = null)
    {
        $I = $this;
        $preSession = $I->grabCookie('PHPSESSID');
        $postFields = [
            'login' => $login,
            'password' => $password,
            '_remember_me' => $rememberMe,
        ];

        if (null !== $otc) {
            $postFields['_otc'] = $otc;
        }

        if (null !== $otcRecovery) {
            $postFields['_otc_recovery'] = $otcRecovery;
        }

        $I->sendPOST('/m/api/login_check', $postFields);

        if (true === $I->grabDataFromJsonResponse('success')) {
            $I->seeCookie('PwdHash');
            $afterSession = $I->grabCookie('PHPSESSID');
            $this->assertNotEquals($afterSession, $preSession);
        }
        $I->saveCsrfToken();
    }
}
