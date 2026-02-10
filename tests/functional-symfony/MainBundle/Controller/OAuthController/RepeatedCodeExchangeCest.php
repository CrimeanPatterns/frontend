<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\OAuthController;

use Codeception\Example;
use Codeception\Util\Stub;

/**
 * @group frontend-functional
 */
class RepeatedCodeExchangeCest extends AbstractCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function testRepeatedCodeExchange(\TestSymfonyGuy $I)
    {
        $login = "usr" . bin2hex(random_bytes(4));
        $userId = $I->createAwUser($login);
        $random = bin2hex(random_bytes(10));

        $data = [
            'wantToTest' => 'register - existing user with oauth',
            'userId' => $userId,
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
            "expectedRememberMeCookies" => true,
        ];

        $this->callback(new Example($data));

        $data['wantToTest'] = 'Authorization code should not be used twice';
        $I->resetCookie("MOCKSESSID");
        $I->resetCookie("PwdHash");

        unset($data['userOAuthTokens']);
        unset($data['seeInSource']);
        $data['http-responses'] = [];

        $data['expectedCode'] = 302;
        $data['expectedRedirect'] = '/login?error=' . rawurlencode('Your session has expired. Please start again.');
        $data['expectedRememberMeCookies'] = false;
        $this->callback(new Example($data));
    }
}
