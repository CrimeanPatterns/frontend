<?php

namespace AwardWallet\MainBundle\Service\CreditCards;

use AwardWallet\MainBundle\Entity\ShoppingCategory;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class MerchantCategoryDetector
{
    private const TRUST_TRANSACTIONS_LIMIT = 15;

    private Connection $replicaConnection;
    private LoggerInterface $logger;
    private array $priorities;

    public function __construct(Connection $replicaConnection, LoggerInterface $logger)
    {
        $this->replicaConnection = $replicaConnection;
        $this->logger = $logger;
        $this->priorities = $replicaConnection->fetchAllKeyValue("
            select
                sc.ShoppingCategoryID,
                scg.Priority
            from
                ShoppingCategory sc
                join ShoppingCategoryGroup scg on sc.ShoppingCategoryGroupID = scg.ShoppingCategoryGroupID
        ");
    }

    /**
     * @param bool $debug - costly option, disabled by default for performance reasons
     */
    public function detectCategory(int $merchantId, int $merchantTransactions, bool $debug = false): ?int
    {
        $limit = $merchantTransactions * 0.01 < self::TRUST_TRANSACTIONS_LIMIT ? self::TRUST_TRANSACTIONS_LIMIT : (int) ($merchantTransactions * 0.01);

        $rows = $this->replicaConnection->executeQuery(
            "SELECT ShoppingCategoryID, count(1) AS Transactions FROM (
                SELECT ShoppingCategoryID FROM AccountHistory
                WHERE MerchantID = ?
                AND ShoppingCategoryID IS NOT NULL
                ORDER BY PostingDate DESC
                LIMIT ?
            ) h
            GROUP BY ShoppingCategoryID
            ORDER BY Transactions DESC",
            [$merchantId, $limit], [\PDO::PARAM_INT, \PDO::PARAM_INT]
        )->fetchAllKeyValue();

        $highestPriority = 0;
        $result = null;

        foreach ($rows as $shoppingCategoryId => $categoryTransactions) {
            if (in_array((int) $shoppingCategoryId, ShoppingCategory::IGNORED_CATEGORIES)) {
                $this->logger->debug("category " . $this->debugInfo($shoppingCategoryId, $debug) . " is ignored");

                continue;
            }

            if ($result === null) {
                $result = $shoppingCategoryId;
            }

            $priority = $this->priorities[(int) $shoppingCategoryId] ?? 0;
            $this->logger->debug("category " . $this->debugInfo($shoppingCategoryId, $debug) . ", transactions: $categoryTransactions, priority: $priority");

            if ($priority > $highestPriority) {
                $result = $shoppingCategoryId;
                $highestPriority = $priority;
            }
        }

        $this->logger->debug("merchant $merchantId mapped to category " . $this->debugInfo($result, $debug) . ", priority {$highestPriority}, based on last " . array_sum($rows) . " transactions");

        return $result;
    }

    private function debugInfo(?int $id, bool $debug): ?string
    {
        if (!$debug) {
            return $id;
        }

        $info = $this->replicaConnection->fetchAssociative("select
            sc.Name as CategoryName,
            sc.ShoppingCategoryGroupID,
            scg.Name as GroupName
        from
            ShoppingCategory sc
            left outer join ShoppingCategoryGroup scg on sc.ShoppingCategoryGroupID = scg.ShoppingCategoryGroupID 
        where
            sc.ShoppingCategoryID = ?
        ", [$id]);

        if ($info === false) {
            return "{$id} (not found)";
        }

        return "{$id} - {$info['CategoryName']}, group {$info['ShoppingCategoryGroupID']} - {$info['GroupName']})";
    }
}
