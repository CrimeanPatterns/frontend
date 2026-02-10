<?php

namespace AwardWallet\MainBundle\Service\CreditCards\Query;

use Doctrine\DBAL\Connection;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class UpsertNewMerchantsQuery
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param array $params flattened list of params {
     *   name1,
     *   displayName1,
     *   shoppingCategoryGroupID1,
     *   merchantPatternId1,
     *
     *   name2,
     *   displayName2,
     *   shoppingCategoryGroupID2,
     *   merchantPatternId2,
     *   ...
     * }
     * @return int affected rows count
     */
    public function execute(array $params): int
    {
        $sqlPlaceHolder =
            it(\iter\range(1, \count($params) / 4))
            ->map(fn () => '(?, ?, ?, 1, ?)')
            ->collect()
            ->joinToString(', ');

        return $this->connection->executeStatement("
            INSERT INTO Merchant (`Name`, DisplayName, ShoppingCategoryGroupID, Transactions, MerchantPatternID) VALUES 
            {$sqlPlaceHolder}
            ON DUPLICATE KEY UPDATE MerchantPatternID = VALUES(MerchantPatternID)
            ",
            $params
        );
    }
}
