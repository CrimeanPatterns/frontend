<?php

namespace AwardWallet\MainBundle\Command\CreditCards;

use AwardWallet\MainBundle\Service\ProgressLogger;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\TransactionIsolationLevel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtAssoc;

class ReportBuilderCommand extends Command
{
    public const INSERT_PACKAGE_SIZE = 30;
    public const DUMP_STATUS_TIME = 30;

    public static $defaultName = 'aw:credit-cards:report-builder';
    /** @var LoggerInterface */
    private $logger;
    /** @var Connection */
    private $mainConnection;
    /** @var Connection */
    private $replicaConnection;
    /** @var Connection */
    private $clickhouse;
    /** @var ProgressLogger */
    private $progressLogger;

    public function __construct(
        LoggerInterface $logger,
        Connection $mainConnection,
        Connection $unbufConnection,
        Connection $clickhouse
    ) {
        parent::__construct();

        $this->logger = $logger;
        $this->mainConnection = $mainConnection;
        $this->replicaConnection = $unbufConnection;
        $this->progressLogger = new ProgressLogger($this->logger, 100, self::DUMP_STATUS_TIME);
        $this->clickhouse = $clickhouse;
    }

    protected function configure()
    {
        $this
            ->addOption('build-multipliers', null, InputOption::VALUE_NONE)
            ->addOption('category', null, InputOption::VALUE_NONE)
            ->addOption('similar', null, InputOption::VALUE_NONE)
            ->addOption('dry-run', null, InputOption::VALUE_NONE)
            ->addOption('package', null, InputOption::VALUE_OPTIONAL, "Update package size", self::INSERT_PACKAGE_SIZE)
            ->addOption("target-version", null, InputOption::VALUE_REQUIRED, "target report version, for tests")
            ->addOption("merchantId", null, InputOption::VALUE_REQUIRED, "limit to this merchant")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', '8G');
        $buildCategoryReport = $input->getOption('category');
        $buildSimilarMerchantsReport = $input->getOption('similar');
        $dryRun = $input->getOption('dry-run');
        $package = $input->getOption('package');

        $this->logger->info("Update package size: " . $package);

        $this->replicaConnection->setTransactionIsolation(TransactionIsolationLevel::READ_UNCOMMITTED);

        if ($dryRun) {
            $this->logger->info("dry run");
        }

        if ($input->getOption('build-multipliers')) {
            $this->saveMultipliers($this->buildMultipliers(), $dryRun);
        }

        if ($buildCategoryReport) {
            $this->mainConnection->executeUpdate("DELETE FROM MasterSlaveCategoryReport");
            $this->buildCategoryReport();
        }

        if ($buildSimilarMerchantsReport) {
            $this->recountSimilarMerchants($dryRun);
        }

        $this->logger->info("Memory usage", [
            'memory_MB' => round(memory_get_usage() / (1024 * 1024), 2),
        ]);

        return 0;
    }

    private function buildMultipliers(): array
    {
        /* multipliers */
        $this->logger->info("building multipliers");
        $sql = "
select 
       concat(toString(CreditCardID), '_', toString(ShoppingCategoryID), '_', RealMultiplier) as Key,
       Transactions
from (
    select 
        s.CreditCardID as CreditCardID, 
        ifNull(h.ShoppingCategoryID, 0) as `ShoppingCategoryID`, 
        toString(toDecimal32(h.Multiplier, 1)) as RealMultiplier, 
        count(*) as Transactions
    from AccountHistory h 
        join SubAccount s on h.SubAccountID = s.SubAccountID
    where s.CreditCardID is not null
    and h.MerchantID is not null
    and h.Amount > 0
    and toDecimal64(h.Miles, 4) > 0
    group by s.CreditCardID, h.ShoppingCategoryID, RealMultiplier
) h
";

        return stmtAssoc($this->clickhouse->executeQuery($sql))
            ->reindexByColumn('Key')
            ->map(function ($row) {
                return (int) $row["Transactions"];
            })->toArrayWithKeys();
    }

    private function saveMultipliers(array $multipliers, $dryRun)
    {
        $this->logger->info("loading ShoppingCategoryMultiplier");
        $existing = $this->replicaConnection->executeQuery(
            "select 
                concat(CreditCardID, '_', ShoppingCategoryID, '_', Multiplier) as UniqueKey,
                Transactions 
            from 
                ShoppingCategoryMultiplier"
        )->fetchAll(\PDO::FETCH_KEY_PAIR);
        $existing = array_map("intval", $existing);

        // добавляем все полученные сочетания в таблицу ShoppingCategoryMultiplier
        $updateStmt = $this->mainConnection->prepare("
              UPDATE ShoppingCategoryMultiplier SET Transactions = :Transactions
              WHERE CreditCardID = :CreditCardID AND ShoppingCategoryID = :ShoppingCategoryID AND Multiplier = :Multiplier
              LIMIT 1
        ");
        $deleteStmt = $this->mainConnection->prepare("
              DELETE FROM ShoppingCategoryMultiplier 
              WHERE CreditCardID = :CreditCardID AND ShoppingCategoryID = :ShoppingCategoryID AND Multiplier = :Multiplier
              LIMIT 1
        ");
        $insertStmt = $this->mainConnection->prepare("
              INSERT IGNORE INTO ShoppingCategoryMultiplier (CreditCardID, ShoppingCategoryID, Multiplier, Transactions)
              VALUES (:CreditCardID, :ShoppingCategoryID, :Multiplier, :Transactions)
        ");

        $count = 0;
        $this->mainConnection->beginTransaction();
        $updated = 0;
        $inserted = 0;
        $this->logger->info("saving ShoppingCategoryMultiplier, " . count($multipliers) . " records");

        foreach ($multipliers as $key => $transactions) {
            $values = explode("_", $key);

            if (is_array($values) && count($values) === 3) {
                [$creditCardId, $shoppingCategoryId, $multiplier] = $values;
            } else {
                $this->logger->notice('Undefined Multipliers Row', ['key' => $key, 'data' => $transactions]);

                continue;
            }

            $this->progressLogger->showProgress("saving multipliers", $count);

            $params = [
                ":CreditCardID" => $creditCardId,
                ":ShoppingCategoryID" => $shoppingCategoryId,
                ":Multiplier" => $multiplier,
                ":Transactions" => $transactions,
            ];

            if (!isset($existing[$key])) {
                if (!$dryRun) {
                    $insertStmt->execute($params);
                }
                $inserted++;
            } elseif ($existing[$key] !== (int) $transactions) {
                if (!$dryRun) {
                    $updateStmt->execute($params);
                }
                $updated++;
            }
            unset($existing[$key]);

            $count++;

            if (!$dryRun && ($count % 100) == 0) {
                $this->mainConnection->commit();
                $this->mainConnection->beginTransaction();
            }
        }
        $this->mainConnection->commit();

        $this->logger->info("deleting " . count($existing) . " ShoppingCategoryMultiplier records");
        $count = 0;
        $deleted = 0;

        $this->mainConnection->beginTransaction();

        foreach ($existing as $key => $transactions) {
            $this->progressLogger->showProgress("deleting from ShoppingCategoryMultiplier", $count);
            [$creditCardId, $shoppingCategoryId, $multiplier] = explode("_", $key);

            if (!$dryRun) {
                $deleteStmt->execute([
                    "CreditCardID" => $creditCardId,
                    "ShoppingCategoryID" => $shoppingCategoryId,
                    "Multiplier" => $multiplier,
                ]);
            }

            $deleted++;
            $count++;

            if (!$dryRun && ($count % 100) == 0) {
                $this->mainConnection->commit();
                $this->mainConnection->beginTransaction();
            }
        }

        $this->mainConnection->commit();
        $this->logger->info("ShoppingCategoryMultiplier updated. $count rows processed, updated: $updated, inserted: $inserted, deleted: $deleted.");
    }

    private function buildCategoryReport()
    {
        $this->logger->info("Select rows MasterSlaveCategoryReport");
        $result = $this->replicaConnection->executeQuery("
            SELECT MerchantID, ShoppingCategoryID
            FROM AccountHistory
            WHERE ShoppingCategoryID IS NOT NULL
            AND MerchantID IS NOT NULL
            AND PostingDate >= DATE_SUB(NOW(),INTERVAL 1 YEAR)
        ");

        $count = 0;
        $merchants = [];
        $categories = [];

        while ($row = $result->fetch()) {
            $this->progressLogger->showProgress("building category report", $count);

            $mId = intval($row["MerchantID"]);
            $cId = intval($row["ShoppingCategoryID"]);

            /* заполнение всех возможных мерчантов для каждой категории */
            if (!isset($categories[$cId])) {
                $categories[$cId] = [];
            }

            if (!in_array($mId, $categories[$cId])) {
                $categories[$cId][] = $mId;
            }

            if (!isset($merchants[$mId])) {
                $merchants[$mId] = [];
            }

            if (!in_array($cId, $merchants[$mId])) {
                $merchants[$mId][] = $cId;
            }
            $count++;
        }
        unset($cId, $mId, $count);

        $insertStmt = $this->mainConnection->prepare("
            INSERT INTO MasterSlaveCategoryReport (MasterCategoryID, SlaveCategoryID)
            VALUES (:MasterCategoryID, :SlaveCategoryID)
            ON DUPLICATE KEY UPDATE Counter = Counter + 1;
        ");

        $count = 0;
        $this->mainConnection->beginTransaction();

        foreach ($categories as $masterId => $categoryMerchants) {
            foreach ($categoryMerchants as $mId) {
                foreach ($merchants[$mId] as $slaveId) {
                    if ($masterId === $slaveId) {
                        continue;
                    }

                    $insertStmt->execute([
                        ":MasterCategoryID" => $masterId,
                        ":SlaveCategoryID" => $slaveId,
                    ]);
                    $count++;

                    if (($count % 100) == 0) {
                        $this->mainConnection->commit();
                        $this->mainConnection->beginTransaction();
                    }
                }
            }
        }
        $this->mainConnection->commit();
        $this->logger->info("Done, $count rows processed.");
    }

    private function recountSimilarMerchants(bool $dryRun)
    {
        $this->logger->notice("Counting similar merchants...");

        $sql = "
            SELECT MerchantID, CONCAT(TRIM(REPLACE(Name, '#', '')), '%') AS Name, Similar FROM Merchant m
            WHERE TRIM(REPLACE(Name, '#', '')) <> ''
        ";
        $result = $this->replicaConnection->executeQuery($sql);

        $updateStmt = $this->mainConnection->prepare(
            "UPDATE Merchant SET Similar = :Similar WHERE MerchantID = :MerchantID"
        );

        $count = 0;
        $updated = 0;

        while ($primaryRow = $result->fetch()) {
            $this->progressLogger->showProgress("counting similar merchants", $count);

            //            $similar = 0;
            //            foreach ($result as $secondaryRow) {
            //                if ($primaryRow['MerchantID'] == $secondaryRow['MerchantID']) {
            //                    continue;
            //                }
            //
            //                if (stripos($secondaryRow['Name'], $primaryRow['Name']) !== false) {
            //                    $similar++;
            //                }
            //            }
            $similar = $this->mainConnection->executeQuery(
                "SELECT count(*) FROM Merchant WHERE Name LIKE :Name",
                [':Name' => $primaryRow['Name']]
            )->fetchColumn(0);
            $count++;

            if ((int) $primaryRow === (int) $similar) {
                continue;
            }

            if (!$dryRun) {
                $updateStmt->execute([
                    ":Similar" => $similar,
                    ":MerchantID" => (int) $primaryRow['MerchantID'],
                ]);
            }
            $updated++;
        }

        $this->logger->info("Counting similar merchants Done. $count rows processed. $updated rows updated.");
    }

    private function primaryKey(array $row): string
    {
        return "{$row['MerchantID']}_{$row['CreditCardID']}_" . (int) $row['ShoppingCategoryID'];
    }
}
