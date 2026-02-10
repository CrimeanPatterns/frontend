<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\History\PlanLinker;

use AwardWallet\Common\Doctrine\BatchUpdater;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;

class Updater
{
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var MatcherFactory
     */
    private $matcherFactory;
    /**
     * @var Writer
     */
    private $writer;

    public function __construct(
        Connection $connection,
        MatcherFactory $matcherFactory,
        Writer $writer
    ) {
        $this->connection = $connection;
        $this->matcherFactory = $matcherFactory;
        $this->writer = $writer;
    }

    public function update(array $rows)
    {
        if (count($rows) === 0) {
            return;
        }

        [$providerCode, $userId, $userAgentId] = $this->findParams($rows);
        $matcher = $this->matcherFactory->getMatcher($providerCode);

        if ($matcher === null) {
            return;
        }

        $unmatched = [];

        foreach ($rows as $row) {
            $matched = false;

            foreach ($matcher->findMatchingItineraries($providerCode, $userId, $userAgentId, $row) as $match) {
                $this->writer->saveMatch($row['UUID'], $match);
                $matched = true;
            }

            if (!$matched) {
                $unmatched[] = $row['UUID'];
            }
        }

        if (count($unmatched) > 0) {
            $this->unlinkUnmatched($unmatched);
        }
    }

    private function findParams(array $rows): array
    {
        return $this->connection->executeQuery("
        select
            p.Code,
            a.UserID,
            a.UserAgentID
        from
            Provider p 
            join Account a on a.ProviderID = p.ProviderID
        where
            a.AccountID = ?
        limit
            1
        ", [array_values($rows)[0]['AccountID']])->fetch(FetchMode::NUMERIC);
    }

    private function unlinkUnmatched(array $unmatched)
    {
        $batcher = new BatchUpdater($this->connection);
        $unmatched = array_map(function (string $uuid): array {
            return [$uuid];
        }, $unmatched);
        $batcher->batchUpdate($unmatched, "delete from HistoryToTripLink where HistoryID = ?", 30);
    }
}
