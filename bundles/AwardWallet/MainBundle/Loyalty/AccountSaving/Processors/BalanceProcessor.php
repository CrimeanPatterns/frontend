<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Processors;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Subaccount;
use Clock\ClockInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class BalanceProcessor
{
    private EntityManagerInterface $em;

    private ClockInterface $clock;

    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $em, ClockInterface $clock, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->clock = $clock;
        $this->logger = $logger;
    }

    public function saveAccountBalance(Account $account, ?float $balance, bool $manual = false): bool
    {
        return $this->saveBalance($account, $balance, $manual);
    }

    public function saveSubAccountBalance(Subaccount $subaccount, ?float $balance): bool
    {
        return $this->saveBalance($subaccount, $balance, false);
    }

    /**
     * @param Account|Subaccount $account
     */
    private function saveBalance($account, ?float $balance, bool $manual): bool
    {
        $origin = $account;
        /** @var Subaccount $subAccount */
        $subAccount = null;

        if ($account instanceof Subaccount) {
            [$account, $subAccount] = [$account->getAccountid(), $account];
        }

        $this->logger->info(sprintf(
            'try saving account #%d%s balance - %s',
            $account->getId(),
            isset($subAccount) ? '.' . $subAccount->getId() : '',
            $balance ?? 'null'
        ));
        $conn = $this->em->getConnection();
        $lastBalance = $conn->executeQuery("
            SELECT
                ab.Balance
            FROM
                AccountBalance ab
            WHERE
                ab.AccountID = :accountId
                AND " . ($subAccount ? "ab.SubAccountID = " . $subAccount->getId() : "ab.SubAccountID IS NULL") . "
            ORDER BY ab.UpdateDate DESC
            LIMIT 1
        ", [
            ':accountId' => $account->getId(),
        ])->fetchOne();
        $currentBalance = $conn->executeQuery("
            SELECT
                Balance
            FROM
                " . ($origin instanceof Account ? 'Account' : 'SubAccount') . "
            WHERE
                " . ($origin instanceof Account ? 'AccountID' : 'SubAccountID') . " = ?
        ", [$origin->getId()])->fetchOne();

        $this->logger->info(sprintf(
            'last balance: %s, current balance: %s',
            $lastBalance !== false ? $lastBalance : 'null',
            $currentBalance !== false && !is_null($currentBalance) ? $currentBalance : 'null',
        ));
        $afterNA = false;
        $hasPrevBalance = $lastBalance !== false;
        $result = false;

        if (
            !$hasPrevBalance
            || is_null($balance)
            || sprintf('%0.2f', $lastBalance) != sprintf('%0.2f', $balance)
            || ($afterNA = ($currentBalance === false || is_null($currentBalance)))
        ) {
            $this->logger->info(sprintf('save balance %s', is_null($balance) ? 'null' : $balance));

            if (!is_null($balance) && !$afterNA) {
                $this->logger->info('add AccountBalance row');
                $conn->executeStatement("
                    INSERT INTO AccountBalance (AccountID, SubAccountID, Balance, UpdateDate) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE Balance = ?
                ", [
                    $account->getId(),
                    $subAccount ? $subAccount->getId() : null,
                    $balance,
                    $this->clock->current()->getAsDateTime()->format('Y-m-d H:i:s'),
                    $balance,
                ]);
            }

            $changes = $conn->executeQuery("
                SELECT
                    COUNT(*) - 1
                FROM
                    AccountBalance ab
                WHERE
                    ab.AccountID = :accountId
                    AND " . ($subAccount ? "ab.SubAccountID = " . $subAccount->getId() : "ab.SubAccountID IS NULL") . "
            ", [
                ':accountId' => $account->getId(),
            ])->fetchOne();

            $origin->setBalance($balance);
            $origin->setChangecount(max(intval($changes), 0));

            if (!is_null($balance) && !$afterNA) {
                if ($hasPrevBalance) {
                    $this->logger->info('update last balance, last change date');
                    $result = true;
                    $origin->setLastbalance($lastBalance);
                    $origin->setLastchangedate($this->clock->current()->getAsDateTime());
                }

                if ($origin instanceof Account) {
                    $origin->setChangesConfirmed($manual || !$hasPrevBalance);
                }
            }

            $this->em->flush();
        }

        return $result;
    }
}
