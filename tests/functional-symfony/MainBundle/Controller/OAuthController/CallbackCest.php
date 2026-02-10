<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\OAuthController;

use AwardWallet\MainBundle\Service\EmailParsing\Client\Model\ConnectOAuthMailboxRequest;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Model\GoogleMailbox;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Model\OAuthMailbox;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Model\UpdateOAuthMailboxRequest;
use AwardWallet\MainBundle\Service\EmailParsing\Client\ObjectSerializer;
use Codeception\Example;
use Codeception\Util\Stub;

/**
 * @group frontend-functional
 */
class CallbackCest extends AbstractCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    /**
     * @dataProvider callbackDataProvider
     */
    public function testCallback(\TestSymfonyGuy $I, Example $example)
    {
        $this->callback($example);
    }

    private function callbackDataProvider(): array
    {
        $random = bin2hex(random_bytes(10));

        return [
            [
                'wantToTest' => 'callback for mailbox',
                'authorized' => true,
                'csrf' => true,
                'routeParams' => [
                    'type' => 'google',
                    'state' => json_encode([
                        'type' => 'google',
                        'agentId' => null,
                        'userId' => static::USER_REPLACEMENT,
                        'action' => 'mailbox',
                        'mailboxAccess' => true,
                        'profileAccess' => false,
                        'csrf' => static::CSRF,
                        'rememberMe' => false,
                        'platform' => 'desktop',
                        'host' => getSymfonyContainer()->getParameter('host'),
                        'query' => [],
                    ]),
                    'code' => 'somegooglecode',
                ],
                'http-responses' => [
                    static::getTokensHttpResponse(200, ['scope' => \Google_Service_Gmail::GMAIL_READONLY]),
                    static::getGmailProfileHttpResponse($random . '@mailbox.com'),
                    static::getGooglePeopleAPIHttpResponse(),
                ],
                'expectedCode' => 302,
                'expectedRedirect' => '/mailboxes/',
                'expectedScannerCalls' => [
                    'connectGoogleMailbox' => Stub::once(function (ConnectOAuthMailboxRequest $request) {
                        $this->I->assertInstanceOf(ConnectOAuthMailboxRequest::class, $request);
                        $this->I->assertEquals(static::ACCESS_TOKEN, $request->getAccessToken());
                        $this->I->assertEquals(static::REFRESH_TOKEN, $request->getRefreshToken());

                        return new OAuthMailbox(['id' => 12345]);
                    }),
                    'listMailboxes' => Stub::atLeastOnce(function () { return []; }),
                ],
            ],
            $this->registerNewUserExample(),
            $this->registerExistingExample(false),
            [
                'wantToTest' => 'register - update mailbox',
                'csrf' => static::CSRF,
                'userWithEmail' => $random . '@no-match-reg-update-oauth.com',
                'userOAuthTokens' => [['Provider' => 'google', 'OAuthID' => $random . '-reg-update-id']],
                'routeParams' => [
                    'type' => 'google',
                    'state' => json_encode([
                        'type' => 'google',
                        'agentId' => null,
                        'userId' => static::USER_REPLACEMENT,
                        'action' => 'register',
                        'mailboxAccess' => true,
                        'profileAccess' => true,
                        'csrf' => static::CSRF,
                        'rememberMe' => false,
                        'platform' => 'desktop',
                        'host' => getSymfonyContainer()->getParameter('host'),
                        'query' => [],
                    ]),
                    'code' => 'somegooglecode',
                ],
                'http-responses' => [
                    static::getTokensHttpResponse(200, ['scope' => \Google_Service_Gmail::GMAIL_READONLY . ' ' . \Google_Service_Oauth2::USERINFO_PROFILE]),
                    static::getGoogleUserInfoHttpResponse($random . '-reg-update-id', $random . '@reg-update.com'),
                    static::getGooglePeopleAPIHttpResponse(),
                ],
                'expectedCode' => 302,
                'expectedRedirect' => '/account/select-provider',
                'expectedAfterRedirect' => 'window.providersData =',
                'expectedScannerCalls' => [
                    'updateOAuthMailbox' => Stub::once(function (UpdateOAuthMailboxRequest $request) {
                        $this->I->assertEquals(static::ACCESS_TOKEN, $request->getAccessToken());
                        $this->I->assertEquals(static::REFRESH_TOKEN, $request->getRefreshToken());

                        return new OAuthMailbox(['id' => 12345]);
                    }),
                    'listMailboxes' => Stub::atLeastOnce(function () use ($random) {
                        return [
                            ObjectSerializer::deserialize((object) ['id' => 12345, "email" => $random . '@reg-update.com', "type" => "google", "userData" => "{}"], GoogleMailbox::class),
                        ];
                    }),
                ],
            ],
            [
                'wantToTest' => 'register - existing user with oauth',
                'userWithEmail' => $random . '@reg-exists-oauth.com',
                'userOAuthTokens' => [['Provider' => 'google', 'OAuthID' => $random . '-google-id']],
                'csrf' => static::CSRF,
                'routeParams' => [
                    'type' => 'google',
                    'state' => json_encode([
                        'type' => 'google',
                        'agentId' => null,
                        'userId' => static::USER_REPLACEMENT,
                        'action' => 'register',
                        'mailboxAccess' => false,
                        'profileAccess' => true,
                        'csrf' => static::CSRF,
                        'rememberMe' => true,
                        'platform' => 'desktop',
                        'host' => getSymfonyContainer()->getParameter('host'),
                        'query' => [],
                    ]),
                    'code' => 'somegooglecode',
                ],
                'http-responses' => [
                    static::getTokensHttpResponse(200, ['scope' => \Google_Service_Oauth2::USERINFO_PROFILE]),
                    static::getGoogleUserInfoHttpResponse($random . '-reg-exists-oauth', $random . '@reg-exist-oauth.com'),
                    static::getGooglePeopleAPIHttpResponse(),
                ],
                'expectedCode' => 200,
                'seeInSource' => "window.location.href = '/account/select-provider?new=1';",
                // 'expectedRedirect' => '/account/select-provider',
                // 'expectedAfterRedirect' => 'window.providersData =',
                'expectedScannerCalls' => [
                    'connectGoogleMailbox' => Stub::never(),
                    'listMailboxes' => Stub::never(),
                ],
                'expectedRememberMeCookies' => true,
            ],
            [
                'wantToTest' => 'register - already authorized',
                'authorized' => true,
                'csrf' => static::CSRF,
                'routeParams' => [
                    'type' => 'google',
                    'state' => json_encode([
                        'type' => 'google',
                        'agentId' => null,
                        'userId' => static::USER_REPLACEMENT,
                        'action' => 'register',
                        'mailboxAccess' => false,
                        'profileAccess' => true,
                        'rememberMe' => true,
                        'platform' => 'desktop',
                        'host' => getSymfonyContainer()->getParameter('host'),
                        'query' => [],
                    ]),
                    'code' => 'somegooglecode',
                ],
                'expectedCode' => 302,
                'expectedRedirect' => '/',
                'expectedScannerCalls' => [
                    'connectGoogleMailbox' => Stub::never(),
                    'listMailboxes' => Stub::never(),
                ],
            ],
            $this->loginExistingExample(),
            [
                'wantToTest' => 'login - new user',
                'csrf' => static::CSRF,
                'routeParams' => [
                    'type' => 'google',
                    'state' => json_encode([
                        'type' => 'google',
                        'agentId' => null,
                        'userId' => static::USER_REPLACEMENT,
                        'action' => 'login',
                        'mailboxAccess' => false,
                        'profileAccess' => true,
                        'csrf' => static::CSRF,
                        'rememberMe' => true,
                        'platform' => 'desktop',
                        'host' => getSymfonyContainer()->getParameter('host'),
                        'query' => [],
                    ]),
                    'code' => 'somegooglecode',
                ],
                'http-responses' => [
                    static::getTokensHttpResponse(200, ['scope' => \Google_Service_Oauth2::USERINFO_PROFILE]),
                    static::getGoogleUserInfoHttpResponse($random . '-login-new', $random . '@login-new.com'),
                    static::getGooglePeopleAPIHttpResponse(),
                ],
                'expectedCode' => 200,
                'seeInSource' => "window.location.href = '/account/select-provider?new=1';",
                // 'expectedRedirect' => '/account/select-provider',
                // 'expectedAfterRedirect' => 'window.providersData =',
                'expectedScannerCalls' => [
                    'connectGoogleMailbox' => Stub::never(),
                    'listMailboxes' => Stub::never(),
                ],
                'expectedNewUserWith' => [
                    'Email' => $random . '@login-new.com',
                    'Pass' => null,
                ],
                'expectedRememberMeCookies' => true,
            ],
            $this->loginExistingOAuthExample(),
            [
                'wantToTest' => 'register - expired csrf',
                'routeParams' => [
                    'type' => 'google',
                    'state' => json_encode([
                        'type' => 'google',
                        'agentId' => null,
                        'userId' => static::USER_REPLACEMENT,
                        'action' => 'register',
                        'mailboxAccess' => false,
                        'profileAccess' => true,
                        'rememberMe' => true,
                        'platform' => 'desktop',
                        'csrf' => 'bad',
                        'host' => getSymfonyContainer()->getParameter('host'),
                        'query' => [],
                    ]),
                    'code' => 'somegooglecode',
                ],
                'expectedCode' => 302,
                'expectedRedirect' => '/login?error=' . rawurlencode('Your session has expired. Please start again.'),
                'expectedScannerCalls' => [
                    'connectGoogleMailbox' => Stub::never(),
                    'listMailboxes' => Stub::never(),
                ],
            ],
            [
                'wantToTest' => 'bad action',
                'routeParams' => [
                    'type' => 'google',
                    'state' => json_encode(['action' => 'drive']),
                ],
                'expectedCode' => 302,
                'expectedRedirect' => '/',
            ],
            [
                'wantToTest' => 'callback for business',
                'authorized' => false,
                'businessDomain' => true,
                'csrf' => true,
                'routeParams' => [
                    'type' => 'google',
                    'state' => json_encode([
                        'type' => 'google',
                        'agentId' => null,
                        'userId' => static::USER_REPLACEMENT,
                        'action' => 'login',
                        'mailboxAccess' => false,
                        'profileAccess' => true,
                        'csrf' => static::CSRF,
                        'rememberMe' => true,
                        'platform' => 'desktop',
                        'host' => getSymfonyContainer()->getParameter('business_host'),
                        'query' => [],
                    ]),
                    'code' => 'somegooglecode',
                ],
                'http-responses' => [
                    static::getTokensHttpResponse(200, ['scope' => \Google_Service_Oauth2::USERINFO_PROFILE]),
                    static::getGoogleUserInfoHttpResponse($random . '-mailbox-id', $random . '@mailbox.com'),
                    static::getGooglePeopleAPIHttpResponse(),
                ],
                'expectedCode' => 302,
                'expectedRedirect' => '/login?error=' . rawurlencode('You are not an administrator of any business account'),
            ],
        ];
    }
}
