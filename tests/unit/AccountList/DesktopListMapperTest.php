<?php

namespace AwardWallet\Tests\Unit;

use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\AccountList\Mapper\DesktopListMapper;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Manager\AccountListManager;
use Codeception\Module\Aw;

/**
 * @group frontend-unit
 */
class DesktopListMapperTest extends BaseUserTest
{
    public function testSubaccountsUpdate()
    {
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), Aw::TEST_PROVIDER_ID, "2.subaccounts");
        $manager = $this->container->get(AccountListManager::class);

        $this->aw->checkAccount($accountId);
        $accountData = $manager
            ->getAccountList(
                $this->container->get(OptionsFactory::class)->createDesktopListOptions(
                    (new Options())
                        ->set(Options::OPTION_USER, $this->container->get(AwTokenStorageInterface::class)->getBusinessUser())
                        ->set(Options::OPTION_ACCOUNT_IDS, [$accountId])
                )
            );
        $accountData = $accountData->getAccounts()[0];

        for ($i = 0; $i < 10; $i++) {
            $this->aw->checkAccount($accountId);

            $accountData = $manager
                ->getAccountList(
                    $this->container->get(OptionsFactory::class)->createDesktopListOptions(
                        (new Options())
                            ->set(Options::OPTION_USER, $this->container->get(AwTokenStorageInterface::class)->getBusinessUser())
                            ->set(Options::OPTION_ACCOUNT_IDS, [$accountId])
                    )
                );
            $accountData = $accountData->getAccounts()[0];
            $subAccounts = $accountData->SubAccountsArray;

            $this->assertCount(2, $subAccounts);

            if ($subAccounts[0]['StateBar']) {
                break;
            }
        }

        $this->assertEquals($accountData->StateBar, null);
        $this->assertEquals($accountData->BalanceRaw, 1);

        $this->assertContains($subAccounts[0]['StateBar'], ['inc', 'dec']);
        $this->assertFalse($subAccounts[0]['ChangeCount'] == "0");

        $this->assertContains($subAccounts[1]['StateBar'], ['inc', 'dec']);
        $this->assertFalse($subAccounts[1]['ChangeCount'] == "0");
    }

    public function testSubAccountFico(): void
    {
        $manager = $this->container->get(AccountListManager::class);

        $this->aw->createAwProvider(
            $code = 'someFICO' . StringUtils::getRandomCode(7),
            $code,
            ['ShortName' => 'Some short name'],
            [
                'Parse' => function () use (&$count) {
                    /** @var $this \TAccountChecker */
                    $this->SetBalance(100500);
                    $this->SetProperty('SubAccounts', [
                        [
                            "Code" => 'somebankFICO',
                            "DisplayName" => "Some Bank FICO Score",
                            "Balance" => 0,
                        ],
                    ]);
                },
            ]
        );

        $accountId = $this->aw->createAwAccount($this->user->getId(), $code, 'somelogin', 'pass');
        $this->aw->checkAccount($accountId, false);

        $accountData = $manager
            ->getAccountList(
                $this->container->get(OptionsFactory::class)->createDesktopListOptions(
                    (new Options())
                        ->set(Options::OPTION_USER,
                            $this->container->get(AwTokenStorageInterface::class)->getBusinessUser())
                        ->set(Options::OPTION_ACCOUNT_IDS, [$accountId])
                )
            );
        $accounts = $accountData->getAccounts();

        $this->assertEquals(
            $accounts[0]->SubAccountsArray[0][DesktopListMapper::SUBACCOUNT_FICO_KEYWORD],
            true
        );
    }
}
