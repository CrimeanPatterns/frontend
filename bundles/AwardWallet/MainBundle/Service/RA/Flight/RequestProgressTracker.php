<?php

namespace AwardWallet\MainBundle\Service\RA\Flight;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;

use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtAssoc;

class RequestProgressTracker
{
    private Connection $connection;

    private Statement $addRequestStatement;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;

        $this->addRequestStatement = $this->connection->prepare('
            INSERT INTO RAFlightSearchRequest (RAFlightSearchRequestID, RAFlightSearchQueryID, RequestDate, ResponseDate)
            VALUES (?, ?, NOW(), NULL)
        ');
    }

    public function requestStarted(string $searchRequestId, int $searchQueryId): void
    {
        $this->addRequestStatement->executeStatement([$searchRequestId, $searchQueryId]);
    }

    /**
     * @param int[] $routesIds results of search
     */
    public function responseReceived(string $searchRequestId, array $routesIds = []): void
    {
        if (!empty($routesIds)) {
            $this->connection->executeStatement('
                INSERT IGNORE INTO RAFlightSearchResponse (RAFlightSearchRequestID, RAFlightSearchRouteID)
                VALUES ' . implode(', ', array_map(fn (int $id) => "('" . addslashes($searchRequestId) . "', $id)", $routesIds))
            );
        }

        $this->connection->executeStatement(
            'UPDATE RAFlightSearchRequest
            SET ResponseDate = NOW()
            WHERE RAFlightSearchRequestID = ?',
            [$searchRequestId]
        );
    }

    public function deleteRequests(int $searchQueryId): void
    {
        $this->connection->executeStatement(
            'DELETE FROM RAFlightSearchRequest
            WHERE RAFlightSearchQueryID = ?',
            [$searchQueryId]
        );
    }

    /**
     * @return iterable<array{RAFlightSearchQueryID: int, Total: int, Completed: int}>
     */
    public function getCompletedQueries(int $timeoutSec = 180): iterable
    {
        return stmtAssoc($this->connection->executeQuery("
            SELECT
                r.RAFlightSearchQueryID,
                COUNT(*) AS Total,
                COUNT(CASE WHEN ResponseDate IS NOT NULL OR RequestDate < NOW() - INTERVAL $timeoutSec SECOND THEN 1 END) AS Completed
            FROM RAFlightSearchRequest r
            GROUP BY r.RAFlightSearchQueryID
            HAVING Total = Completed
        "));
    }

    /**
     * @return array ['total' => int, 'completed' => int, 'pending' => int, 'timeout' => int, 'progress' => float]
     */
    public function getProgress(int $searchQueryId, int $timeoutSec = 180): array
    {
        $result = $this->connection->fetchAssociative("
            SELECT
                COUNT(*) AS Total,
                COUNT(CASE WHEN ResponseDate IS NULL AND RequestDate < NOW() - INTERVAL $timeoutSec SECOND THEN 1 END) AS Timeout,
                COUNT(CASE WHEN ResponseDate IS NULL AND RequestDate > NOW() - INTERVAL $timeoutSec SECOND THEN 1 END) AS Pending
            FROM RAFlightSearchRequest r
            WHERE r.RAFlightSearchQueryID = ?
        ", [$searchQueryId]);

        $total = (int) $result['Total'];
        $pending = (int) $result['Pending'];
        $completed = $total - $pending;
        $progress = $total ? round($completed / $total, 2) : 0;

        return [
            'total' => $total,
            'completed' => $completed,
            'pending' => $pending,
            'timeout' => (int) $result['Timeout'],
            'progress' => $progress,
        ];
    }
}
