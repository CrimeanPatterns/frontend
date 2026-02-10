<?php

namespace AwardWallet\MainBundle\Service\BalanceWatch;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\BalanceWatch;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class Timeout
{
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;
    private Query $query;
    private Stopper $stopper;

    public function __construct(LoggerInterface $logger, EntityManagerInterface $entityManager, Query $query, Stopper $stopper)
    {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->query = $query;
        $this->stopper = $stopper;
    }

    public function getTimeoutSecondsByAccountId(int $accountId): int
    {
        return $this->getTimeoutSeconds($this->entityManager->getRepository(Account::class)->find($accountId));
    }

    public function getTimeoutSeconds(Account $account): int
    {
        $watch = $this->query->getAccountBalanceWatch($account);

        if ($watch === null) {
            $this->logger->critical('BalanceWatchManager watch row not found', ['accountId' => $account->getId()]);

            return $this->checkTimeout(0, null);
        }

        if (BalanceWatch::POINTS_SOURCE_TRANSFER === $watch->getPointsSource()) {
            return $this->checkTimeout(Constants::TIMEOUT_SECONDS, $watch);
        }

        return $this->checkTimeout(86400 * 3, $watch);
    }

    // timeout for local dev, or forced stop if background command is failed
    private function checkTimeout(int $timeout, ?BalanceWatch $balanceWatch): int
    {
        /*
        if (null !== $balanceWatch
            && (time() - $balanceWatch->getAccount()->getBalanceWatchStartDate()->getTimestamp()) > $timeout
        ) {
            $this->logger->critical('BalanceWatchManager BalanceWatch row not found (forced stop - reason timeout)', ['accountId' => $balanceWatch->getAccount()->getId()]);
            $this->stopper->stopBalanceWatch($balanceWatch->getAccount(), Constants::EVENT_TIMEOUT);
        }
        */

        return $timeout;
    }
}
