<?php

namespace AwardWallet\Tests\FunctionalSymfony\Mobile;

use AwardWallet\Common\PasswordCrypt\PasswordEncryptor;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use AwardWallet\MainBundle\Service\UserRemover;
use AwardWallet\Tests\FunctionalSymfony\_steps\Mobile\AccountSteps;
use AwardWallet\Tests\Modules\DbBuilder\Account;
use AwardWallet\Tests\Modules\DbBuilder\User;
use Codeception\Example;
use Codeception\Scenario;
use Google\Authenticator\GoogleAuthenticator;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\ExpectationFailedException;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @group mobile
 * @group security
 * @group frontend-functional
 */
class UserCest extends AbstractCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    protected const LOCKER_ERROR = 'locker_error';

    protected const ARG_WANT_TO_TEST = 'case';
    protected const ARG_LOCKER_SETTINGS = 'login_locker';
    protected const ARG_RESPONSE = 'response';
    protected const ARG_REQUEST = 'request';

    public function translatedLoginLogoutFailedLogin(\TestSymfonyGuy $I, Scenario $scenario)
    {
        $I->createAwUser(
            $login = 'logintest-' . StringHandler::getRandomCode(3),
            $password = 'password'
        );

        $I->haveHttpHeader('Accept-Language', 'ru');
        $this->userSteps->login($login, $password);
        $I->assertEquals('Кредитные карты', $I->grabDataFromJsonResponse('constants.providerKinds.0.Name'));
        $this->userSteps->logout();

        $this->userSteps->sendStatus();
        $this->userSteps->sendLoginForm($login, 'wrongpass');

        $I->seeResponseContainsJson([
            'success' => false,
            'message' => 'Неверное имя пользователя/ пароль', ]
        );
    }

    public function translatedFailedLogin(\TestSymfonyGuy $I, Scenario $scenario)
    {
        $I->createAwUser(
            $login = 'logintest-' . StringHandler::getRandomCode(10),
            $password = 'password'
        );

        $I->haveHttpHeader('Accept-Language', 'ru');
        $this->userSteps->sendStatus();
        $this->userSteps->sendLoginForm($login, 'wrongpass');

        $I->seeResponseContainsJson([
            'success' => false,
            'message' => 'Неверное имя пользователя/ пароль', ]
        );
    }

    public function recoverPassword(\TestSymfonyGuy $I, Scenario $scenario)
    {
        $userSteps = $this->userSteps;
        $userId = $I->createAwUser(
            $login = 'newuser-' . StringHandler::getRandomCode(10),
            $password = 'password',
            ['Email' => $email = $login . '@fakemail.com']
        );
        // do not send policy should be ignored
        $I->haveInDatabase('DoNotSend', ['Email' => $email]);

        $userSteps->login($login, $password);
        $userSteps->logout();

        $userSteps->sendStatus();

        $I->sendGET('/test/client-info');
        $I->resetLockout('forgot', $I->grabDataFromJsonResponse('client_ip'));

        $I->specify('invalid users',
            function ($invalidLogin) use ($userSteps) {
                $userSteps->sendRecoveryEmail($invalidLogin);
            },
            [
                'examples' => [
                    [' '],
                    ['    '],
                    [''],
                    ['0'],
                    ["' OR UserID=7"],
                ],
                'throws' => new ExpectationFailedException(''),
            ]
        );

        $userSteps->sendRecoveryEmail($login);

        $code = $I->grabFromDatabase('Usr', 'ResetPasswordCode', ['UserID' => $userId]);
        $I->assertNotEmpty($code);

        $invalidCodeSpecify = function (array $examples, \Throwable $fail) use ($I, $userId, $userSteps, $login, $email) {
            $I->specify(
                'invalid code',
                function ($code) use ($userId, $userSteps, $login, $email) {
                    $userSteps->changeRecoveryPassword('hack3r', [$userId, $login, $email], $code);
                },
                [
                    'examples' => $examples,
                    'throws' => ['fail', $fail],
                ]
            );
        };
        $invalidCodeSpecify(
            [
                [md5(null)],
                [md5(0)],
                [md5('')],
            ],
            new AssertionFailedError('')
        );
        $invalidCodeSpecify(
            [
                ["' OR 1=1"],
                ["' OR UserID=7"],
            ],
            new ExpectationFailedException('')
        );

        $errorsExtractor = function (\TestSymfonyGuy $I) {
            return $I->grabDataFromResponseByJsonPath('$.form.children..[?(@.name="pass")].children..[?(@.name="first)].errors')[0];
        };

        $userSteps->changeRecoveryPassword('', [$userId, $login, $email], $code, [[$errorsExtractor]]);
        $userSteps->changeRecoveryPassword('    ', [$userId, $login, $email], $code, [[$errorsExtractor]]);
        $userSteps->changeRecoveryPassword($password, [$userId, $login, $email], $code, [[$errorsExtractor]]); // check same old password

        $userSteps->changeRecoveryPassword($newPassword = 'shinynewpassword', [$userId, $login, $email], $code);

        $I->specify('code reuse after success change', function () use ($userId, $code, $userSteps, $login, $email) {
            $userSteps->changeRecoveryPassword($newPassword = 'shinynewpassword', [$userId, $login, $email], $code);
        },
            ['throws' => ['fail', new AssertionFailedError('')]]
        );

        $I->specify('use old password',
            function () use ($login, $password, $userSteps) {
                $userSteps->login($login, $password);
            },
            ['throws' => new ExpectationFailedException('')]
        );

        $userSteps->login($login, $newPassword);
    }

    public function logoutAndAccess(\TestSymfonyGuy $I, Scenario $scenario)
    {
        $I->sendGET($url = AccountSteps::getUrl('data'));
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(403);

        $userId = $this->createUserAndLogin($I);
        $I->sendGET($url);
        $I->seeResponseCodeIs(200);
        $I->seeHttpHeader('X-Aw-Userid', $userId);

        $this->userSteps->logout();
        $I->sendGET($url);
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(403);
        $I->saveCsrfToken();

        $this->userSteps->logout();
    }

    /**
     * @group locks
     */
    public function twoFactorAuthLogin(\TestSymfonyGuy $I, Scenario $scenario)
    {
        $userId = $I->createAwUser(
            $login = 'newuser-' . StringHandler::getRandomCode(10),
            $password = 'password',
            ['AccountLevel' => ACCOUNT_LEVEL_AWPLUS],
            true
        );

        /** @var GoogleAuthenticator $auth */
        [$secret, $_, $auth] = $I->createTwoFactorAuthCode($userId);

        $this->userSteps->sendStatus();
        $I->seeResponseContainsJson(['authorized' => false]);

        $this->userSteps->sendLoginForm($login, $password . 'invalid', '1');
        $I->seeResponseContainsJson(['success' => false, 'otcRequired' => false, 'message' => 'Invalid user name or password']);

        $this->userSteps->sendLoginForm($login, $password, '1');
        $I->seeResponseContainsJson(['success' => false, 'otcRequired' => true, 'message' => 'One-time code required']);

        $this->userSteps->sendLoginForm($login, $password, '1', '');
        $I->seeResponseContainsJson(['success' => false, 'otcRequired' => true, 'message' => 'One-time code required']);

        $this->userSteps->sendLoginForm($login, $password, '1', $lastCode = $auth->getCode($secret));
        $I->seeResponseContainsJson(['success' => true]);

        $this->userSteps->logout();
        $this->userSteps->sendStatus();
        $this->userSteps->sendLoginForm($login, $password, '1', $lastCode);
        $I->seeResponseContainsJson(['success' => false, 'otcRequired' => true, 'message' => 'The presented one-time code is invalid']);

        $I->executeQuery("UPDATE Usr SET GoogleAuthSecret = NULL, GoogleAuthRecoveryCode = NULL WHERE UserID = {$userId}");

        $this->userSteps->sendStatus();
        $I->seeResponseContainsJson(['authorized' => false]);
    }

    public function twoFactorAuthShouldNotDowngrade(\TestSymfonyGuy $I, Scenario $scenario)
    {
        $userId = $I->createAwUser(
            $login = 'newuser-' . StringHandler::getRandomCode(10),
            $password = 'password',
            ['AccountLevel' => ACCOUNT_LEVEL_FREE],
            true
        );

        /** @var GoogleAuthenticator $auth */
        [$secret, $code, $auth] = $I->createTwoFactorAuthCode($userId);

        $this->userSteps->sendStatus();
        $I->seeResponseContainsJson(['authorized' => false]);

        $this->userSteps->sendLoginForm($login, $password, '1', '0000000');
        $I->seeResponseContainsJson(['success' => false, 'otcRequired' => true, 'message' => 'The presented one-time code is invalid']);

        $this->userSteps->sendLoginForm($login, $password, '1', $lastCode = $auth->getCode($secret));
        $I->seeResponseContainsJson(['success' => true]);
        $I->dontSeeEmailTo($I->grabFromDatabase('Usr', 'Email', ['UserID' => $userId]), 'AwardWallet two-factor authentication removed');

        /** @var PasswordEncryptor $encryptor */
        $encryptor = $I->grabService(PasswordEncryptor::class);

        $I->seeInDatabase('Usr', [
            'UserID' => $userId,
            'GoogleAuthSecret' => $encryptor->encrypt($secret),
            'GoogleAuthRecoveryCode' => $encryptor->encrypt($code),
        ]);
    }

    public function developerImpersonation(\TestSymfonyGuy $I, Scenario $scenario)
    {
        [$userId, $accountId] = $this->createImpersonated($I);

        $staffUserId = $I->createAwUser(
            $staffLogin = 'staffuser-' . bin2hex(random_bytes(10)),
            $staffPassword = 'staffpass',
            [],
            true
        );
        $this->userSteps->login(
            $staffLogin,
            $staffPassword,
            null,
            (new GoogleAuthenticator())->getCode($I->grabFromDatabase('Usr', 'GoogleAuthSecret', ['UserID' => $staffUserId]))
        );

        $I->assertTrue($I->grabDataFromJsonResponse('profile.impersonate'));

        $I->sendPOST($this->userSteps::getUrl('impersonate'), ['loginOrEmail' => '-1000']);
        $I->seeResponseCodeIs(404);

        $I->sendPOST($this->userSteps::getUrl('impersonate'), ['loginOrEmail' => '-1000', 'fullImpersonate' => true]);
        $I->seeResponseCodeIs(404);

        $this->userSteps->impersonate($userId);

        $formData = $this->accountSteps->loadAccountForm($url = AccountSteps::getUrl('edit', $accountId));
        $formData['login'] = 'future.trip';
        $formData['pass'] = '123';
        $formData['notrelated'] = true;
        $I->sendPUT($url, $formData);
        $I->seeResponseCodeIs(403);

        $this->userSteps->logout();
        $this->userSteps->sendStatus(true);
        $this->accountSteps->loadData();

        $I->assertEquals($staffUserId, $I->grabDataFromJsonResponse('profile.UserID'));

        $this->userSteps->logout();
        $this->userSteps->sendStatus(false);
    }

    public function adminImpersonation(\TestSymfonyGuy $I, Scenario $scenario)
    {
        [$userId, $accountId] = $this->createImpersonated($I);

        // TODO: please remove SiteAdmin hardcode
        $I->executeQuery("update Usr set RegistrationIP = '" . $I->getClientIp() . "', LastLogonIP = '" . $I->getClientIp() . "' where Login = 'SiteAdmin'");
        $this->userSteps->login('SiteAdmin', 'Awdeveloper12');

        $I->assertTrue($I->grabDataFromJsonResponse('profile.impersonate'));
        $this->userSteps->impersonate($userId);

        $formData = $this->accountSteps->loadAccountForm($url = AccountSteps::getUrl('edit', $accountId));
        $formData['login'] = 'future.trip';
        $formData['pass'] = '122';
        $formData['notrelated'] = true;
        $I->sendPUT($url, $formData);
        $I->seeResponseCodeIs(403);

        $I->sendDELETE($url);
        $I->seeResponseCodeIs(403);
    }

    public function adminFullImpersonation(\TestSymfonyGuy $I, Scenario $scenario)
    {
        [$userId, $accountId] = $this->createImpersonated($I);

        // TODO: please remove SiteAdmin hardcode
        $I->executeQuery("update Usr set RegistrationIP = '" . $I->getClientIp() . "' where Login = 'SiteAdmin'");
        $this->userSteps->login('SiteAdmin', 'Awdeveloper12');

        $I->assertTrue($I->grabDataFromJsonResponse('profile.impersonate'));
        // make sure you have
        // SetEnvIf Remote_Addr "^192\.168\.10\.\d+$" whiteListedIp=1
        // in your /etc/apache2/sites-available/awardwalletcommon.conf
        // if you are testing in prod mode
        $this->userSteps->impersonate($userId, true);

        $formData = $this->accountSteps->loadAccountForm($url = AccountSteps::getUrl('edit', $accountId));
        $formData['login'] = 'future.trip';
        $formData['pass'] = '122';
        $formData['notrelated'] = true;
        $I->sendPUT($url, $formData);
        $I->grabDataFromJsonResponse('account');
        $I->seeResponseCodeIs(200);

        $I->sendDELETE($url);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['accountId' => $accountId]);
    }

    /**
     * @dataProvider deleteUserDataProvider
     */
    public function deleteUserInvalid(\TestSymfonyGuy $I, Example $test): void
    {
        $I->wantToTest($test[self::ARG_WANT_TO_TEST]);
        $I->createAwUser($userLogin = 'newuser-' . bin2hex(random_bytes(10)), $userPass = 'userpass', [], false);
        $userRemover = $I->prophesize(UserRemover::class);
        $userRemover
            ->deleteUser(Argument::cetera())
            ->shouldNotBeCalled();

        $I->switchToUser($userLogin);
        $I->saveCsrfToken();
        ($test[self::ARG_LOCKER_SETTINGS])($I, $userLogin);
        $I->mockService(UserRemover::class, $userRemover->reveal());
        $I->sendPOST('/m/api/user/delete', $test[self::ARG_REQUEST]);
        $I->seeResponseContainsJson($test[self::ARG_RESPONSE]);
    }

    public function deleteBusinessAdminError(\TestSymfonyGuy $I)
    {
        $userId = $I->createAwUser($userLogin = 'newuser-' . bin2hex(random_bytes(10)), 'userpass', [], false);
        $businessId = $I->createBusinessUserWithBookerInfo(null, ['Company' => $companyName = 'Some Company Name']);
        $I->connectUserWithBusiness($userId, $businessId, Useragent::ACCESS_ADMIN);

        $userRemover = $I->prophesize(UserRemover::class);
        $userRemover
            ->deleteUser(Argument::cetera())
            ->shouldNotBeCalled();
        $I->mockService(UserRemover::class, $userRemover->reveal());
        $I->switchToUser($userLogin);
        $I->saveCsrfToken();
        $I->sendPOST('/m/api/user/delete', [
            'password' => 'userpass',
            'reason' => 'somereason',
        ]);
        $I->seeResponseContainsJson([
            'success' => false,
            'unlinkFromBusiness' => true,
            'companyName' => $companyName,
        ]);
    }

    public function deleteUserSuccess(\TestSymfonyGuy $I)
    {
        $userId = $I->createAwUser($userLogin = 'newuser-' . bin2hex(random_bytes(10)), 'userpass', [], false);
        $I->switchToUser($userLogin);
        $I->saveCsrfToken();
        $I->sendPOST('/m/api/user/delete', [
            'password' => 'userpass',
            'reason' => 'somereason',
        ]);
        $I->seeResponseContainsJson([
            'success' => true,
        ]);
        $I->dontSeeInDatabase('Usr', ['UserID' => $userId]);
    }

    public function captchaHeader(\TestSymfonyGuy $I)
    {
        $userId = $I->createAwUser($userLogin = 'newuser-' . bin2hex(random_bytes(10)), 'userpass', [], false);
        $I->switchToUser($userLogin);
        $I->saveCsrfToken();
        $I->sendPOST('/m/api/user/delete', [
            'password' => 'userpass',
            'reason' => 'somereason',
        ]);
        $I->seeResponseContainsJson([
            'success' => true,
        ]);
        $I->dontSeeInDatabase('Usr', ['UserID' => $userId]);
    }

    protected function createImpersonated(\TestSymfonyGuy $I)
    {
        $providerId = $I->createAwProvider();
        $accountId = $I->makeAccount(new Account(
            $user = new User('newuser-' . bin2hex(random_bytes(10)), false),
            null,
            [],
            ['ProviderID' => $providerId]
        ));

        return [$user->getId(), $accountId];
    }

    protected function deleteUserDataProvider()
    {
        $validRequest = [
            'password' => 'userpass',
            'reason' => 'somereason',
        ];

        return [
            [
                self::ARG_WANT_TO_TEST => 'login locked',
                self::ARG_LOCKER_SETTINGS => function (\TestSymfonyGuy $I, string $login) {
                    $this->mockRestrictingLoginLocker($I, $login);
                },
                self::ARG_REQUEST => $validRequest,
                self::ARG_RESPONSE => [
                    'success' => false,
                    'error' => self::LOCKER_ERROR,
                ],
            ],
            [
                self::ARG_WANT_TO_TEST => 'password locked',
                self::ARG_LOCKER_SETTINGS => function (\TestSymfonyGuy $I, string $login) {
                    $this->mockAllowingLoginLocker($I, $login);
                    $this->mockRestrictingPasswordLocker($I, $login);
                },
                self::ARG_REQUEST => $validRequest,
                self::ARG_RESPONSE => [
                    'success' => false,
                    'passwordError' => self::LOCKER_ERROR,
                ],
            ],
            [
                self::ARG_WANT_TO_TEST => 'empty password',
                self::ARG_LOCKER_SETTINGS => function (\TestSymfonyGuy $I, string $login) {
                    $this->mockAllowingLoginLocker($I, $login);
                },
                self::ARG_REQUEST => [
                    'reason' => 'somereason',
                    'password' => '',
                ],
                self::ARG_RESPONSE => [
                    'success' => false,
                    'passwordError' => 'This value should not be blank.',
                ],
            ],
            [
                self::ARG_WANT_TO_TEST => 'empty reason',
                self::ARG_LOCKER_SETTINGS => function (\TestSymfonyGuy $I, string $login) {
                    /** @var AntiBruteforceLockerService $prophecy */
                    $prophecy = $I->prophesize(AntiBruteforceLockerService::class);
                    $prophecy
                        ->checkForLockout($login)
                        ->willReturn(null)
                        ->shouldBeCalledTimes(2);
                    $prophecy
                        ->unlock($login)
                        ->shouldBeCalled();
                    $I->mockService('aw.security.antibruteforce.login', $prophecy->reveal());
                    $this->mockAllowingPasswordLocker($I, $login);
                },
                self::ARG_REQUEST => [
                    'reason' => '',
                    'password' => 'userpass',
                ],
                self::ARG_RESPONSE => [
                    'success' => false,
                    'reasonError' => 'This value should not be blank.',
                ],
            ],
            [
                self::ARG_WANT_TO_TEST => 'invalid password',
                self::ARG_LOCKER_SETTINGS => function (\TestSymfonyGuy $I, string $login) {
                    /** @var AntiBruteforceLockerService $prophecy */
                    $prophecy = $I->prophesize(AntiBruteforceLockerService::class);
                    $prophecy
                        ->checkForLockout($login)
                        ->willReturn(null)
                        ->shouldBeCalledTimes(2);

                    $I->mockService('aw.security.antibruteforce.login', $prophecy->reveal());
                    $this->mockAllowingPasswordLocker($I, $login);
                },
                self::ARG_REQUEST => [
                    'reason' => 'somereason',
                    'password' => 'userpass_invalid',
                ],
                self::ARG_RESPONSE => [
                    'success' => false,
                    'passwordError' => 'Invalid password',
                ],
            ],
        ];
    }

    protected function mockRestrictingLoginLocker(\TestSymfonyGuy $I, string $key): void
    {
        $I->mockService('aw.security.antibruteforce.login', $this->createAntibruteforceMock($I, $key, self::LOCKER_ERROR)->reveal());
    }

    protected function mockAllowingLoginLocker(\TestSymfonyGuy $I, string $key): void
    {
        $I->mockService('aw.security.antibruteforce.login', $this->createAntibruteforceMock($I, $key, null)->reveal());
    }

    protected function mockRestrictingPasswordLocker(\TestSymfonyGuy $I, string $key): void
    {
        $I->mockService('aw.security.antibruteforce.password', $this->createAntibruteforceMock($I, $key, self::LOCKER_ERROR)->reveal());
    }

    protected function mockAllowingPasswordLocker(\TestSymfonyGuy $I, string $key): void
    {
        $I->mockService('aw.security.antibruteforce.password', $this->createAntibruteforceMock($I, $key, null)->reveal());
    }

    protected function createAntibruteforceMock(\TestSymfonyGuy $I, string $key, ?string $error): ObjectProphecy
    {
        /** @var AntiBruteforceLockerService $prophecy */
        $prophecy = $I->prophesize(AntiBruteforceLockerService::class);
        $prophecy
            ->checkForLockout($key)
            ->willReturn($error)
            ->shouldBeCalledOnce();

        return $prophecy;
    }
}
