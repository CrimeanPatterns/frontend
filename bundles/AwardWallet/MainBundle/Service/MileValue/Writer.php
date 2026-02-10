<?php

namespace AwardWallet\MainBundle\Service\MileValue;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Psr\Log\LoggerInterface;

class Writer
{
    private Connection $connection;

    private LoggerInterface $logger;

    private HistoryWriter $historyWriter;

    public function __construct(Connection $connection, LoggerInterface $logger, HistoryWriter $historyWriter)
    {
        $this->connection = $connection;
        $this->logger = $logger;
        $this->historyWriter = $historyWriter;
    }

    /**
     * @return int - MileValueID or null if not saved
     */
    public function savePrice(array $params, bool $dryRun): ?int
    {
        if ($dryRun) {
            $this->logger->info("dry run, skip recording price");

            return null;
        }

        $existing = $this->connection->executeQuery("select * from MileValue where TripID = ?", [$params['TripID']])->fetch(FetchMode::ASSOCIATIVE);

        if ($existing === false) {
            $this->logger->info("adding new trip {$params['TripID']} to MileValue");
            $params["CreateDate"] = date("Y-m-d H:i:s");
            $params['UpdateDate'] = $params["CreateDate"];
            $this->connection->insert("MileValue", $params);

            return $this->connection->lastInsertId();
        }

        foreach (Constants::CUSTOM_FIELDS as $field) {
            if ($existing['Custom' . $field]) {
                $this->logger->info("skip updating {$field}, it's custom", ["TripID" => $existing["TripID"]]);
                unset($params[$field]);
            }
        }

        $this->historyWriter->saveHistory($existing['MileValueID'], $existing, $params);

        $params['UpdateDate'] = date("Y-m-d H:i:s");
        $this->logger->info("updating trip {$params['TripID']} in MileValue");
        $this->connection->update("MileValue", $params, ["TripID" => $existing["TripID"]]);

        return $existing['MileValueID'];
    }
}
