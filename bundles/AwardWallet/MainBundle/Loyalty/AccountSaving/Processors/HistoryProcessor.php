<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Processors;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Globals\Updater\Engine\UpdaterEngineInterface;
use AwardWallet\MainBundle\Loyalty\AccountSaving\History\PlanLinker\Updater;
use AwardWallet\MainBundle\Loyalty\Resources\History;
use AwardWallet\MainBundle\Loyalty\Resources\HistoryColumn;
use AwardWallet\MainBundle\Loyalty\Resources\HistoryField;
use AwardWallet\MainBundle\Loyalty\Resources\HistoryRow;
use AwardWallet\MainBundle\Loyalty\Resources\ProviderInfoResponse;
use AwardWallet\MainBundle\Service\AccountHistory\MultiplierService;
use AwardWallet\MainBundle\Service\CreditCards\MerchantMatcher\MerchantMatcher;
use AwardWallet\MainBundle\Service\CreditCards\ShoppingCategoryMatcher;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Psr\Log\LoggerInterface;

class HistoryProcessor
{
    public static $historyInfoKeys = ['Info', 'Bonus'];

    /** @var LoggerInterface */
    private $logger;
    /** @var Connection */
    private $connection;
    /** @var MerchantMatcher */
    private $merchantMatcher;
    /** @var ShoppingCategoryMatcher */
    private $categoryMatcher;
    /** @var Statement */
    private $insertHistoryRowQuery;
    /** @var Statement */
    private $updateHistoryRowQuery;
    /** @var Statement */
    private $deleteHistoryRowQuery;

    /** @var Updater */
    private $planLinkUpdater;
    /** @var AccountRepository */
    private $accountRepository;
    /** @var UpdaterEngineInterface */
    private $updaterEngine;

    public function __construct(
        LoggerInterface $logger,
        Connection $connection,
        MerchantMatcher $merchantMatcher,
        ShoppingCategoryMatcher $categoryMatcher,
        Updater $planLinkUpdater,
        UpdaterEngineInterface $updaterEngine,
        AccountRepository $accountRepository
    ) {
        $this->logger = $logger;
        $this->connection = $connection;
        $this->merchantMatcher = $merchantMatcher;
        $this->categoryMatcher = $categoryMatcher;

        $sqlInsert = <<<SQL
            INSERT INTO AccountHistory (AccountID, PostingDate, Description, Miles, Info, Position, UUID, SubAccountID, Amount, AmountBalance, MilesBalance, CurrencyID, Category, MerchantID, ShoppingCategoryID, Multiplier)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
SQL;

        $this->insertHistoryRowQuery = $this->connection->prepare($sqlInsert);
        $this->updateHistoryRowQuery = $this->connection->prepare("UPDATE AccountHistory SET Position = :Position WHERE UUID = :UUID");
        $this->deleteHistoryRowQuery = $this->connection->prepare("DELETE FROM AccountHistory WHERE UUID = :UUID");
        $this->planLinkUpdater = $planLinkUpdater;
        $this->updaterEngine = $updaterEngine;
        $this->accountRepository = $accountRepository;
    }

    public static function serializeInfo($row, $infoKeys)
    {
        $InfoArray = [];
        $exist = false;

        foreach ($infoKeys as $key) {
            if (isset($row[$key])) {
                $exist = true;
                $InfoArray[$key] = $row[$key];
            } else {
                $InfoArray[$key] = '';
            }
        }

        if ($exist) {
            return @serialize($InfoArray);
        } else {
            return null;
        }
    }

    public function saveAccountHistory($accountId, History $history, $isEmailImport = false)
    {
        if (null === $history->getRange()) {
            $this->logger->info("no range, will not save history", ['accountId' => $accountId]);

            return;
        }
        /** @var Account $account */
        $account = $this->accountRepository->find($accountId);

        $fromExtension = $history->getState() === null;

        if ($fromExtension) {
            $account->setHistoryVersion($account->getProviderid()->getCacheversion());
        }

        $mainHistory = !empty($history->getRows()) ? $history->getRows() : [];
        $historyRows = $this->buildRows($mainHistory);
        $this->logger->info("main history row count: " . count($historyRows), ['accountId' => $accountId]);

        $sourceSubAccs = !empty($history->getSubAccounts()) ? $history->getSubAccounts() : [];
        $subAccHistoryRows = [];

        foreach ($sourceSubAccs as $subAcc) {
            $subAccId = $this->findSubAccId($accountId, $subAcc->getCode());
            $subAccHistoryRows[$subAccId] = $this->buildRows($subAcc->getRows());
            $this->logger->info("sub account {$subAccId} history row count: " . count($subAccHistoryRows[$subAccId]), ['accountId' => $accountId, 'subAccountId' => $subAccId]);
        }

        if (empty($historyRows) && empty($subAccHistoryRows)) {
            $this->logger->info("empty history, nothing to save", ['accountId' => $accountId]);

            return;
        }

        $providerInfo = $this->updaterEngine->getProviderInfo($account->getProviderid()->getCode());
        $historyColumns = $this->buildColumns($providerInfo);

        $this->connection->beginTransaction();

        $infoKeys = array_keys(array_intersect($historyColumns, self::$historyInfoKeys));
        $fullHistory[0] = $this->buildRowsForDb($accountId, $historyRows, $historyColumns, $infoKeys);

        foreach ($subAccHistoryRows as $subAccId => $rows) {
            $fullHistory[$subAccId] = $this->buildRowsForDb($accountId, $rows, $historyColumns, $infoKeys, $subAccId);
        }

        unset($historyRows, $subAccHistoryRows); // cleaning memory

        // processing logic
        foreach ($fullHistory as $subAccId => $subAccRows) {
            // find minDate from results
            $minDate = null;

            if (in_array($history->getRange(), [History::HISTORY_INCREMENTAL, History::HISTORY_INCREMENTAL2])) {
                foreach ($subAccRows as $hash => $row) {
                    if (!isset($minDate)) {
                        $minDate = $row['PostingDate'];
                    }
                    $minDate = strtotime($row['PostingDate']) < strtotime($minDate) ? $row['PostingDate'] : $minDate;
                }
            }

            $existing = $this->findExistingHistoryRows($accountId, $subAccId, $minDate);
            $subAccRows = $this->processSubAccHistory($subAccRows, $existing);
            $this->planLinkUpdater->update($subAccRows);

            if ($isEmailImport) { // not removing while saving email importing rows
                continue;
            }

            // removing unknown
            foreach ($existing as $hash => $row) {
                if (!array_key_exists($hash, $subAccRows)) {
                    $this->removeHistoryRow($row['UUID']);
                }
            }
        }

        $this->connection->commit();
        $this->connection->executeQuery(
            "UPDATE Account SET LastCheckHistoryDate = FROM_UNIXTIME(:TIME) WHERE AccountID = :ACCOUNTID",
            [':ACCOUNTID' => $accountId, ":TIME" => time()]
        );

        if (!$fromExtension) {
            $this->connection->executeQuery(
                "UPDATE Account SET HistoryState = :STATE WHERE AccountID = :ACCOUNTID",
                [':STATE' => $history->getState(), ':ACCOUNTID' => $accountId]
            );
        }
    }

    private function processSubAccHistory(array $rows, array $existing): array
    {
        foreach ($rows as $hash => $row) {
            if (!array_key_exists($hash, $existing)) {
                $rows[$hash]['UUID'] = $this->insertHistoryRow($row);
            } else {
                $rows[$hash]['UUID'] = $existing[$hash]['UUID'];

                if ($existing[$hash]['Position'] != $row['Position']) {
                    $this->updateHistoryRow($existing[$hash]['UUID'], $row['Position']);
                }
            }
        }

        return $rows;
    }

    /**
     * @param array $row prepared by this::buildRowsForDb method
     */
    private function insertHistoryRow(array $row): string
    {
        $orderedColumns = [
            'AccountID', 'PostingDate', 'Description', 'Miles', 'Info', 'Position', 'UUID', 'SubAccountID', 'Amount',
            'AmountBalance', 'MilesBalance', 'CurrencyID', 'Category', 'MerchantID', 'ShoppingCategoryID', 'Multiplier',
        ];

        $result = null;

        $params = [];

        foreach ($orderedColumns as $order => $column) {
            if ($column === 'UUID') {
                $params[$order] = StringHandler::uuid();
                $result = $params[$order];

                continue;
            }

            if (in_array($column, ['AccountID', 'SubAccountID', 'CurrencyID', 'MerchantID', 'ShoppingCategoryID'])) {
                $params[$order] = !empty($row[$column]) ? $row[$column] : null;
            } else {
                $params[$order] = $row[$column] ?? null;
            }
        }

        $merchantIdIndex = array_search('MerchantID', $orderedColumns);
        $try = 0;
        $executed = false;
        $maxRetries = 3;

        while ($try < $maxRetries && !$executed) {
            try {
                $this->insertHistoryRowQuery->execute($params);
                $executed = true;
            } catch (ForeignKeyConstraintViolationException $e) {
                $this->logger->warning(
                    "History row inserting exception",
                    ['exception_message' => $e->getMessage(), 'exception_class' => get_class($e)]
                );
                $this->connection->commit();
                $this->connection->beginTransaction();

                if (!empty($params[$merchantIdIndex]) && strpos($e->getMessage(), 'AccountHistory_ibfk_2')) {
                    $params[$merchantIdIndex] = $this->merchantMatcher->identify(
                        $params[array_search('Description', $orderedColumns)],
                        $params[array_search('ShoppingCategoryID', $orderedColumns)],
                        false
                    );
                } else {
                    throw $e;
                }

                if ($try >= ($maxRetries - 1)) {
                    throw $e;
                }
            } finally {
                $try++;
            }
        }

        return $result;
    }

    /**
     * @param string $uuid
     * @param int $position
     */
    private function updateHistoryRow($uuid, $position)
    {
        $this->updateHistoryRowQuery->execute([":Position" => (int) $position, ":UUID" => $uuid]);
    }

    /**
     * @param string $uuid
     */
    private function removeHistoryRow($uuid)
    {
        $this->logger->info("deleting history row " . $uuid);
        $this->deleteHistoryRowQuery->execute([":UUID" => $uuid]);
    }

    /**
     * @return array
     */
    private function buildRows(array $rows)
    {
        $result = [];

        if (empty($rows)) {
            return $result;
        }

        /** @var HistoryRow $row */
        foreach ($rows as $i => $row) {
            if (empty($row->getFields())) {
                continue;
            }

            /** @var HistoryField $field */
            foreach ($row->getFields() as $field) {
                if (is_string($field->getValue()) && trim($field->getValue()) === '') {
                    continue;
                }
                $result[$i][$field->getName()] = $field->getValue();
            }
        }

        return $result;
    }

    private function buildColumns(ProviderInfoResponse $providerInfo): array
    {
        $columns = $providerInfo->getHistorycolumns();

        $result = [];

        /** @var HistoryColumn $column */
        foreach ($columns as $column) {
            $result[$column->getName()] = $column->getKind();
        }

        return $result;
    }

    /**
     * returns array ['sha1-hash' => [fields exactly like in Database]].
     *
     * @param int $accountId
     * @param int|null $subAccId
     * @return array
     */
    private function buildRowsForDb($accountId, array $rows, array $columns, array $infoKeys, $subAccId = null)
    {
        $theSame = [];
        $result = [];

        foreach ($rows as $i => $row) {
            $dbRow = [
                'AccountID' => $accountId,
                'SubAccountID' => $subAccId,
                'Info' => self::serializeInfo($row, $infoKeys),
                'Description' => null,
                'Miles' => null,
                'Position' => $i,
                'Amount' => null,
                'AmountBalance' => null,
                'MilesBalance' => null,
                'Category' => null,
                'Multiplier' => null,
            ];

            foreach ($row as $name => $value) {
                if (!isset($columns[$name])) {
                    $this->logger->critical("Unknown column name: " . $name, ['accountId' => $accountId]);

                    continue;
                }

                switch ($columns[$name]) {
                    case 'PostingDate':
                        if (date('Y-m-d', strtotime($value)) === $value) {
                            $dbRow[$columns[$name]] = $value;
                        } else {
                            $dbRow[$columns[$name]] = date('Y-m-d H:i:s', $value);
                        }

                        break;

                    case 'Miles':
                        $dbRow[$columns[$name]] = filterBalance($value, true);

                        break;

                    case 'Currency':
                        $dbRow['CurrencyID'] = $this->findCurrencyIdByCode($value);

                        break;

                    case 'Amount':
                    case 'AmountBalance':
                    case 'MilesBalance':
                        $dbRow[$columns[$name]] = (float) $value;

                        break;

                    case 'Description':
                    case 'Category':
                        $dbRow[$columns[$name]] = $value;

                        break;
                }
            }

            if (empty($dbRow["PostingDate"])) {
                //                $this->logger->critical('History row empty PostingDate', ['accountId' => $accountId, 'subAccId' => $subAccId, 'historyRow' => $row]);
                continue;
            }

            if ((int) $subAccId > 0 && abs($dbRow["Amount"]) > 0) {
                $accountRow = $this->connection->executeQuery("SELECT ProviderID FROM Account WHERE AccountID = :AccountID", [":AccountID" => $accountId])->fetch();
                $providerId = (int) $accountRow['ProviderID'];
                $dbRow["ShoppingCategoryID"] = $this->categoryMatcher->identify($dbRow["Category"], $providerId);
                $dbRow["MerchantID"] = $this->merchantMatcher->identify($dbRow["Description"], $dbRow["ShoppingCategoryID"]);
                $dbRow["Multiplier"] = MultiplierService::calculate((float) $dbRow['Amount'], (float) $dbRow['Miles'], $providerId);
            }

            $hash = $this->processHash($dbRow, $theSame);
            $result[$hash] = $dbRow;
        }

        return $result;
    }

    /**
     * returns array ['sha1-hash' => [Database fields]].
     *
     * @param int $accountId
     * @param int|null $subAccId
     * @param string|null $startDate MySQL datetime formatted
     * @return array
     */
    private function findExistingHistoryRows($accountId, $subAccId = null, $startDate = null)
    {
        $sql = "SELECT * FROM AccountHistory WHERE AccountID = :AccountID";
        $params = [":AccountID" => $accountId];

        if (isset($subAccId) && $subAccId > 0) {
            $sql .= " AND SubAccountID = :SubAccountID";
            $params[":SubAccountID"] = $subAccId;
        } else {
            $sql .= " AND SubAccountID IS NULL";
        }

        if (isset($startDate)) {
            $sql .= " AND PostingDate >= :StartDate";
            $params[":StartDate"] = $startDate;
        }

        $orderBy = " ORDER BY PostingDate DESC, Position ASC";
        $rows = $this->connection->executeQuery($sql . $orderBy, $params)->fetchAll();
        $result = [];

        if (empty($rows)) {
            return $result;
        }

        $theSame = [];

        foreach ($rows as $row) {
            $hash = $this->processHash($row, $theSame);
            $result[$hash] = $row;
        }

        return $result;
    }

    /**
     * @param array $row db compatible row
     * @param array $theSame the same hashes link
     * @return string processed hash
     */
    private function processHash(array $row, array &$theSame)
    {
        $hashData = implode('-', [
            $row['PostingDate'],
            $row['Description'],
            $row['Miles'],
            $row['Info'],
            $row['Category'],
            $row['SubAccountID'],
        ]);
        $hash = sha1($hashData);

        if (isset($theSame[$hash])) {
            $resultHash = sha1($hashData . $theSame[$hash]);
            ++$theSame[$hash];
        } else {
            $resultHash = $hash;
            $theSame[$hash] = 1;
        }

        return $resultHash;
    }

    /**
     * @param int $accountId
     * @param string $code
     * @return int
     */
    private function findSubAccId($accountId, $code)
    {
        $sql = <<<SQL
            SELECT SubAccountID FROM SubAccount
            WHERE AccountID = :AccountID AND Code = :Code
SQL;

        $result = $this->connection->executeQuery($sql, [":AccountID" => $accountId, ":Code" => $code])->fetch();

        if (empty($result)) {
            return null;
        }

        return (int) $result['SubAccountID'];
    }

    /**
     * @param string $code
     * @return int
     */
    private function findCurrencyIdByCode($code)
    {
        $sql = "SELECT CurrencyID FROM Currency WHERE Code = :Code";

        $result = $this->connection->executeQuery($sql, [":Code" => $code])->fetch();

        if (empty($result)) {
            return null;
        }

        return (int) $result['CurrencyID'];
    }
}
