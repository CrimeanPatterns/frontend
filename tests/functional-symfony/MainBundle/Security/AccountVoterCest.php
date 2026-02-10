<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Security\Voter;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Manager\UserManager;
use Codeception\Example;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @group frontend-functional
 */
class AccountVoterCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    /**
     * @dataProvider usePasswordInExtensionDataProvider
     */
    public function testUsePasswordInExtension(\TestSymfonyGuy $I, Example $example)
    {
        $userId = $I->createAwUser();

        $providerId = $I->createAwProvider(
            null,
            null,
            ["Autologin" => $example["Autologin"], "CheckInBrowser" => $example["CheckInBrowser"]]
        );
        $accountId = $I->createAwAccount($userId, $providerId, "some");

        /** @var EntityManagerInterface $em */
        $em = $I->grabService("doctrine.orm.default_entity_manager");
        $account = $em->find(Account::class, $accountId);
        $user = $em->find(Usr::class, $userId);

        /** @var UserManager $um */
        $um = $I->grabService("aw.manager.user_manager");
        $um->loadToken($user, false);

        /** @var AuthorizationCheckerInterface $authChecker */
        $authChecker = $I->grabService("security.authorization_checker");

        /** @var RequestStack $requestStack */
        $requestStack = $I->grabService("request_stack");
        $requestStack->push(new Request());

        $I->assertEquals($example["ExpectedResult"], $authChecker->isGranted('USE_PASSWORD_IN_EXTENSION', $account));
    }

    /**
     * @dataProvider updateDataProvider
     */
    public function testUpdate(\TestSymfonyGuy $I, Example $example)
    {
        $userId = $I->createAwUser();

        $providerId = $I->createAwProvider(
            null,
            null,
            $example['ProviderFields'],
        );
        $accountId = $I->createAwAccount($userId, $providerId, "some");

        /** @var EntityManagerInterface $em */
        $em = $I->grabService("doctrine.orm.default_entity_manager");
        $account = $em->find(Account::class, $accountId);
        $user = $em->find(Usr::class, $userId);

        /** @var UserManager $um */
        $um = $I->grabService("aw.manager.user_manager");
        $um->loadToken($user, false);

        /** @var AuthorizationCheckerInterface $authChecker */
        $authChecker = $I->grabService("security.authorization_checker");

        /** @var RequestStack $requestStack */
        $requestStack = $I->grabService("request_stack");
        $requestStack->push(new Request([], [], [], $example['Cookies'] ?? []));

        $I->assertEquals($example["ExpectedResult"], $authChecker->isGranted('UPDATE', $account));
    }

    private function usePasswordInExtensionDataProvider()
    {
        return [
            ['Autologin' => AUTOLOGIN_SERVER, 'CheckInBrowser' => CHECK_IN_SERVER, 'ExpectedResult' => false],
            ['Autologin' => AUTOLOGIN_MIXED, 'CheckInBrowser' => CHECK_IN_SERVER, 'ExpectedResult' => true],
            ['Autologin' => AUTOLOGIN_EXTENSION, 'CheckInBrowser' => CHECK_IN_SERVER, 'ExpectedResult' => true],
            ['Autologin' => AUTOLOGIN_SERVER, 'CheckInBrowser' => CHECK_IN_CLIENT, 'ExpectedResult' => true],
            ['Autologin' => AUTOLOGIN_SERVER, 'CheckInBrowser' => CHECK_IN_MIXED, 'ExpectedResult' => true],
        ];
    }

    private function updateDataProvider()
    {
        return [
            [
                'ProviderFields' => [
                    "State" => PROVIDER_CHECKING_EXTENSION_ONLY,
                    "CheckInBrowser" => 1,
                ],
                'Cookies' => ['DBE' => 1],
                'ExpectedResult' => true,
            ],
            [
                'ProviderFields' => [
                    "State" => PROVIDER_CHECKING_EXTENSION_ONLY,
                    "CheckInBrowser" => 1,
                ],
                'Cookies' => ['DBE' => 1],
                'ForceV3Group' => true,
                'ExpectedResult' => true,
            ],
        ];
    }
}
