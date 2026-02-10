<?php

namespace AwardWallet\Tests\FunctionalSymfony\Security;

use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Security\Reauthentication\ReauthenticatorWrapper;
use AwardWallet\Tests\FunctionalSymfony\_steps\Mobile\UserSteps;
use Codeception\Module\Aw;
use Google\Authenticator\GoogleAuthenticator;

use function PHPUnit\Framework\assertNotEquals;

/**
 * @group frontend-functional
 */
class SessionCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    /** @var \TestGuy\Mobile\UserSteps userSteps */
    protected $userSteps;

    private $login;

    private $userId;

    public function _before(\TestSymfonyGuy $I)
    {
        $I->executeQuery("DELETE FROM Session WHERE UserID = " . \CommonUser::$admin_id);
        $scenario = $I->grabScenarioFrom($I);
        /** @var \TestGuy\Mobile\UserSteps userSteps */
        $this->userSteps = new UserSteps($scenario);
        $this->login = 'session-tst' . StringHandler::getRandomCode(10);
        $this->userId = $I->createAwUser($this->login, Aw::DEFAULT_PASSWORD);
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $this->userSteps = null;
    }

    public function testInvalidSessionWithRememberMeCookie(\TestSymfonyGuy $I)
    {
        $I->amOnPage('/account/list?_switch_user=' . $this->login);
        $I->dontSeeCookie("PwdHash");
        $this->commonTests($I);
    }

    public function testInvalidSessionWithoutRememberMeCookie(\TestSymfonyGuy $I)
    {
        $this->userSteps->login($this->login, Aw::DEFAULT_PASSWORD);
        $I->seeCookie("PwdHash");
        $this->commonTests($I);
    }

    public function testOtherSessionsInvalidationAfterPasswordChange(\TestSymfonyGuy $I)
    {
        $I->sendGET('/m/api/login_status?_switch_user=' . $this->login);
        $I->sendGET('/m/api/data');
        $I->grabDataFromJsonResponse('profile.UserID');
        $firstSession = $I->grabCookie('MOCKSESSID');

        $I->resetCookies();
        $I->sendGET('/m/api/login_status?_switch_user=' . $this->login);
        $I->sendGET('/m/api/data');
        $I->grabDataFromJsonResponse('profile.UserID');
        $secondSession = $I->grabCookie('MOCKSESSID');

        assertNotEquals($secondSession, $firstSession, "sessions shouldn't be the same");

        // change password
        $I->resetCookies();
        $I->setCookie("MOCKSESSID", $firstSession);
        $I->sendGET('/m/api/profile/changePassword');
        $token = $I->grabDataFromResponseByJsonPath('$.children[?(@.name = "_token")].value')[0];

        $I->sendPUT('/m/api/profile/changePassword', [
            'oldPassword' => Aw::DEFAULT_PASSWORD,
            'pass' => [
                'first' => $newPassword = 'new ' . Aw::DEFAULT_PASSWORD,
                'second' => $newPassword,
            ],
            '_token' => $token,
        ]);
        $I->seeResponseContainsJson(['success' => true, 'needUpdate' => true]);

        $I->sendGET('/m/api/login_status');
        $I->seeResponseContainsJson(['authorized' => true]);

        $I->resetCookies();
        $I->setCookie("MOCKSESSID", $secondSession);
        $I->sendGET('/m/api/data');
        $I->seeResponseContainsJson(['error' => 'Access denied']);

        $I->seeInDatabase('Session', ['UserID' => $this->userId, 'SessionID' => $secondSession, 'Valid' => 0]);
        $I->seeInDatabase('Session', ['UserID' => $this->userId, 'SessionID' => $firstSession, 'Valid' => 1]);
    }

    public function testOtherSessionsInvalidationAfter2FASetup(\TestSymfonyGuy $I)
    {
        $I->sendGET('/m/api/login_status?_switch_user=' . $this->login);
        $I->sendGET('/m/api/data');
        $I->grabDataFromJsonResponse('profile.UserID');
        $firstSession = $I->grabCookie('MOCKSESSID');

        $I->resetCookies();
        $I->sendGET('/m/api/login_status?_switch_user=' . $this->login);
        $I->sendGET('/m/api/data');
        $I->grabDataFromJsonResponse('profile.UserID');
        $secondSession = $I->grabCookie('MOCKSESSID');

        assertNotEquals($secondSession, $firstSession, "sessions shouldn't be the same");

        // setup 2FA
        $I->resetCookies();
        $I->setCookie("MOCKSESSID", $firstSession);
        $router = $I->grabService('router');
        $page = $router->generate('aw_profile_2factor');
        $I->amOnPage($page . "?_switch_user=" . $this->login);

        $I->mockService(ReauthenticatorWrapper::class, $I->stubMake(ReauthenticatorWrapper::class, [
            'isReauthenticated' => true,
            'reset' => true,
        ]));

        $auth = new GoogleAuthenticator();
        $secret = $I->grabValueFrom(["name" => "two_fact[secret]"]);
        $I->assertNotEmpty($secret);
        $code = $auth->getCode($secret);
        $I->fillField("Code", $code);
        $I->click("Next");
        $I->see("Setup almost complete");
        $I->click("Complete Setup");
        $I->see("authentication has been successfully enabled");

        $I->sendGET('/m/api/login_status');
        $I->seeResponseContainsJson(['authorized' => true]);

        $I->resetCookies();
        $I->setCookie("MOCKSESSID", $secondSession);
        $I->sendGET('/m/api/data');
        $I->seeResponseContainsJson(['error' => 'Access denied']);

        $I->seeInDatabase('Session', ['UserID' => $this->userId, 'SessionID' => $secondSession, 'Valid' => 0]);
        $I->seeInDatabase('Session', ['UserID' => $this->userId, 'SessionID' => $firstSession, 'Valid' => 1]);
    }

    protected function commonTests(\TestSymfonyGuy $I)
    {
        $sessionId = $I->grabCookie('MOCKSESSID');
        $I->seeInDatabase('Session', ['SessionID' => $sessionId, 'Valid' => 1, 'UserID' => $this->userId]);
        $I->shouldHaveInDatabase('Session', [
            'SessionID' => $I->grabRandomString(20),
            'UserID' => $this->userId,
            'IP' => '1.1.1.1',
            'Valid' => 1,
            'LoginDate' => date("Y-m-d H:i:s"),
            'LastActivityDate' => date("Y-m-d H:i:s"),
        ]);
        $I->amOnPage("/account/list");
        $I->see("Currently being used in 1 other location");
        $I->assertEquals($I->grabCookie("MOCKSESSID"), $sessionId);
        $I->executeQuery("UPDATE Session SET Valid = 0 WHERE SessionID = '$sessionId'");
        $I->amOnPage("/account/list");
        $I->assertNotEquals($I->grabCookie("MOCKSESSID"), $sessionId);
    }
}
