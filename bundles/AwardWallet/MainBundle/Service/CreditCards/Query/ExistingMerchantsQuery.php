<?php

namespace AwardWallet\MainBundle\Service\CreditCards\Query;

use Doctrine\DBAL\Connection;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function AwardWallet\MainBundle\Globals\Utils\stmt\stmt;

class ExistingMerchantsQuery
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param array[] $criteria array of array{Name, NotNullGroupID}
     * @return iterable<array> iterable of array{Name, MerchantID, NotNullGroupID}
     */
    public function execute(array $criteria): iterable
    {
        if (!$criteria) {
            return [];
        }

        $preSelectStmt = $this->connection->executeQuery(
            'select `Name`, MerchantID, NotNullGroupID from Merchant use index (akNameNotNullGroupID) where '
            . it($criteria)
              ->map(fn () => '(`Name` = ? AND NotNullGroupID = ?)')
              ->joinToString(' OR '),
            it($criteria)
                    ->flatten(1)
                    ->toArray()
        );

        return stmt($preSelectStmt);
    }
}
