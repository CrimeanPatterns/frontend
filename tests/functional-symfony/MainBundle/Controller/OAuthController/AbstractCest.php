<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\OAuthController;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Scanner\WarningGenerator;
use AwardWallet\MainBundle\Security\OAuth\StateFactory;
use AwardWallet\MainBundle\Service\EmailParsing\EmailScannerApiStub;
use Codeception\Example;
use Codeception\Util\Stub;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

abstract class AbstractCest
{
    protected const USER_REPLACEMENT = 223322;
    protected const USER_PASS = 'xxx';
    protected const CSRF = 'blah';
    protected const ACCESS_TOKEN = 'some_acc_token';
    protected const REFRESH_TOKEN = 'some_refresh_token';

    /**
     * @var \TestSymfonyGuy
     */
    protected $I;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->I = $I;
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $this->I = null;
    }

    protected function start(Example $example)
    {
        $I = $this->I;
        $I->wantToTest($example['wantToTest']);
        $I->followRedirects(false);
        $I->amOnRoute('aw_usermailbox_oauth', $example['routeParams']);
        $I->seeResponseCodeIs($example['expectedCode']);

        if (isset($example['expectedRedirect'])) {
            $I->seeRedirectToHost($example['expectedRedirect']);
        }

        foreach ($example['expectedScopes'] ?? [] as $scope) {
            $I->seeRedirectContains($scope);
        }

        foreach ($example['notExpectedScopes'] ?? [] as $scope) {
            $I->dontSeeRedirectContains($scope);
        }
    }

    protected function callback(Example $example): Usr
    {
        $I = $this->I;
        $I->wantToTest($example['wantToTest']);
        $I->followRedirects(false);
        $routeParams = $example['routeParams'];
        $login = "usr" . bin2hex(random_bytes(4));
        $password = ($example['userWithPassword'] ?? false) ? self::USER_PASS : null;

        if ($example['userId'] ?? false) {
            $userId = $example['userId'];
        } elseif ($example['userWithEmail'] ?? false) {
            $userId = $I->createAwUser($login, $password, ['Email' => $example['userWithEmail']]);
        } else {
            $userId = $I->createAwUser($login, $password);
        }

        if (isset($example['userOAuthTokens'])) {
            foreach ($example['userOAuthTokens'] as $OAuthToken) {
                $I->haveInDatabase("UserOAuth", array_merge($OAuthToken, [
                    "UserID" => $userId,
                    "Email" => $I->grabRandomString(10) . '@mail.com',
                    "FirstName" => 'John',
                    "LastName" => 'Bill',
                ]));
            }
        }

        if ($example['authorized'] ?? false) {
            if (isset($routeParams['state'])) {
                $routeParams['state'] = str_replace(static::USER_REPLACEMENT, $userId, $routeParams['state']);
            }
            $I->amOnRoute('aw_test_client_info', ['_switch_user' => $login]);
        }

        if ($example['csrf'] ?? false) {
            $I->persistService('session');
            $I->grabService('session')->set('oauth_csrf', static::CSRF);
        }

        if (!empty($example['http-responses'])) {
            $responses = $example['http-responses'];
            $I->mockService("aw.curl_driver", $I->stubMakeEmpty(\HttpDriverInterface::class, [
                'request' => Stub::atLeastOnce(function () use (&$responses, $I) {
                    $I->assertNotEmpty($responses);

                    return array_shift($responses);
                }),
            ]));
        }
        $scannerCalls = $example['expectedScannerCalls'] ?? null;

        if ($scannerCalls) {
            $I->mockService(EmailScannerApiStub::class, $I->stubMakeEmpty(EmailScannerApiStub::class, $scannerCalls));
        }
        $I->mockService(WarningGenerator::class, $I->stubMakeEmpty(WarningGenerator::class));

        if ($example['businessDomain'] ?? false) {
            $I->amOnBusiness();
        }

        if (substr($example['routeParams']['state'] ?? '', 0, 1) === '{') {
            /** @var SessionInterface $session */
            $session = $I->grabService("session");
            $state = json_decode($example['routeParams']['state'], true);
            /** @var StateFactory $stateFactory */
            $stateFactory = $I->grabService(StateFactory::class);
            $request = Request::create('http://' . ($state['host'] ?? 'xyz') . '/blah');
            $request->setSession($session);

            if ($state['query'] ?? null) {
                $request->query->add($state['query']);
            }

            $stateCode = $stateFactory->createState($state['type'] ?? '', $state['agentId'] ?? null, $state['mailboxAccess'] ?? false, $state['profileAccess'] ?? false, $state['action'], $request);
            $routeParams['state'] = $stateCode;

            if (isset($state['csrf']) && $state['csrf'] === 'bad') {
                $I->persistService('session');
                $I->grabService('session')->set('oauth_csrf', 'will not match');
            }
        }

        $I->amOnRoute('aw_usermailbox_oauthcallback', $routeParams);
        $I->seeResponseCodeIs($example['expectedCode']);

        if ($example['expectedRememberMeCookies'] ?? false) {
            $I->grabCookie('PwdHash');
            $I->assertGreaterOrEquals(300, \strlen($I->grabCookie('PwdHash')), 'PwdHash is suspiciously short');
        } else {
            $I->dontSeeCookie('PwdHash');
        }

        if (isset($example['expectedRedirect'])) {
            if (strpos($example['expectedRedirect'], '?') !== false) {
                $I->seeRedirectTo($example['expectedRedirect']);
            } else {
                $I->seeRedirectToPath($example['expectedRedirect']);
            }
        }

        if (isset($example['expectedAfterRedirect'])) {
            $I->amOnPage($I->grabHttpHeader('Location'));
            $I->seeInSource($example['expectedAfterRedirect']);
        }

        if (isset($example['expectedNewUserWith'])) {
            $I->seeInDatabase('Usr', $example['expectedNewUserWith']);

            if (isset($example['expectedNewUserWith']['Email'])) {
                $I->seeEmailTo(
                    $example['expectedNewUserWith']['Email'],
                    'Welcome to AwardWallet.com',
                    'Thank you for registering'
                );
            }
        }

        if (isset($example['seeInSource'])) {
            $I->seeInSource($example['seeInSource']);
        }
        $I->verifyMocks();

        return $I->grabService('doctrine')->getRepository(Usr::class)->find($userId);
    }

    protected function googleRegisterNoMailboxExample(): array
    {
        return [
            'wantToTest' => 'google register no mailbox',
            'routeParams' => [
                'type' => 'google',
                'action' => 'register',
            ],
            'expectedCode' => 302,
            'expectedRedirect' => 'accounts.google.com',
            'expectedScopes' => ['userinfo.email', 'userinfo.profile'],
            'notExpectedScopes' => ['gmail.readonly'],
        ];
    }

    protected function googleLoginNoMailboxExample(): array
    {
        return [
            'wantToTest' => 'google login no mailbox',
            'routeParams' => [
                'type' => 'google',
                'action' => 'login',
            ],
            'expectedCode' => 302,
            'expectedRedirect' => 'accounts.google.com',
            'expectedScopes' => ['userinfo.email', 'userinfo.profile'],
            'notExpectedScopes' => ['gmail.readonly'],
        ];
    }

    protected function registerNewUserExample(): array
    {
        $random = bin2hex(random_bytes(10));

        return [
            'wantToTest' => 'register - new user',
            'csrf' => true,
            'routeParams' => [
                'type' => 'google',
                'state' => json_encode([
                    'type' => 'google',
                    'agentId' => null,
                    'action' => 'register',
                    'mailboxAccess' => false,
                    'profileAccess' => true,
                    'csrf' => self::CSRF,
                    'rememberMe' => true,
                    'platform' => 'desktop',
                    'host' => getSymfonyContainer()->getParameter('host'),
                    'query' => [],
                ]),
                'code' => 'somegooglecode',
            ],
            'http-responses' => [
                static::getTokensHttpResponse(200, ['scope' => \Google_Service_Oauth2::USERINFO_PROFILE]),
                static::getGoogleUserInfoHttpResponse($random . '-reg-new-id', $random . '@reg-new.com'),
                static::getGooglePeopleAPIHttpResponse(),
            ],
            'expectedCode' => 200,
            // 'expectedRedirect' => '/account/select-provider',
            // 'expectedAfterRedirect' => 'window.providersData =',
            'expectedScannerCalls' => [
                'connectGoogleMailbox' => Stub::never(),
                'listMailboxes' => Stub::never(),
            ],
            'expectedOAuthRecords' => [['Type' => 'google', 'OAuthID' => $random . '-reg-new-id']],
            'expectedNewUserWith' => [
                'Email' => $random . '@reg-new.com',
                'Pass' => null,
            ],
            'expectedRememberMeCookies' => true,
        ];
    }

    protected function registerExistingExample(bool $mailboxAccess): array
    {
        $random = bin2hex(random_bytes(10));
        $scopes = [\Google_Service_Oauth2::USERINFO_PROFILE];

        if ($mailboxAccess) {
            $scopes[] = \Google_Service_Gmail::GMAIL_READONLY;
        }

        return [
            'wantToTest' => 'register - existing user',
            'userWithEmail' => $random . '@reg-exists.com',
            'userWithPassword' => true,
            'csrf' => self::CSRF,
            'routeParams' => [
                'type' => 'google',
                'state' => json_encode([
                    'type' => 'google',
                    'agentId' => null,
                    'userId' => self::USER_REPLACEMENT,
                    'action' => 'register',
                    'mailboxAccess' => $mailboxAccess,
                    'profileAccess' => true,
                    'csrf' => self::CSRF,
                    'rememberMe' => true,
                    'platform' => 'desktop',
                    'host' => getSymfonyContainer()->getParameter('host'),
                    'query' => [],
                ]),
                'code' => 'somegooglecode',
            ],
            'http-responses' => [
                static::getTokensHttpResponse(200, ['scope' => implode(' ', $scopes)]),
                static::getGoogleUserInfoHttpResponse($random . '-reg-exists-id', $random . '@reg-exists.com'),
                static::getGooglePeopleAPIHttpResponse(),
            ],
            'expectedCode' => 302,
            'expectedRedirect' => '/login?error=A%20user%20with%20this%20email%20address%20%28' . $random . '@reg-exists.com%29%20is%20already%20registered;%20please%20enter%20your%20password%20to%20log%20in.',
            'expectedScannerCalls' => [
                'connectGoogleMailbox' => Stub::never(),
                'listMailboxes' => Stub::never(),
            ],
        ];
    }

    protected function loginExistingExample(): array
    {
        $random = bin2hex(random_bytes(10));

        return [
            'wantToTest' => 'login - existing user',
            'userWithEmail' => $random . '@login-exists.com',
            'userWithPassword' => true,
            'csrf' => self::CSRF,
            'routeParams' => [
                'type' => 'google',
                'state' => json_encode([
                    'type' => 'google',
                    'agentId' => null,
                    'userId' => self::USER_REPLACEMENT,
                    'action' => 'login',
                    'mailboxAccess' => false,
                    'profileAccess' => true,
                    'csrf' => self::CSRF,
                    'query' => ['a' => 'b'],
                    'rememberMe' => true,
                    'platform' => 'desktop',
                    'host' => getSymfonyContainer()->getParameter('host'),
                ]),
                'code' => 'somegooglecode',
            ],
            'http-responses' => [
                static::getTokensHttpResponse(200, ['scope' => \Google_Service_Oauth2::USERINFO_PROFILE]),
                static::getGoogleUserInfoHttpResponse($random . '-google-code', $random . '@login-exists.com'),
                static::getGooglePeopleAPIHttpResponse(),
            ],
            'expectedCode' => 302,
            'expectedRedirect' => '/login?error=A%20user%20with%20this%20email%20address%20%28' . $random . '@login-exists.com%29%20is%20already%20registered;%20please%20enter%20your%20password%20to%20log%20in.&a=b',
            'expectedScannerCalls' => [
                'connectGoogleMailbox' => Stub::never(),
                'listMailboxes' => Stub::never(),
            ],
        ];
    }

    protected function loginExistingOAuthExample()
    {
        $random = bin2hex(random_bytes(10));

        return [
            'wantToTest' => 'login - existing oauth',
            'userWithEmail' => $random . '@no-match-login-exists-oauth.com',
            'userOAuthTokens' => [['Provider' => 'google', 'OAuthID' => $random . '-login-exists-oauth']],
            'csrf' => self::CSRF,
            'routeParams' => [
                'type' => 'google',
                'state' => json_encode([
                    'type' => 'google',
                    'agentId' => null,
                    'userId' => self::USER_REPLACEMENT,
                    'action' => 'login',
                    'mailboxAccess' => false,
                    'profileAccess' => true,
                    'csrf' => self::CSRF,
                    'rememberMe' => true,
                    'platform' => 'desktop',
                    'host' => getSymfonyContainer()->getParameter('host'),
                    'query' => [],
                ]),
                'code' => 'somegooglecode',
            ],
            'http-responses' => [
                static::getTokensHttpResponse(200, ['scope' => \Google_Service_Oauth2::USERINFO_PROFILE]),
                static::getGoogleUserInfoHttpResponse($random . '-login-exists-oauth', $random . '@login-exists-oauth.com'),
                static::getGooglePeopleAPIHttpResponse(),
            ],
            'expectedCode' => 302,
            'expectedRedirect' => '/account/select-provider',
            'expectedAfterRedirect' => 'window.providersData =',
            'expectedScannerCalls' => [
                'connectGoogleMailbox' => Stub::never(),
                'listMailboxes' => Stub::never(),
            ],
        ];
    }

    protected static function getTokensHttpResponse(int $code = 200, array $extra = []): \HttpDriverResponse
    {
        return new \HttpDriverResponse(
            json_encode(array_merge([
                'access_token' => self::ACCESS_TOKEN,
                'refresh_token' => self::REFRESH_TOKEN,
                // to prevent double redirect to google for missing mailbox access
                'scope' => 'https://www.googleapis.com/auth/gmail.readonly',
            ], $extra)),
            $code
        );
    }

    protected static function getGoogleUserInfoHttpResponse(
        string $sub,
        string $email,
        string $givenName = 'John',
        string $familyName = 'Smith',
        int $code = 200
    ): \HttpDriverResponse {
        return new \HttpDriverResponse(json_encode([
            'sub' => $sub,
            'email' => $email,
            'given_name' => $givenName,
            'family_name' => $familyName,
        ]), $code);
    }

    protected static function getGmailProfileHttpResponse(
        string $email
    ): \HttpDriverResponse {
        return new \HttpDriverResponse(json_encode([
            'emailAddress' => $email,
        ]), 200);
    }

    protected static function getGooglePeopleAPIHttpResponse(int $code = 200): \HttpDriverResponse
    {
        return new \HttpDriverResponse(
            json_encode([
                'resourceName' => 'people/12345',
                'etag' => '%EgQBAzcuGgQBAgUHIgwrekRSbXVpL3A3VT0=',
                'photos' => [
                    [
                        'metadata' => [
                            'primary' => true,
                            'source' => [
                                'type' => 'PROFILE',
                                'id' => '12345',
                            ],
                        ],
                        'url' => 'https://lh3.googleusercontent.com/photo.jpg',
                    ],
                ],
            ]), $code
        );
    }
}
