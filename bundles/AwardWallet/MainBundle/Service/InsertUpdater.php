<?php

namespace AwardWallet\MainBundle\Service;

use Doctrine\DBAL\Connection;

class InsertUpdater
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function insertOrUpdate(string $tableName, array $values, array $excludeOnUpdate, array $addOnUpdate): int
    {
        $sql = "insert into $tableName(" . implode(", ", array_keys($values)) . ")
        values (" . implode(", ", array_map(function (string $fieldName) { return ":" . $fieldName; }, array_keys($values))) . ")";

        $updates = [];

        foreach ($values as $field => $value) {
            if (in_array($field, $excludeOnUpdate)) {
                continue;
            }

            if (isset($addOnUpdate[$field])) {
                $value = $addOnUpdate[$field];
            } else {
                $value = ":" . $field;
            }
            $updates[] = "$field = $value";
        }
        $sql .= "on duplicate key update " . implode(", ", $updates);

        return $this->connection->executeUpdate($sql, $values);
    }
}
