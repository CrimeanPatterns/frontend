<?php

namespace AwardWallet\MainBundle\Service\BalanceWatch;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\BalanceWatch;
use AwardWallet\MainBundle\Event\AccountBalanceChangedEvent;
use AwardWallet\MainBundle\Event\AccountUpdatedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Monolog\Logger;

class BalanceWatchListener
{
    /** @var Logger */
    private $logger;

    /** @var EntityManagerInterface */
    private $entityManager;
    private Query $bwQuery;

    private Stopper $stopper;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        Query $bwQuery,
        Stopper $stopper
    ) {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->bwQuery = $bwQuery;
        $this->stopper = $stopper;
    }

    public function onAccountUpdated(AccountUpdatedEvent $event): void
    {
        $account = $event->getAccount();
        $isBalanceWatch = $account->getBalanceWatchStartDate() !== null;

        if ($isBalanceWatch
            && (
                in_array($event->getCheckAccountResponse()->getState(), [ACCOUNT_MISSING_PASSWORD, ACCOUNT_INVALID_PASSWORD, ACCOUNT_LOCKOUT, ACCOUNT_PREVENT_LOCKOUT])
                || $account->isDisabled()
            )) {
            $eventReason = Constants::EVENT_UPDATE_ERROR;
            $this->logger->info('BalanceWatch - STOP', [
                'accountId' => $account->getId(),
                'reason' => Constants::EVENTS[$eventReason],
                'place' => 'BalanceWatchListener::onAccountBalanceChanged',
            ]);
            $this->stopper->stopBalanceWatch($account, $eventReason);
        }
    }

    public function onAccountBalanceChanged(AccountBalanceChangedEvent $event)
    {
        if ($event->getSource() === AccountBalanceChangedEvent::SOURCE_MANUAL) {
            return;
        }

        $account = $event->getAccount();
        $isBalanceWatch = $account->getBalanceWatchStartDate() !== null;

        if ($isBalanceWatch && $account->getErrorcode() === ACCOUNT_CHECKED) {
            $eventReason = null;

            if ($account->getBalance() !== $account->getLastbalance()
                && $account->getLastchangedate() > $account->getBalanceWatchStartDate()) {
                $balanceWatch = $this->bwQuery->getAccountBalanceWatch($account);

                if (null === $balanceWatch) {
                    throw new \LogicException('BalanceWatchListener: data not found to calculate changes');
                }

                if ((null === $balanceWatch->getExpectedPoints() && $account->getBalance() > $account->getLastbalance())
                    || ($account->getBalance() - $account->getLastbalance()) >= $balanceWatch->getExpectedPoints()
                    || $this->getAccountBalanceChanged($account, $balanceWatch) >= $balanceWatch->getExpectedPoints()) {
                    $eventReason = Constants::EVENT_BALANCE_CHANGED;
                }
            }

            if ($eventReason) {
                $this->logger->info('BalanceWatch - STOP', [
                    'accountId' => $account->getId(),
                    'reason' => Constants::EVENTS[$eventReason],
                    'place' => 'BalanceWatchListener::onAccountBalanceChanged',
                ]);
                $this->stopper->stopBalanceWatch($account, $eventReason);
            }
        }
    }

    private function getAccountBalanceChanged(Account $account, BalanceWatch $balanceWatch): int
    {
        $totalChange = 0;
        $balanceStart = $this->entityManager->getConnection()->fetchColumn(
            'SELECT Balance FROM AccountBalance WHERE AccountID = ? AND UNIX_TIMESTAMP(UpdateDate) < ? AND SubAccountID IS NULL ORDER BY UpdateDate DESC LIMIT 1',
            [$account->getId(), $balanceWatch->getCreationDate()->getTimestamp()],
            0, [\PDO::PARAM_INT, \PDO::PARAM_INT]
        );

        $balances = $this->entityManager->getConnection()->fetchAll(
            'SELECT Balance FROM AccountBalance WHERE AccountID = ? AND UNIX_TIMESTAMP(UpdateDate) > ? AND SubAccountID IS NULL ORDER BY UpdateDate ASC',
            [$account->getId(), $balanceWatch->getCreationDate()->getTimestamp()],
            [\PDO::PARAM_INT, \PDO::PARAM_INT]
        );
        $balances = empty($balances) ? [] : array_column($balances, 'Balance');

        for ($i = 0, $iCount = \count($balances); $i < $iCount; $i++) {
            if (0 === $i) {
                $totalChange += ($balances[$i] - $balanceStart);

                continue;
            }
            $totalChange += ($balances[$i] - $balances[$i - 1]);
        }

        return $totalChange;
    }
}
