<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class AccountListener
{
    private AccountTotalCalculator $calculator;

    private EntityManagerInterface $entityManager;

    private LoggerInterface $logger;

    public function __construct(AccountTotalCalculator $calculator, EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->calculator = $calculator;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function onAccountUpdate(AccountUpdateEvent $event)
    {
        $account = $event->getAccount();
        $account->setTotalbalance($this->calculator->calculate($account));
        $this->logger->info(sprintf(
            'update total balance #%d, method: %s, total: %d',
            $account->getId(),
            $event->getSource(),
            $account->getTotalbalance()
        ));
        $this->entityManager->flush();
    }
}
