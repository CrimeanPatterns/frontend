<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Loyalty\AccountSaving;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Subaccount;
use AwardWallet\MainBundle\Event\AccountBalanceChangedEvent;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\AccountProcessor;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\BalanceProcessor;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountResponse;
use AwardWallet\MainBundle\Loyalty\Resources\SubAccount as LoyaltySubAccount;
use AwardWallet\MainBundle\Loyalty\Resources\UserData;
use AwardWallet\Tests\FunctionalSymfony\BaseTraitCest;
use AwardWallet\Tests\FunctionalSymfony\Traits\FreeUser;
use Clock\ClockInterface;
use Clock\ClockTest;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use function Duration\seconds;

/**
 * @group frontend-functional
 */
class BalanceCest extends BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use FreeUser;
    private const SUBACC_CODE = 'Testsub';

    private ?ClockInterface $clock;

    private ?Account $account;

    private ?Subaccount $subaccount;

    private ?AccountProcessor $accountProcessor;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);

        /** @var EntityManagerInterface $em */
        $em = $I->grabService('doctrine.orm.default_entity_manager');
        $this->account = $em->getRepository(Account::class)->find(
            $I->createAwAccount($this->user->getId(), 'testprovider', 'login1', null, [
                'Balance' => null,
            ])
        );
        /** @var Subaccount $subAccount */
        $this->subaccount = $em->getRepository(Subaccount::class)->find($I->createAwSubAccount(
            $this->account->getId(),
            [
                'Balance' => null,
                'Code' => self::SUBACC_CODE,
            ]
        ));

        $I->mockService(BalanceProcessor::class, new BalanceProcessor(
            $em,
            $this->clock = new ClockTest(),
            $I->stubMakeEmpty(LoggerInterface::class),
        ));

        $this->accountProcessor = $I->grabService(AccountProcessor::class);
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $this->clock = null;
        $this->account = null;
        $this->subaccount = null;
        $this->accountProcessor = null;

        parent::_after($I);
    }

    public function testAccount(\TestSymfonyGuy $I)
    {
        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = $I->grabService("event_dispatcher");
        $eventWasSent = false;
        $eventDispatcher->addListener(AccountBalanceChangedEvent::class, function (AccountBalanceChangedEvent $event) use (&$eventWasSent) {
            $eventWasSent = true;
        });

        $this->assertCountBalances($I, 0);
        $this->accountProcessor->saveAccount($this->account, $this->getCheckAccountResponse(250, ACCOUNT_CHECKED));
        $this->assertCountBalances($I, 1);
        $this->assertAccountBalance($I, 250);
        $I->assertFalse($eventWasSent);

        $this->sleep();
        $this->accountProcessor->saveAccount($this->account, $this->getCheckAccountResponse(0, ACCOUNT_WARNING));
        $this->assertCountBalances($I, 2);
        $this->assertAccountBalance($I, 0);
        $I->assertTrue($eventWasSent);
        $eventWasSent = false;

        $this->sleep();
        $this->accountProcessor->saveAccount($this->account, $this->getCheckAccountResponse(100, ACCOUNT_INVALID_PASSWORD));
        $this->assertCountBalances($I, 2);
        $I->assertFalse($eventWasSent);
    }

    public function testSubAccount(\TestSymfonyGuy $I)
    {
        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = $I->grabService("event_dispatcher");
        $eventWasSent = false;
        $eventDispatcher->addListener(AccountBalanceChangedEvent::class, function (AccountBalanceChangedEvent $event) use (&$eventWasSent) {
            $eventWasSent = true;
        });

        $this->assertCountBalances($I, 0);
        $this->accountProcessor->saveAccount($this->account, $this->getCheckSubAccountResponse(300, ACCOUNT_CHECKED));
        $this->assertCountBalances($I, 1, true);
        $this->assertAccountBalance($I, 300, true);
        $I->assertFalse($eventWasSent);

        $this->sleep();
        $this->accountProcessor->saveAccount($this->account, $this->getCheckSubAccountResponse(0, ACCOUNT_WARNING));
        $this->assertCountBalances($I, 2, true);
        $this->assertAccountBalance($I, 0, true);
        $I->assertTrue($eventWasSent);
        $eventWasSent = false;

        $this->sleep();
        $this->accountProcessor->saveAccount($this->account, $this->getCheckSubAccountResponse(50, ACCOUNT_INVALID_PASSWORD));
        $this->assertCountBalances($I, 2, true);
        $I->assertFalse($eventWasSent);
    }

    private function assertAccountBalance(\TestSymfonyGuy $I, ?float $balance, bool $isSubaccount = false)
    {
        $I->seeInDatabase('AccountBalance', [
            'AccountID' => $this->account->getId(),
            'SubAccountID' => $isSubaccount ? $this->subaccount->getId() : null,
            'Balance' => $balance,
        ]);
    }

    private function assertCountBalances(\TestSymfonyGuy $I, int $count, bool $isSubaccount = false)
    {
        $I->assertEquals($count, $I->grabCountFromDatabase('AccountBalance', [
            'AccountID' => $this->account->getId(),
            'SubAccountID' => $isSubaccount ? $this->subaccount->getId() : null,
        ]));
    }

    private function getCheckAccountResponse(?float $balance, int $state): CheckAccountResponse
    {
        $response = new CheckAccountResponse();
        $response
            ->setUserdata(new UserData($this->account->getId()))
            ->setCheckdate(new \DateTime())
            ->setRequestdate(new \DateTime())
            ->setState($state)
            ->setBalance($balance);

        return $response;
    }

    private function getCheckSubAccountResponse(?float $balance, int $state): CheckAccountResponse
    {
        $response = new CheckAccountResponse();
        $subAccount = new LoyaltySubAccount();
        $subAccount->setCode(self::SUBACC_CODE);
        $subAccount->setBalance($balance);

        $response
            ->setUserdata(new UserData($this->account->getId()))
            ->setCheckdate(new \DateTime())
            ->setRequestdate(new \DateTime())
            ->setState($state)
            ->setBalance(0)
            ->setSubaccounts([$subAccount]);

        return $response;
    }

    private function sleep()
    {
        $this->clock->sleep(seconds(1));
    }
}
