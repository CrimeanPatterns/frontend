<?php

namespace AwardWallet\Tests\Unit\MainBundle\Manager;

use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Manager\AccountListManager;
use AwardWallet\Tests\Unit\BaseUserTest;
use Codeception\Module\Aw;

/**
 * @group frontend-unit
 */
class AccountListManagerTest extends BaseUserTest
{
    protected $userId;
    protected $accountId;
    /** @var AccountListManager */
    private $manager;

    public function _before()
    {
        parent::_before();
        $this->manager = $this->container->get(AccountListManager::class);
        $this->accountId = $this->aw->createAwAccount($this->user->getUserid(), Aw::TEST_PROVIDER_ID, 'Checker.SubAccountsBalance');
    }

    public function testChangeSubaccBalanceSuccess()
    {
        $this->aw->checkAccount($this->accountId);
        $data = $this->manager
            ->getAccountList(
                $this->container->get(OptionsFactory::class)->createDesktopListOptions(
                    (new Options())
                        ->set(Options::OPTION_USER, $this->container->get(AwTokenStorageInterface::class)->getBusinessUser())
                        ->set(Options::OPTION_ACCOUNT_IDS, [$this->accountId])
                )
            );
        $subaccs = $data['a' . $this->accountId]['SubAccountsArray'];
        $this->assertCount(2, $subaccs);

        foreach ($subaccs as $subacc) {
            $this->assertArrayHasKey('BalanceInTotalSum', $subacc['Properties']);
            $this->assertEquals('true', $subacc['Properties']['BalanceInTotalSum']['Val']);
        }
    }
}
