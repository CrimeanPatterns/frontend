<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\History\PlanLinker;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Psr\Log\LoggerInterface;

class Writer
{
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /** @var Statement[] */
    private $queries = [];

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function saveMatch(string $historyId, Match $match)
    {
        $query = $this->getQuery($match->getTable());
        $query->execute(["HistoryID" => $historyId, "ItineraryID" => $match->getId()]);
    }

    private function getQuery(string $table): Statement
    {
        if (!isset($this->queries[$table])) {
            switch ($table) {
                case "Trip":
                    $sql = "insert into HistoryToTripLink(HistoryID, TripID) values (:HistoryID, :ItineraryID) 
                        on duplicate key update LinkDate = current_timestamp";

                    break;

                default:
                    throw new \Exception("Unknown table: $table");
            }
            $this->queries[$table] = $this->connection->prepare($sql);
        }

        return $this->queries[$table];
    }
}
