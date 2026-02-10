<?php

namespace AwardWallet\MainBundle\Worker\AsyncProcess;

use AwardWallet\MainBundle\Service\CheckerFactory;
use Doctrine\DBAL\Connection;

class ExpirationExecutor implements ExecutorInterface
{
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var CheckerFactory
     */
    private $checkerFactory;

    public function __construct(Connection $connection, CheckerFactory $checkerFactory)
    {
        $this->connection = $connection;
        $this->checkerFactory = $checkerFactory;
    }

    /**
     * @param ExpirationTask $task
     */
    public function execute(Task $task, $delay = null): Response
    {
        $categories = [];

        $providerCode = $this->connection->executeQuery("select Code from Provider where ProviderID = :providerId", ["providerId" => $task->providerId])->fetchColumn(0);
        $checker = $this->checkerFactory->getAccountChecker($providerCode);

        $columns = $checker->GetHistoryColumns();
        $combineBonus = $checker->combineHistoryBonusToMiles();
        $bonusColumn = null;

        if ($combineBonus) {
            $bonusColumn = array_search('Bonus', $columns);
        }

        $beginOfYear = $this->loadBalances("awardwallet_2015_01_01", $task->providerId);
        $beginOfYear = array_filter($beginOfYear, function ($balance) { return $balance > 0; });
        $endOfYear = $this->loadBalances("awardwallet_2015_12", $task->providerId);

        $sql = "
		select
		 	h.AccountID,
			h.Miles,
			h.Info
		from AccountHistory h
		    join Account a on h.AccountID = a.AccountID
		where
			a.ProviderID = :providerId
			and h.PostingDate >= '" . (date("Y") - 1) . "-01-01' and h.PostingDate < '" . (date("Y") - 1) . "-12-01'";
        $q = $this->connection->executeQuery($sql, ["providerId" => $task->providerId]);
        $rawData = [];
        $totals = ['Transactions' => 0, 'Accounts' => [], 'HistorySum' => 0];

        while ($row = $q->fetch(\PDO::FETCH_ASSOC)) {
            if (!empty($row['Info'])) {
                $row = array_merge($row, unserialize($row['Info']));
            }
            unset($row['Info']);
            unset($row['Position']);

            if ($combineBonus) {
                if (empty($row['Miles']) && !empty($row[$bonusColumn])) {
                    $row['Miles'] = (float) preg_replace('#[\.\,](\d{3})#ims', '$1', $row[$bonusColumn]);
                }
            }

            if (empty($row['Miles'])) {
                continue;
            }

            if (!isset($beginOfYear[$row['AccountID']]) || !isset($endOfYear[$row['AccountID']])) {
                continue;
            }

            foreach ($columns as $caption => $column) {
                if (!in_array($column, ['PostingDate', 'Description', 'Miles'])) {
                    if (!array_key_exists($caption, $row)) {
                        $row[$caption] = null;
                    } elseif (is_string($row[$caption])) {
                        $row[$caption] = strtolower($row[$caption]);
                    }
                }
            }

            if ($task->rawData) {
                $rawData[] = $row;

                if (count($rawData) >= 1000) {
                    break;
                }
            } else {
                $this->countRow($row, $totals);
            }
        }

        if ($task->rawData) {
            $historySum = [];

            foreach ($rawData as $row) {
                if (!isset($historySum[$row['AccountID']])) {
                    $historySum[$row['AccountID']] = 0;
                }
                $historySum[$row['AccountID']] += $row['Miles'];
            }

            foreach ($rawData as &$row) {
                $row['BeginBalance'] = $beginOfYear[$row['AccountID']];
                $row['EndBalance'] = $endOfYear[$row['AccountID']];
                $row['HistorySum'] = $historySum[$row['AccountID']];
                $row['Delta'] = $row['BeginBalance'] + $row['HistorySum'] - $row['EndBalance'];
            }

            $result = new SqlResponse($rawData);
            $result->columns[0] = 'AccountID';

            return $result;
        } else {
            $totals['BeginBalance'] = array_sum($beginOfYear);
            $totals['EndBalance'] = array_sum($endOfYear);
            $totals['Delta'] = $totals['BeginBalance'] + $totals['HistorySum'] - $totals['EndBalance'];
            $totals['Accounts'] = count($totals['Accounts']);
            $result = new SqlResponse([$totals]);
            $result->columns[0] = 'Transactions';

            return $result;
        }
    }

    private function loadBalances($database, $providerId)
    {
        //		$database = "awardwallet";
        $sql = "
		select
		 	a.AccountID,
			a.Balance
		from
			{$database}.Account a
		where
			a.ProviderID = :providerId";
        $q = $this->connection->executeQuery($sql, ["providerId" => $providerId]);

        return $q->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    private function countRow($row, &$total)
    {
        $total['HistorySum'] += abs($row['Miles']);
        $total['Transactions']++;
        $total['Accounts']['a' . $row['AccountID']] = true;
    }
}
