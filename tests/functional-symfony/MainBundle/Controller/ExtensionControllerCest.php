<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller;

use AwardWallet\MainBundle\Service\LinkTargetHostResolver;
use Codeception\Example;
use Codeception\Stub;

/**
 * @coversDefaultClass \AwardWallet\MainBundle\Controller\ExtensionController
 * @group frontend-functional
 */
class ExtensionControllerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    /**
     * @dataProvider clickUrlDataProvider
     */
    public function testClickUrl(\TestSymfonyGuy $I, Example $example)
    {
        $I->mockService(LinkTargetHostResolver::class, Stub::makeEmpty(LinkTargetHostResolver::class));

        $login = "u" . bin2hex(random_bytes(8));
        $userId = $I->createAwUser($login, null, ['AccountLevel' => $example['accountLevel'], 'LinkAdsDisabled' => $example['linkAdsDisabled']]);
        $providerCode = "p" . bin2hex(random_bytes(8));
        $I->createAwProvider(null, $providerCode, [
            "ClickURL" => $example['clickUrl'],
        ]);
        $dir = $I->grabService("kernel")->getProjectDir() . "/engine/$providerCode";
        mkdir($dir);
        file_put_contents("$dir/extension.js",
            'var plugin = {
    // keepTabOpen: true,//todo
    // hideOnStart: true,
    clearCache: true,
    hosts: {
        "bestwestern.com": true,
        "book.bestwestern.com": true,
        "www.bestwestern.com": true,
        "www.bestwestern.co.uk": true
    },
    cashbackLink: \'\', // Dynamically filled by extension controller
}
');
        $I->amOnRoute("aw_extension_js", ["_switch_user" => $login, "providerCode" => $providerCode]);
        $I->see("cashbackLink: '{$example['expectedCashbackLink']}'");
    }

    private function clickUrlDataProvider(): array
    {
        return [
            [
                'accountLevel' => ACCOUNT_LEVEL_FREE,
                'linkAdsDisabled' => 0,
                'clickUrl' => null,
                'expectedCashbackLink' => '',
            ],
            [
                'accountLevel' => ACCOUNT_LEVEL_FREE,
                'linkAdsDisabled' => 0,
                'clickUrl' => "http://none.existent.domain.local/click-8125108-13909193?sid=AWREFCODE",
                'expectedCashbackLink' => "http://none.existent.domain.local/click-8125108-13909193?sid=AWREFCODE",
            ],
            [
                'accountLevel' => ACCOUNT_LEVEL_AWPLUS,
                'linkAdsDisabled' => 0,
                'clickUrl' => "http://none.existent.domain.local/click-8125108-13909193?sid=AWREFCODE",
                'expectedCashbackLink' => "http://none.existent.domain.local/click-8125108-13909193?sid=AWREFCODE",
            ],
            [
                'accountLevel' => ACCOUNT_LEVEL_AWPLUS,
                'linkAdsDisabled' => 1,
                'clickUrl' => "http://none.existent.domain.local/click-8125108-13909193?sid=AWREFCODE",
                'expectedCashbackLink' => "",
            ],
            [
                'accountLevel' => ACCOUNT_LEVEL_FREE,
                'linkAdsDisabled' => 1,
                'clickUrl' => "http://none.existent.domain.local/click-8125108-13909193?sid=AWREFCODE",
                'expectedCashbackLink' => "http://none.existent.domain.local/click-8125108-13909193?sid=AWREFCODE",
            ],
        ];
    }
}
