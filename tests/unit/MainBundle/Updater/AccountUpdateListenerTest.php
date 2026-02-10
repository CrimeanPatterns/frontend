<?php

namespace AwardWallet\Tests\Unit\MainBundle\Updater;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Event\AccountUpdatedEvent;
use AwardWallet\MainBundle\Loyalty\AccountSaving\ProcessingReport;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountResponse;
use AwardWallet\MainBundle\Loyalty\Resources\UserData;
use AwardWallet\MainBundle\Updater\AccountUpdateListener;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use AwardWallet\Tests\Unit\BaseTest;
use Prophecy\Argument;

use function AwardWallet\MainBundle\Globals\Utils\f\propertyPathEq;

/**
 * @group frontend-unit
 * @coversDefaultClass \AwardWallet\MainBundle\Updater\AccountUpdateListener
 */
class AccountUpdateListenerTest extends BaseTest
{
    /**
     * @covers ::onAccountUpdate
     */
    public function testOnAccountUpdated(): void
    {
        $userMock = $this->prophesize(Usr::class);
        $userMock
            ->isUpdater3k()
            ->willReturn(true);

        $account = $this->prophesize(Account::class);
        $account->
            getAccountid()
            ->willReturn(2);
        $account
            ->getUser()
            ->willReturn($userMock->reveal());

        $event = new AccountUpdatedEvent(
            $account->reveal(),
            (new CheckAccountResponse())
                ->setState(ACCOUNT_CHECKED)
                ->setMessage('Success')
                ->setUserdata(new UserData()),
            new ProcessingReport(),
            AccountUpdatedEvent::UPDATE_METHOD_LOYALTY
        );

        $asyncProcessMock = $this->prophesize(Process::class);
        $asyncProcessMock
            ->execute(
                Argument::that(propertyPathEq('accountId', 2)),
                Argument::cetera()
            )
            ->shouldBeCalledOnce();

        $listener = new AccountUpdateListener($asyncProcessMock->reveal());
        $listener->onAccountUpdated($event);
    }
}
