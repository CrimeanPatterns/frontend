<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\Account;

use AwardWallet\MainBundle\Loyalty\CurlSender;
use AwardWallet\MainBundle\Loyalty\CurlSenderResult;
use AwardWallet\Tests\Modules\AutoVerifyMocksTrait;
use Codeception\Example;
use Codeception\Stub;

/**
 * @group frontend-functional
 * @coversDefaultClass \AwardWallet\MainBundle\Controller\Account\AutologinV3Controller
 */
class AutologinV3ControllerCest
{
    use AutoVerifyMocksTrait;

    /**
     * @dataProvider testAffiliateLinkDataProvider
     */
    public function testAffiliateLink(\TestSymfonyGuy $I, Example $example)
    {
        $curlSender = Stub::makeEmpty(CurlSender::class, [
            'call' => Stub\Expected::once(function ($method, $jsonData = null, $isAwDefaultUser = true, $timeout = CurlSender::TIMEOUT) use ($I, $example) {
                $result = new CurlSenderResult();
                $result->setCode(200);
                $result->setResponse('{"browserExtensionSessionId":"sess1", "browserExtensionConnectionToken":"tok123"}');
                $json = json_decode($jsonData, true);
                $I->assertEquals($example['ExpectedAffiliateLinksAllowed'], $json['affiliateLinksAllowed']);

                return $result;
            }),
        ]);
        $I->mockService(CurlSender::class, $curlSender);

        $login = "l" . bin2hex(random_bytes(8));
        $userId = $I->createAwUser($login, null, [
            'AccountLevel' => $example['AccountLevel'],
            'LinkAdsDisabled' => $example['LinkAdsDisabled'],
        ]);
        $providerId = $I->createAwProvider(null, null, ['AutologinV3' => 1, 'Autologin' => AUTOLOGIN_EXTENSION]);
        $accountId = $I->createAwAccount($userId, $providerId, "login");
        $I->amOnPage("/page/about?_switch_user=" . $login);
        $I->saveCsrfToken();
        $I->sendAjaxPostRequest("/account/account/get-autologin-connection/{$accountId}");
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(["browserExtensionSessionId" => "sess1"]);
    }

    private function testAffiliateLinkDataProvider(): array
    {
        return [
            [
                'AccountLevel' => ACCOUNT_LEVEL_FREE,
                'LinkAdsDisabled' => 0,
                'ExpectedAffiliateLinksAllowed' => true,
            ],
            [
                'AccountLevel' => ACCOUNT_LEVEL_FREE,
                'LinkAdsDisabled' => 1,
                'ExpectedAffiliateLinksAllowed' => true,
            ],
            [
                'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
                'LinkAdsDisabled' => 0,
                'ExpectedAffiliateLinksAllowed' => true,
            ],
            [
                'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
                'LinkAdsDisabled' => 1,
                'ExpectedAffiliateLinksAllowed' => false,
            ],
        ];
    }
}
