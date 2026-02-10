<?php

namespace AwardWallet\MainBundle\Service\Lounge;

use Doctrine\DBAL\Connection;

class Storage
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function save(string $key, array $value, ?\DateTime $expirationDate = null): bool
    {
        try {
            $this->connection->executeStatement('
                INSERT INTO LoungeKeyValueStorage (LoungeKeyValueStorageID, Value, ExpirationDate)
                VALUES (:id, :value, :expirationDate)
                ON DUPLICATE KEY UPDATE
                    Value = :value,
                    ExpirationDate = :expirationDate
            ', [
                'id' => $key,
                'value' => json_encode($value, JSON_UNESCAPED_UNICODE),
                'expirationDate' => $expirationDate ? $expirationDate->format('Y-m-d H:i:s') : null,
            ]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function get(string $key): ?array
    {
        try {
            $query = '
                SELECT Value FROM LoungeKeyValueStorage
                WHERE LoungeKeyValueStorageID = :id 
                AND (ExpirationDate IS NULL OR ExpirationDate > NOW())
            ';

            $value = $this->connection->fetchOne($query, ['id' => $key]);

            if ($value === false) {
                return null;
            }

            return json_decode($value, true);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function delete(string $key): bool
    {
        try {
            $this->connection->delete(
                'LoungeKeyValueStorage',
                ['LoungeKeyValueStorageID' => $key]
            );

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function clearExpired(): void
    {
        try {
            $this->connection->executeStatement('DELETE FROM LoungeKeyValueStorage WHERE ExpirationDate < NOW()');
        } catch (\Exception $e) {
        }
    }

    public function setExpiration(string $key, ?\DateTime $expirationDate = null): bool
    {
        try {
            $data = ['ExpirationDate' => null];

            if (!is_null($expirationDate)) {
                $data['ExpirationDate'] = $expirationDate->format('Y-m-d H:i:s');
            }

            $this->connection->update(
                'LoungeKeyValueStorage',
                $data,
                ['LoungeKeyValueStorageID' => $key]
            );

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
