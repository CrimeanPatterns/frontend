<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Service\MobileExtensionHandler;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorage;
use AwardWallet\MainBundle\Service\MobileExtensionHandler\MobileExtensionHandler;
use Codeception\Example;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;

/**
 * @group mobile
 */
class MobileExtensionHandlerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    /**
     * @dataProvider clickUrlDataProvider
     */
    public function testClickUrl(\TestSymfonyGuy $I, Example $example)
    {
        $login = "u" . bin2hex(random_bytes(8));
        $userId = $I->createAwUser($login, null, ['AccountLevel' => $example['accountLevel'], 'LinkAdsDisabled' => $example['linkAdsDisabled']]);

        $providerCode = "p" . bin2hex(random_bytes(8));
        $I->createAwProvider(null, $providerCode, [
            "ClickURL" => $example['clickUrl'],
            "MobileAutoLogin" => MOBILE_AUTOLOGIN_DESKTOP_EXTENSION,
            "State" => PROVIDER_ENABLED,
            "AutoLogin" => AUTOLOGIN_EXTENSION,
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

        $accountId = $I->createAwAccount($userId, $providerCode, "some.login");

        /** @var Usr $user */
        $user = $I->grabService(EntityManagerInterface::class)->find(Usr::class, $userId);
        /** @var AwTokenStorage $tokenStorage */
        $tokenStorage = $I->grabService(AwTokenStorage::class);
        $tokenStorage->setToken(new PostAuthenticationGuardToken($user, 'secured_area', $user->getRoles()));

        /** @var RequestStack $requestStack */
        $requestStack = $I->grabService(RequestStack::class);
        $requestStack->push(new Request([], [], [], [], [], ['REMOTE_ADDR' => '1.2.3.4', 'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3']));

        /** @var MobileExtensionHandler $handler */
        $handler = $I->grabService(MobileExtensionHandler::class);
        [$source, $error] = $handler->loadExtensionForAccountById($accountId, MobileExtensionHandler::DESKTOP_TYPE);
        $I->assertNull($error);
        $cashbackLink = str_replace("AWREFCODE", $user->getRefcode() . "-m", $example['expectedCashbackLink']);
        $I->assertStringContainsString(json_encode($cashbackLink), $source);
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
