<?php

namespace AwardWallet\MainBundle\Updater;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Event\AccountUpdatedEvent;
use AwardWallet\MainBundle\Service\OneTimeCodeProcessor\Event\AccountSentOTCRecheckEvent;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use AwardWallet\MainBundle\Worker\AsyncProcess\UpdaterAccountTickTask;

class AccountUpdateListener
{
    /**
     * @var Process
     */
    private $process;

    public function __construct(Process $process)
    {
        $this->process = $process;
    }

    public function onAccountOTCRecheck(AccountSentOTCRecheckEvent $event): void
    {
        $this->scheduleAccountTick($event->getAccount());
    }

    public function onAccountUpdated(AccountUpdatedEvent $event)
    {
        $this->scheduleAccountTick($event->getAccount());
    }

    private function scheduleAccountTick(Account $account): void
    {
        $updaterTask = new UpdaterAccountTickTask($account->getAccountid());
        $this->process->execute($updaterTask, null, false, Process::PRIORITY_HIGH);
    }
}
