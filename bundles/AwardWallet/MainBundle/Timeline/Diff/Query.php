<?php

namespace AwardWallet\MainBundle\Timeline\Diff;

use Doctrine\DBAL\Connection;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class Query
{
    /**
     * @var \Doctrine\DBAL\Driver\Statement
     */
    private $query;
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->query = $connection->prepare("
		SELECT Property, OldVal, NewVal, ChangeDate FROM DiffChange WHERE SourceID = ? ORDER BY ChangeDate, DiffChangeID
		");
        $this->connection = $connection;
    }

    public function query($sourceId)
    {
        $this->query->execute([$sourceId]);

        return new Changes($this->query->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function queryAll(array $sourceIds): array
    {
        $sourceIds = \array_values($sourceIds);
        $sourceIdsMap = \array_flip($sourceIds);
        $stmt = $this->connection->executeQuery(
            "SELECT SourceID, Property, OldVal, NewVal, ChangeDate FROM DiffChange WHERE SourceID in (?) ORDER BY SourceID, ChangeDate, DiffChangeID",
            [$sourceIds],
            [Connection::PARAM_STR_ARRAY]
        );

        $changes =
            it($stmt)
            ->groupAdjacentByColumn('SourceID')
            ->reindex(function (array $changes) {
                return $changes[0]['SourceID'];
            })
            ->map(function (array $changes) {
                return new Changes($changes);
            })
            ->toArrayWithKeys();

        $emptyChange = new Changes([]);

        foreach (\array_diff_key($sourceIdsMap, $changes) as $absentKeyInDb => $_) {
            $changes[$absentKeyInDb] = $emptyChange;
        }

        return $changes;
    }
}
