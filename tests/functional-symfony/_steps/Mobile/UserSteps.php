<?php

namespace AwardWallet\Tests\FunctionalSymfony\_steps\Mobile;

use AwardWallet\MainBundle\Globals\StringHandler;

class UserSteps extends MobileApiAbstractSteps
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    protected static $routes = [
        'status' => '/login_status',
        'login' => '/login_check',
        'register' => '/register',
        'logout' => '/logout',
        'sendRecoveryEmail' => '/recover',
        'changePassword' => '/recover/change/%s/%s',
        'impersonate' => '/impersonate',
        'removePincode' => '/pincode',
    ];

    /**
     * @param string $login
     * @param string $password
     * @return int UserID
     */
    public function login($login, $password, $rememberMe = null, $otc = null)
    {
        $I = $this;
        $I->wantTo('login');
        $this->sendStatus();
        $this->sendLoginForm($login, $password, '1', $otc);
        $I->seeResponseContainsJson(['success' => true]);

        $I->am('user');
        $I->sendGET(AccountSteps::getUrl('data'));
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(200);

        return (int) $I->grabDataFromJsonResponse('profile.UserID');
    }

    public function sendStatus($authorized = false)
    {
        $I = $this;
        $I->sendGET(self::getUrl('status'));
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['authorized' => $authorized]);
        $I->saveCsrfToken();
    }

    public function sendLoginForm($login, $password, $rememberMe = '1', $otc = null, $otcRecovery = null)
    {
        $I = $this;
        $I->handleXScripted();
        $preSession = $I->grabCookie('MOCKSESSID');
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

        $clientCheck = $I->grabService('session')->get('client_check');
        $I->haveHttpHeader("X-Scripted", $clientCheck['result']);
        $I->sendPOST(self::getUrl('login'), $postFields);

        if (true === $I->grabDataFromJsonResponse('success')) {
            $I->seeCookie('PwdHash');
            $afterSession = $I->grabCookie('MOCKSESSID');
            $this->assertNotEquals($afterSession, $preSession);
        }
        $I->saveCsrfToken();
    }

    /**
     * @param string $password
     * @param string $firstName
     * @param string $lastName
     * @param string $email
     * @return int UserID
     */
    public function register($password, $firstName, $lastName, $email, array $expectedErrors = [])
    {
        $I = $this;
        $I->sendGET($url = self::getUrl('register'));
        $I->saveCsrfToken();
        $I->seeResponseCodeIs(200);

        $user = array_fill_keys($I->grabDataFromJsonResponse('children.0.children.*.name'), '');
        $user['pass'] = $password;
        $user['firstname'] = $firstName;
        $user['lastname'] = $lastName;
        $user['email'] = $email;

        $form = array_combine(
            $I->grabDataFromJsonResponse('children.*.name'),
            $I->grabDataFromJsonResponse('children.*.value')
        );

        $form['user'] = $user;
        $form['agree'] = true;
        //        unset(
        //            $form['termsOfUse'],
        //            $form['agree'],
        //        );

        $I->sendPOST($url, $form);
        $I->seeResponseCodeIs(200);

        if ($expectedErrors) {
            $this->checkFormErrors($expectedErrors);

            return 0;
        }

        $I->dontSeeDataInJsonResponse('children');
        $userId = (int) $I->grabDataFromJsonResponse('userId');
        $I->saveCsrfToken();

        // WTF: empty token on logout ????
        //        $I->sendGET(self::getUrl('status'));
        //        $I->seeResponseContainsJson(['authorized' => true]);
        // db cleanup
        $I->haveInsertedInDatabase('Cart', ['CartID' => $I->grabFromDatabase('Cart', 'CartID', ['UserID' => $userId])]);
        $I->haveInsertedInDatabase('Usr', ['UserID' => $userId]);

        $I->sendGET(AccountSteps::getUrl('data'));
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(200);

        $I->grabDataFromJsonResponse('profile.UserID');
        $I->seeEmailTo($email, 'Welcome to AwardWallet.com', null, 60);
        $I->dontSeeEmailTo($email, 'AwardWallet.com Order ID', null, 60);

        return $userId;
    }

    public function createUserAndLogin($loginPrefix = 'foobar-', $password = 'userpass', array $userFields = [], $staff = false)
    {
        $userId = $this->createAwUser(
            $login = $loginPrefix . StringHandler::getRandomCode(10),
            $password,
            $userFields,
            $staff
        );
        $this->login($login, $password);

        return $userId;
    }

    /**
     * @param string $login
     */
    public function sendRecoveryEmail($login)
    {
        $I = $this;
        $I->sendGET($url = self::getUrl('sendRecoveryEmail'));
        $I->seeResponseCodeIs(200);

        $formData = array_combine(
            $I->grabDataFromJsonResponse('form.children.*.name'),
            $I->grabDataFromJsonResponse('form.children.*.value')
        );

        $formData['loginOrEmail'] = $login;

        $I->sendPOST($url, $formData);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['success' => true]);
        $I->seeEmailTo($I->grabFromDatabase('Usr', 'Email', ['Login' => $login]), 'Reset password to AwardWallet.com');
    }

    /**
     * @param string $newPassword
     * @param string $code
     */
    public function changeRecoveryPassword($newPassword, $userID, $code, array $expectedErrors = [])
    {
        $I = $this;

        if (is_array($userID)) {
            $this->assertEquals(3, count($userID));
            [$userID, $login, $email] = $userID;
        }

        $I->sendGET($url = self::getUrl('changePassword', $userID, $code));
        $I->seeResponseCodeIs(200);

        $formData = array_combine(
            $I->grabDataFromJsonResponse('form.children.*.name'),
            $I->grabDataFromJsonResponse('form.children.*.value')
        );

        $I->assertEquals('', $I->grabDataFromResponseByJsonPath('$.form.children..[?(@.name="pass")].children..[?(@.name="first")].value')[0]);
        $I->assertEquals('', $I->grabDataFromResponseByJsonPath('$.form.children..[?(@.name="pass")].children..[?(@.name="second")].value')[0]);

        $pass['first'] = $newPassword;
        $pass['second'] = $newPassword;
        $formData['pass'] = $pass;

        if (isset($login)) {
            $I->assertEquals($login, $I->grabDataFromResponseByJsonPath('$.form.children..[?(@.name="login")].value')[0]);
        }

        if (isset($email)) {
            $I->assertEquals($email, $I->grabDataFromResponseByJsonPath('$.form.children..[?(@.name="email")].value')[0]);
        }

        $I->sendPOST($url, $formData);

        if ($expectedErrors) {
            $this->checkFormErrors($expectedErrors);

            return;
        }
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['success' => true]);
    }

    public function logout(): void
    {
        $I = $this;
        $I->wantTo('logout');
        $I->sendGET(self::getUrl('logout'));
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['success' => true]);
        //        $I->resetCookie("PwdHash");
        //        $I->resetCookie("MOCKSESSID");
    }

    /**
     * @param int $userId
     * @param null $firstName
     * @param null $lastName
     * @param array $fields
     * @return int UserAgentID
     */
    public function addFamilyMember($userId, $firstName = null, $lastName = null, $fields = [])
    {
        $I = $this;
        $fakeUserAgentData = include $this->fakeDataPath . '/fakeUserAgent.php';

        if (isset($firstName)) {
            $fakeUserAgentData['FirstName'] = $firstName;
        }

        if (isset($lastName)) {
            $fakeUserAgentData['LastName'] = $lastName;
        }

        if (!array_key_exists('AgentID', $fields)) {
            $fakeUserAgentData['AgentID'] = $userId;
        }
        $fakeUserAgentData = array_merge($fakeUserAgentData, $fields);

        return $I->haveInDatabase('UserAgent', $fakeUserAgentData);
    }

    public function impersonate($target, $fullImpersonate = false)
    {
        $I = $this;
        $I->sendPOST(self::getUrl('impersonate'), ['loginOrEmail' => $target, 'fullImpersonate' => $fullImpersonate]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['success' => true]);
        $I->seeInDatabase('ImpersonateLog', ['TargetUserID' => (int) $target]);
        $I->saveCsrfToken();
        $impersonatedId = $I->grabDataFromJsonResponse('userId');
        $I->sendGET(AccountSteps::getUrl('data'));
        $I->assertEquals($impersonatedId, $I->grabDataFromJsonResponse('profile.UserID'));
    }

    public function getTestIOSTransaction()
    {
        return include $this->fakeDataPath . '/fakeIOSTransaction.php';
    }

    public function removePincode($pinCode)
    {
        $this->sendPOST(self::getUrl('removePincode'), ['pincode' => $pinCode]);
    }

    public function setupPincode($pinCode)
    {
        $this->sendPUT(self::getUrl('removePincode'), ['pincode' => $pinCode]);
    }

    protected function handleXScripted()
    {
        $session = $this->grabService('session');
        $clientCheck = $session->get('client_check');
        $this->haveHttpHeader("X-Scripted", $clientCheck['result']);
    }
}
