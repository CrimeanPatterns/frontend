<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\OAuthController;

use AwardWallet\MainBundle\Service\EmailParsing\Client\Model\ConnectOAuthMailboxRequest;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Model\OAuthMailbox;
use Codeception\Example;
use Codeception\Util\Stub;

/**
 * @group frontend-functional
 */
class DeclinedAccessCest extends AbstractCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    /**
     * @dataProvider callbackDataProvider
     */
    public function testCallback(\TestSymfonyGuy $I, Example $example)
    {
        $this->callback($example);
    }

    protected function loginWithMailboxExample(): array
    {
        $random = bin2hex(random_bytes(10));

        return [
            'wantToTest' => 'login - with mailbox access',
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
                    'mailboxAccess' => true,
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
                static::getTokensHttpResponse(200, ['scope' => \Google_Service_Oauth2::USERINFO_PROFILE . ' ' . \Google_Service_Gmail::GMAIL_READONLY]),
                static::getGoogleUserInfoHttpResponse($random . '-login-exists-oauth', $random . '@login-exists-oauth.com'),
                static::getGooglePeopleAPIHttpResponse(),
            ],
            'expectedCode' => 302,
            'expectedRedirect' => '/account/select-provider',
            'expectedAfterRedirect' => 'window.providersData =',
            'expectedScannerCalls' => [
                'connectGoogleMailbox' => Stub::once(function (ConnectOAuthMailboxRequest $request) {
                    $this->I->assertInstanceOf(ConnectOAuthMailboxRequest::class, $request);
                    $this->I->assertEquals(static::ACCESS_TOKEN, $request->getAccessToken());
                    $this->I->assertEquals(static::REFRESH_TOKEN, $request->getRefreshToken());

                    return new OAuthMailbox(['id' => 12345]);
                }),
                'listMailboxes' => Stub::atLeastOnce(function () { return []; }),
            ],
        ];
    }

    protected function loginWithDeclinedAccessMailboxExample(): array
    {
        $random = bin2hex(random_bytes(10));

        return [
            'wantToTest' => 'login - mailbox access declined',
            'userWithEmail' => $random . '@no-match-login-exists-oauth.com',
            'userOAuthTokens' => [['Provider' => 'google', 'OAuthID' => $random . '-login-exists-oauth', 'DeclinedMailboxAccess' => 1]],
            'csrf' => self::CSRF,
            'routeParams' => [
                'type' => 'google',
                'state' => json_encode([
                    'type' => 'google',
                    'agentId' => null,
                    'userId' => self::USER_REPLACEMENT,
                    'action' => 'login',
                    'mailboxAccess' => true,
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
                static::getTokensHttpResponse(200, ['scope' => \Google_Service_Oauth2::USERINFO_PROFILE . ' ' . \Google_Service_Gmail::GMAIL_READONLY]),
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

    private function callbackDataProvider()
    {
        return [
            $this->loginWithMailboxExample(),
            $this->loginWithDeclinedAccessMailboxExample(),
        ];
    }
}
