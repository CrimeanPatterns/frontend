<?php

namespace AwardWallet\Tests\Unit\MainBundle\Loyalty\AccountSaving\Processors;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Repositories\SubaccountRepository;
use AwardWallet\MainBundle\Entity\Subaccount;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\BalanceProcessor;
use AwardWallet\Tests\Unit\BaseUserTest;
use Clock\ClockInterface;
use Clock\ClockTest;
use Duration\Duration;
use Psr\Log\LoggerInterface;

use function Duration\seconds;

/**
 * @group frontend-unit
 */
class BalanceProcessorTest extends BaseUserTest
{
    private ?BalanceProcessor $processor;

    private ?Account $account;

    private ?SubaccountRepository $subAccRep;

    private ?ClockInterface $clock;

    public function _before()
    {
        parent::_before();

        $this->processor = new BalanceProcessor($this->em, $this->clock = new ClockTest(), $this->makeEmpty(LoggerInterface::class));
        $this->subAccRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Subaccount::class);
        $this->account = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Account::class)->find(
            $this->aw->createAwAccount(
                $this->user->getId(),
                $this->aw->createAwProvider(),
                'test',
                null,
                [
                    'Balance' => null,
                ]
            )
        );
    }

    public function _after()
    {
        $this->clock = null;
        $this->processor = null;
        $this->subAccRep = null;
        $this->account = null;

        parent::_after();
    }

    public function testAccountFirstBalance()
    {
        $this->assertAccount();
        $this->assertFalse($this->processor->saveAccountBalance($this->account, 100));
        $this->assertAccount(100);
    }

    public function testSubAccountFirstBalance()
    {
        [$subAccount, $subAccount2] = $this->createSubAccounts();
        $this->assertCount(2, $this->account->getSubAccountsEntities());
        $this->assertAccount();
        $this->assertSubAccount($subAccount);
        $this->assertSubAccount($subAccount2);

        $this->assertFalse($this->processor->saveSubAccountBalance($subAccount, 200));

        $this->assertSubAccount($subAccount, 200);
        $this->assertSubAccount($subAccount2);
        $this->assertAccount();
    }

    public function testAccountFirstBalanceChange()
    {
        $this->sleep();
        $this->assertFalse($this->processor->saveAccountBalance($this->account, 100));
        $this->sleep();
        $this->assertTrue($this->processor->saveAccountBalance($this->account, 500));

        $this->assertAccount(500, 100, $this->clock->current()->getAsDateTime(), 1, false);
    }

    public function testSubAccountFirstBalanceChange()
    {
        [$subAccount, $subAccount2] = $this->createSubAccounts();

        $this->sleep();
        $this->assertFalse($this->processor->saveSubAccountBalance($subAccount2, 100));
        $this->sleep();
        $this->assertTrue($this->processor->saveSubAccountBalance($subAccount2, 500));

        $this->assertSubAccount($subAccount2, 500, 100, $this->clock->current()->getAsDateTime(), 1);
        $this->assertSubAccount($subAccount);
        $this->assertAccount();
    }

    public function testAccountBalanceNull()
    {
        $this->assertFalse($this->processor->saveAccountBalance($this->account, null));
        $this->assertAccount();
        $this->sleep();
        $this->assertFalse($this->processor->saveAccountBalance($this->account, 100));
        $this->sleep();
        $this->assertFalse($this->processor->saveAccountBalance($this->account, null));
        $this->assertAccount(null);
        $this->sleep();
        $this->assertFalse($this->processor->saveAccountBalance($this->account, 100));
        $this->assertAccount(100);
        $time = $this->sleep();
        $this->assertTrue($this->processor->saveAccountBalance($this->account, 500));
        $this->assertAccount(500, 100, $time->getAsDateTime(), 1, false);
    }

    public function testSubAccountBalanceNull()
    {
        [$subAccount, $subAccount2] = $this->createSubAccounts();

        $this->assertFalse($this->processor->saveSubAccountBalance($subAccount, null));
        $this->assertSubAccount($subAccount);
        $this->sleep();
        $this->assertFalse($this->processor->saveSubAccountBalance($subAccount, 100));
        $this->sleep();
        $this->assertFalse($this->processor->saveSubAccountBalance($subAccount, null));
        $this->assertSubAccount($subAccount, null);
        $this->sleep();
        $this->assertFalse($this->processor->saveSubAccountBalance($subAccount, 100));
        $this->assertSubAccount($subAccount, 100);
        $time = $this->sleep();
        $this->assertTrue($this->processor->saveSubAccountBalance($subAccount, 500));
        $this->assertSubAccount($subAccount, 500, 100, $time->getAsDateTime(), 1);
    }

    public function testAccountMinorBalanceChange()
    {
        $this->assertFalse($this->processor->saveAccountBalance($this->account, 0.0005));
        $this->assertAccount(0);
        $time = $this->sleep();
        $this->assertTrue($this->processor->saveAccountBalance($this->account, 100));
        $this->sleep();
        $this->assertFalse($this->processor->saveAccountBalance($this->account, 100.001));
        $this->assertAccount(100, 0, $time->getAsDateTime(), 1, false);
        $time = $this->sleep();
        $this->assertTrue($this->processor->saveAccountBalance($this->account, 100.01));
        $this->assertAccount(100.01, 100, $this->clock->current()->getAsDateTime(), 2, false);
        $this->sleep();
        $this->assertFalse($this->processor->saveAccountBalance($this->account, null));
        $this->assertAccount(null, 100, $time->getAsDateTime(), 2, false);
    }

    public function testSubAccountMinorBalanceChange()
    {
        [$subAccount, $subAccount2] = $this->createSubAccounts();
        $this->assertFalse($this->processor->saveSubAccountBalance($subAccount, 0.0005));
        $this->assertSubAccount($subAccount, 0);
        $time = $this->sleep();
        $this->assertTrue($this->processor->saveSubAccountBalance($subAccount, 100));
        $this->sleep();
        $this->assertFalse($this->processor->saveSubAccountBalance($subAccount, 100.001));
        $this->assertSubAccount($subAccount, 100, 0, $time->getAsDateTime(), 1);
        $time = $this->sleep();
        $this->assertTrue($this->processor->saveSubAccountBalance($subAccount, 100.01));
        $this->assertSubAccount($subAccount, 100.01, 100, $this->clock->current()->getAsDateTime(), 2);
        $this->sleep();
        $this->assertFalse($this->processor->saveSubAccountBalance($subAccount, null));
        $this->assertSubAccount($subAccount, null, 100, $time->getAsDateTime(), 2);
    }

    private function assertAccount(
        ?float $balance = null,
        ?float $lastBalance = null,
        ?\DateTime $lastChangeDate = null,
        int $changeCount = 0,
        bool $changesConfirmed = true
    ) {
        $this->db->seeInDatabase('Account', [
            'AccountID' => $this->account->getId(),
            'Balance' => $balance,
            'LastChangeDate' => $lastChangeDate ? $lastChangeDate->format('Y-m-d H:i:s') : null,
            'ChangeCount' => $changeCount,
            'LastBalance' => $lastBalance,
            'ChangesConfirmed' => (int) $changesConfirmed,
        ]);
    }

    private function assertSubAccount(
        Subaccount $subAccount,
        ?float $balance = null,
        ?float $lastBalance = null,
        ?\DateTime $lastChangeDate = null,
        int $changeCount = 0
    ) {
        $this->db->seeInDatabase('SubAccount', [
            'SubAccountID' => $subAccount->getId(),
            'Balance' => $balance,
            'LastChangeDate' => $lastChangeDate ? $lastChangeDate->format('Y-m-d H:i:s') : null,
            'ChangeCount' => $changeCount,
            'LastBalance' => $lastBalance,
        ]);
    }

    /**
     * @return Subaccount[]
     */
    private function createSubAccounts(): array
    {
        /** @var Subaccount $subAccount */
        $subAccount = $this->subAccRep->find($this->aw->createAwSubAccount(
            $this->account->getId(),
            [
                'Balance' => null,
            ]
        ));
        /** @var Subaccount $subAccount2 */
        $subAccount2 = $this->subAccRep->find($this->aw->createAwSubAccount(
            $this->account->getId(),
            [
                'Balance' => null,
            ]
        ));
        $this->em->refresh($this->account);

        return [$subAccount, $subAccount2];
    }

    private function sleep(): Duration
    {
        $this->clock->sleep(seconds(1));

        return $this->clock->current();
    }
}
