<?php

namespace AwardWallet\MainBundle\Service\StoreLocationFinder;

use AwardWallet\MainBundle\Event\AccountUpdateEvent;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use AwardWallet\MainBundle\Worker\AsyncProcess\StoreLocationFinderTask;
use Psr\Log\LoggerInterface;

class AccountListener
{
    private Process $asyncTaskExecutor;

    private LoggerInterface $logger;

    public function __construct(
        Process $asyncTaskExecutor,
        LoggerInterface $statLogger
    ) {
        $this->asyncTaskExecutor = $asyncTaskExecutor;
        $this->logger = $statLogger;
    }

    public function onAccountUpdate(AccountUpdateEvent $event)
    {
        if ($task = StoreLocationFinderTask::createFromLoyalty($event->getAccount())) {
            $this->asyncTaskExecutor->execute($task);
        }
    }
}
