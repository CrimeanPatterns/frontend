<?php

namespace AwardWallet\MainBundle\Command\CreditCards\CompleteTransactionsCommand;

use AwardWallet\MainBundle\Globals\StringUtils;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\TransactionIsolationLevel;

use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtObj;

class MainQuery
{
    public const SOURCE_MASTER = 'master';
    public const SOURCE_REPLICA = 'replica';
    private Connection $unbuffConnection;
    private Connection $replicaConnection;

    public function __construct(Connection $unbufConnection, Connection $replicaUnbufferedConnection)
    {
        $this->unbuffConnection = $unbufConnection;
        $this->replicaConnection = $replicaUnbufferedConnection;
    }

    /**
     * @param list<string> $uuids
     * @return iterable<AccountHistoryRow>
     */
    public function execute(string $source, bool $update, ?string $where = null, array $uuids = [], ?int $limit = null): iterable
    {
        // ---------------------------------------------------------
        // собираем транзакции
        $params = [];
        $types = [];
        $uuidWhere = '';

        if ($uuids) {
            $params[] = $uuids;
            $types[] = Connection::PARAM_STR_ARRAY;
            $uuidWhere = 'h.UUID in (?) AND ';
        }

        $sql = 'SELECT 
            h.UUID, h.Description, h.Miles, h.Amount, h.PostingDate, 
            h.Category, h.ShoppingCategoryID, h.MerchantID, h.Multiplier,
            a.ProviderID,
            a.UpdateDate
        FROM 
            AccountHistory h ' . (($where || $uuids) ? "" : 'FORCE INDEX (idxCompleteTransactions)') . '
            JOIN Account a ON h.AccountID = a.AccountID
        WHERE ' . $uuidWhere . '  ABS(Amount) > 0
        AND h.SubAccountID IS NOT NULL';

        if (StringUtils::isNotEmpty($where)) {
            $sql .= " and " . $where;
        }

        if (!$update) {
            $sql .= " AND h.MerchantID IS NULL";
        }

        if (null !== $limit) {
            $sql .= " limit " . $limit;
        }

        $dbConn = $source === self::SOURCE_MASTER ? $this->unbuffConnection : $this->replicaConnection;
        $dbConn->setTransactionIsolation(TransactionIsolationLevel::READ_COMMITTED);
        $result = $dbConn->executeQuery(
            $sql,
            $params,
            $types
        );

        return stmtObj($result, AccountHistoryRow::class);
    }
}
