<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use Doctrine\ORM\EntityRepository;

class ParameterRepository extends EntityRepository
{
    public const BACKGROUND_CHECK_DISABLED = 'background_check_disabled';
    public const CHECKIN_DISABLED = 'checkin_disabled';
    public const SEMI_HOURLY_DISABLED = 'semi_hourly_disabled';
    public const PROVIDER_QUEUE_SIZE = 'provider_queue_size';
    public const MERCHANT_REPORT_VERSION = 'merchant_report_version';
    public const MERCHANT_UPPER_DATE = 'merchant_upper_date';
    public const CLICKHOUSE_DB_VERSION = 'clickhouse_db_version';
    public const SKYSCANNER_DEALS_VERSION = 'skyscanner_deals_version';
    public const CONVERTED_INFO_PROVIDERS_PARAM = 'converted_info_column_providers_param';
    public const LAST_TRANSACTIONS_DATE = 'last_transactions_date';
    public const MERCHANT_EXAMPLES_DATE = 'merchant_examples_date';
    public const ENORMOUS_BALANCE_LIMIT = 50_000_000;

    public function getMilesCount($updateCache = false)
    {
        $connection = $this->getEntityManager()->getConnection();
        $sql = "
			SELECT Val
			FROM   Param
			WHERE  Name = 'TotalBalance'
		";
        $total = 0;
        $stmt = $connection->executeQuery($sql);
        $rowBalance = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($rowBalance === false || trim($rowBalance['Val']) == '' || $updateCache) {
            $stmt = $connection->executeQuery("
                SELECT 
                    SUM(Balance) AS Balance 
                FROM Account
                WHERE
                    Balance BETWEEN - :balanceLimit AND :balanceLimit",
                [':balanceLimit' => self::ENORMOUS_BALANCE_LIMIT]
            );
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            $total += $row['Balance'];
            $stmt = $connection->executeQuery("
                SELECT 
                    SUM(Balance) AS Balance 
                FROM SubAccount
                WHERE
                    Balance BETWEEN - :balanceLimit AND :balanceLimit",
                [':balanceLimit' => self::ENORMOUS_BALANCE_LIMIT]
            );
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            $total += $row['Balance'];
            $total = round($total);

            if ($rowBalance !== false && trim($rowBalance['Val']) != '') {
                $oldTotal = round($rowBalance['Val']);
                $change = round(abs($total / $oldTotal - 1) * 100, 2);

                if ($oldTotal != 0 && $change > 10) {
                    // TODO: must delete
                    DieTrace("total changed too steep", false, 0, "old: $oldTotal, new: $total, change: $change");

                    return $oldTotal;
                }
            }

            $stmt = $connection->prepare("
				INSERT INTO 
					Param (Name, Val)
				VALUES
					('TotalBalance', :total)
				ON DUPLICATE KEY
					UPDATE Val = :total
			");
            $stmt->bindValue(':total', $total);
            $stmt->execute();
        } else {
            $total = $rowBalance['Val'];
        }

        return $total;
    }

    public function getParam($name, $default = null, $bigDataValue = false)
    {
        $valueField = $bigDataValue ? "BigData" : "Val";
        $result = $this->getEntityManager()->getConnection()->fetchColumn("select {$valueField} from Param where Name = ?", [$name]);

        if ($result === false) {
            $result = $default;
        }

        return $result;
    }

    public function setParam($name, $value, $bigDataValue = false)
    {
        $valueField = $bigDataValue ? "BigData" : "Val";
        $this->getEntityManager()
             ->getConnection()
             ->executeUpdate(
                 "insert into Param(Name, {$valueField}) values (:name, :val) on duplicate key update {$valueField} = VALUES({$valueField})",
                 ["name" => $name, "val" => $value]
             );
    }
}
