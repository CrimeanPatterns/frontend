<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\History\PlanLinker;

use AwardWallet\MainBundle\Service\ProgressLogger;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class Cleaner
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var Connection
     */
    private $unbufConnection;

    public function __construct(
        LoggerInterface $logger,
        Connection $connection,
        Connection $unbufConnection
    ) {
        $this->logger = $logger;
        $this->connection = $connection;
        $this->unbufConnection = $unbufConnection;
    }

    public function cleanProvider(string $provider, int $endTime)
    {
        $this->logger->info("deleting old links for $provider");
        $providerId = $this->connection->executeQuery("select ProviderID from Provider where Code = ?", [$provider])->fetchColumn();

        $this->unbufConnection->executeQuery("
        select
            hpl.TripID,
            hpl.HistoryID
        from
            HistoryToTripLink hpl
            join Trip t on t.TripID = hpl.TripID
        where
            t.ProviderID = :providerId
            and hpl.LinkDate < :endTime
        ", ["providerId" => $providerId, "endTime" => date("Y-m-d H:i:s", $endTime)]);

        $progress = new ProgressLogger($this->logger, 100, 30);

        $deleteQuery = $this->connection->prepare("delete from HistoryToTripLink where TripID = :tripId and HistoryID = :historyId");

        $count = 0;

        foreach ($this->unbufConnection as $row) {
            $progress->showProgress("deleting old links for $provider", $count);
            $deleteQuery->execute(["tripId" => $row['TripID'], "historyId" => $row['HistoryID']]);
            $count++;
        }

        $this->logger->info("deleted {$count} old links for $provider");
    }
}
