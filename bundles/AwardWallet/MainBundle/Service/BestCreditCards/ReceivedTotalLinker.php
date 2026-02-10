<?php

namespace AwardWallet\MainBundle\Service\BestCreditCards;

use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Psr\Log\LoggerInterface;

class ReceivedTotalLinker
{
    private Connection $connection;
    private ContextAwareLoggerWrapper $logger;
    private ?Statement $historyQuery = null;
    private ?Statement $updateQuery = null;

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        $this->logger = new ContextAwareLoggerWrapper($logger);
        $this->logger->pushContext(['service' => 'ReceivedTotalLinker']);
        $this->connection = $connection;
    }

    public function link(int $userId, float $total, \DateTime $date, int $receivedTotalId): bool
    {
        $uuid = $this->findUuid($userId, $total, $date);

        if ($this->updateQuery === null) {
            $this->updateQuery = $this->connection->prepare("update ReceivedTotal set AccountHistoryUUID = :uuid where ReceivedTotalID = :receivedTotalId");
        }

        return $this->updateQuery->executeStatement(['uuid' => $uuid, 'receivedTotalId' => $receivedTotalId]) > 0;
    }

    private function findUuid(int $userId, float $total, \DateTime $date): ?string
    {
        if ($this->historyQuery === null) {
            $this->historyQuery = $this->connection->prepare(
                "select 
                    UUID 
                from 
                    AccountHistory ah 
                where 
                    ah.Amount = :amount 
                    and ah.PostingDate = :date 
                    and SubAccountID in (select sa.SubAccountID from SubAccount sa join Account a on a.AccountID = sa.AccountID where a.UserID = :userId);
                "
            );
        }

        $uuids = $this->historyQuery->executeQuery(["amount" => $total, "date" => $date->format("Y-m-d"), "userId" => $userId])->fetchFirstColumn();

        if (count($uuids) === 0) {
            return null;
        }

        if (count($uuids) > 1) {
            $this->logger->info("found " . count($uuids) . " uuids for received total $userId, $total, " . $date->format("Y-m-d") . ", too much, skipping");

            return null;
        }

        return $uuids[0];
    }
}
