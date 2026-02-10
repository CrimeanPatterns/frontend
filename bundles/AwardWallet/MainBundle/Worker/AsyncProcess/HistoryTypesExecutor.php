<?php

namespace AwardWallet\MainBundle\Worker\AsyncProcess;

use AwardWallet\MainBundle\Service\CheckerFactory;
use Doctrine\DBAL\Connection;

class HistoryTypesExecutor implements ExecutorInterface
{
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var CheckerFactory
     */
    private $checkerFactory;

    public function __construct(Connection $unbufConnection, CheckerFactory $checkerFactory)
    {
        $this->connection = $unbufConnection;
        $this->checkerFactory = $checkerFactory;
    }

    /**
     * @param HistoryTypesTask $task
     */
    public function execute(Task $task, $delay = null): Response
    {
        $categories = [];

        $providerCode = $this->connection->executeQuery("select Code from Provider where ProviderID = :providerId", ["providerId" => $task->providerId])->fetchColumn(0);
        $checker = $this->checkerFactory->getAccountChecker($providerCode);

        $columns = $checker->GetHistoryColumns();
        $combineBonus = $checker->combineHistoryBonusToMiles() && !$task->redemption;
        $bonusColumn = null;

        if ($combineBonus) {
            $bonusColumn = array_search('Bonus', $columns);
        }

        $sql = "
		select
			h.*
		from
			AccountHistory h
			join Account a on h.AccountID = a.AccountID
		where
			a.ProviderID = :providerId
			" . ($combineBonus ? "" : "and h.Miles " . ($task->redemption ? "<" : ">") . " 0") . "
			and h.PostingDate >= '" . (date("Y") - 1) . "-01-01' and h.PostingDate < '" . date("Y") . "-01-01'";
        $q = $this->connection->executeQuery($sql, ["providerId" => $task->providerId]);
        $rawData = [];
        $totals = ['Category' => '', 'Points' => 0, 'Transactions' => 0, 'Accounts' => []];

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

                if (
                    empty($row['Miles'])
                    || ($task->redemption && $row['Miles'] > 0)
                    || (!$task->redemption && $row['Miles'] < 0)
                ) {
                    continue;
                }
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

            $category = call_user_func($task->mapper, $row);

            if ($category === null) {
                continue;
            }

            if ($task->rawData) {
                if (empty($task->filter) || $task->filter == $category) {
                    $rawData[] = $row;

                    if (count($rawData) >= 1000) {
                        break;
                    }
                }
            } else {
                if (!isset($categories[$category])) {
                    $categories[$category] = ['Category' => $category, 'Points' => 0, 'Transactions' => 0, 'Accounts' => []];
                }

                $this->countRow($row, $categories[$category]);
                $this->countRow($row, $totals);
            }
        }

        if ($task->rawData) {
            $result = new SqlResponse($rawData);
            $result->columns[0] = 'AccountID';

            return $result;
        } else {
            uasort($categories, function ($a, $b) {
                return $b['Points'] - $a['Points'];
            });
            $categories['Total'] = $totals;

            foreach ($categories as &$category) {
                $category['Accounts'] = count($category['Accounts']);
            }

            $result = new SqlResponse(array_values($categories));
            $result->columns[0] = $task->firstColumnTitle;

            return $result;
        }
    }

    private function countRow($row, &$total)
    {
        $total['Points'] += abs($row['Miles']);
        $total['Transactions']++;
        $total['Accounts']['a' . $row['AccountID']] = true;
    }
}
