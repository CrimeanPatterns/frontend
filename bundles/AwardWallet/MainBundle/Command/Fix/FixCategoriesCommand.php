<?php

namespace AwardWallet\MainBundle\Command\Fix;

use AwardWallet\Common\Doctrine\BatchUpdater;
use AwardWallet\MainBundle\Service\AccountHistory\MultiplierService;
use AwardWallet\MainBundle\Service\CreditCards\MerchantMatcher\MerchantMatcher;
use AwardWallet\MainBundle\Service\CreditCards\ShoppingCategoryMatcher;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class FixCategoriesCommand extends Command
{
    private const UPDATE_FIELDS = ["Category", "ShoppingCategoryID", "MerchantID", "Multiplier"];

    private const CATEGORY_MAP = [
        'all purchases' => null,
        'all other purchases' => null,
    ];
    public static $defaultName = 'aw:fix:categories';

    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var Connection
     */
    private $clickhouse;
    /**
     * @var MerchantMatcher
     */
    private $merchantMatcher;
    /**
     * @var ShoppingCategoryMatcher
     */
    private $categoryMatcher;
    /**
     * @var Connection
     */
    private $unbufConnection;

    public function __construct(
        Connection $connection,
        Connection $clickhouse,
        Connection $unbufConnection,
        MerchantMatcher $merchantMatcher,
        ShoppingCategoryMatcher $categoryMatcher
    ) {
        parent::__construct();

        $this->connection = $connection;
        $this->clickhouse = $clickhouse;
        $this->merchantMatcher = $merchantMatcher;
        $this->categoryMatcher = $categoryMatcher;
        $this->unbufConnection = $unbufConnection;
    }

    public function configure()
    {
        $this
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'limit')
            ->addOption('apply', null, InputOption::VALUE_NONE, 'apply new category, otherwise dry run')
            ->addOption('change-category', null, InputOption::VALUE_NONE, 'apply new categories')
            ->addOption('print', null, InputOption::VALUE_NONE, 'show found records')
            ->addOption('where', null, InputOption::VALUE_REQUIRED, 'where filter', "h.Category = 'earned on all purchases' and a.ProviderID = 87")
            ->addOption('orderBy', null, InputOption::VALUE_REQUIRED, 'order by, for example "h.PostingDate DESC"')
            ->addOption('backup', null, InputOption::VALUE_REQUIRED, 'backup modified UUIDs list to file')
            ->addOption('source', null, InputOption::VALUE_REQUIRED, 'data source: "clickhouse" or "mysql"', "clickhouse");
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("searching for categories");
        $where = $input->getOption('where');

        $fields = "h.UUID as UUID, 
            h.PostingDate as PostingDate, 
            h.AccountID as AccountID, 
            h.SubAccountID as SubAccountID, 
            h.ShoppingCategoryID as ShoppingCategoryID, 
            h.MerchantID as MerchantID, 
            a.ProviderID as ProviderID, 
            h.Multiplier as Multiplier, 
            h.Amount as Amount,
            h.Miles as Miles";

        $source = $input->getOption('source');

        if ($source === 'mysql') {
            $fields .= ", h.Description, h.Category";
        }

        $sql = "select 
            $fields
        from 
            AccountHistory h 
            join SubAccount sa on h.SubAccountID = sa.SubAccountID 
            join Account a on sa.AccountID = a.AccountID 
        where
            $where";

        if ($orderBy = $input->getOption('orderBy')) {
            $sql .= " order by " . $orderBy;
        }

        if ($limit = $input->getOption('limit')) {
            $sql .= " limit " . (int) $limit;
        }

        $apply = $input->getOption('apply');
        $print = $input->getOption('print');
        $backup = $input->getOption('backup');
        $changeCategory = $input->getOption("change-category");

        $updateSql = "update AccountHistory set MerchantID = :NewMerchantID, Multiplier = :NewMultiplier";

        if ($changeCategory !== null) {
            $updateSql .= ", Category = :NewCategory, ShoppingCategoryID = :NewShoppingCategoryID";
        }

        $updateSql .= " where UUID = :UUID limit 1";

        $output->writeln($sql);

        if ($source === "clickhouse") {
            $output->writeln("reading from clickhouse");
            $q = $this->clickhouse->executeQuery($sql);
        } else {
            $output->writeln("reading from mysql");
            $q = $this->unbufConnection->executeQuery($sql);
        }

        $output->writeln("query opened, processing");
        $batcher = new BatchUpdater($this->connection);
        $total = 0;
        $fixed = 0;

        $chain = it($q)
            ->onNthMillis(10000, function ($time, $ticksCounter, $value, $key) use ($output, &$fixed) {
                $output->writeln("processed " . number_format($ticksCounter, 0) . " records in " . number_format($time / 1000, 0) . " seconds, fixed: {$fixed}..");
            })
            ->onEach(function () use (&$total) {
                $total++;
            })
        ;

        if ($source === 'clickhouse') {
            $output->writeln("will load extra info from mysql");
            $chain = $chain
                ->chunk(50)
                ->flatMap([$this, "loadRowsInfo"])
            ;
        }

        if ($changeCategory) {
            $output->writeln("filtering by category");
            $chain = $chain
                ->map(function (array $row) {
                    $newCategory = preg_replace('#^earned\s+#ims', '', $row['Category']);
                    $newCategory = preg_replace('#^on\s+#ims', '', $newCategory);
                    $newCategory = str_replace(' - Blue Business Plus 2X', '', $newCategory);

                    if (array_key_exists($newCategory, self::CATEGORY_MAP)) {
                        $newCategory = self::CATEGORY_MAP[$newCategory];
                    }

                    if ($newCategory !== null && trim($newCategory) === '') {
                        $newCategory = null;
                    }

                    $row['NewCategory'] = $newCategory;

                    return $row;
                })
                ->filter(function (array $row) {
                    return $row['NewCategory'] !== $row['Category'];
                })
                ->map(function (array $row) {
                    $row['NewShoppingCategoryID'] = $row['NewCategory'] ? $this->categoryMatcher->identify($row['NewCategory'], $row['ProviderID']) : null;

                    return $row;
                })
            ;
        }

        $chain = $chain
            ->map(function (array $row) {
                $row['NewMerchantID'] = !empty($row["Description"]) ? $this->merchantMatcher->identify($row["Description"], $row['NewShoppingCategoryID'] ?? $row['ShoppingCategoryID']) : null;
                $row['NewMultiplier'] = MultiplierService::calculate((float) $row['Amount'], (float) $row["Miles"], $row['ProviderID']);

                return $row;
            })
        ;

        if ($print) {
            $chain = $chain
                ->onEach(function (array $row) use ($output) {
                    $s = "{$row["UUID"]}: {$row["PostingDate"]} {$row['ProviderID']} {$row["Description"]} \$" . number_format($row["Amount"], 2);

                    foreach (self::UPDATE_FIELDS as $field) {
                        if (array_key_exists("New" . $field, $row) && $row["New" . $field] != $row[$field]) {
                            $s .= ", {$field}: {$row[$field]} -> {$row["New" . $field]}";
                        }
                    }
                    $output->writeln($s);
                })
            ;
        }

        if ($backup) {
            $output->writeln("backing up to {$backup}");
            $file = fopen($backup, "wb");

            if ($file === false) {
                throw new \Exception("failed to open file $backup");
            }

            $chain = $chain
                ->onEach(function (array $row) use ($file) {
                    if (fwrite($file, $row['UUID'] . "\t" . $row['Category'] . "\t" . $row['ShoppingCategoryID'] . "\t" . $row['MerchantID'] . "\t" . $row['Multiplier'] . "\n") === false) {
                        throw new \Exception("failed to write");
                    }
                })
                ->finally(function () use ($file, $output) {
                    if (fclose($file) === false) {
                        throw new \Exception("failed to close file");
                    }

                    $output->writeln("backup saved");
                });
        }

        $chain = $chain->onEach(function () use (&$fixed) {
            $fixed++;
        });

        if ($apply) {
            $chain = $chain
                ->map(function (array $row) {
                    return array_intersect_key($row, array_merge(array_flip(array_map(function (string $field) {
                        return "New" . $field;
                    }, self::UPDATE_FIELDS)), ['UUID' => true]));
                })
                ->chunk(50)
                ->flatMap(function (array $rows) use ($batcher, $updateSql) {
                    $batcher->batchUpdate($rows, $updateSql, 0);

                    return $rows;
                });
        }

        $chain->count();

        $output->writeln("done, processed {$total} rows, fixed: {$fixed}");

        return 0;
    }

    public function loadRowsInfo(array $rows): array
    {
        $uuids = it($rows)->map(function (array $row) { return $row['UUID']; })->toArray();
        $q = $this->connection->executeQuery(
            "select 
                h.UUID, h.Description, h.Category 
            from 
                AccountHistory h
            where 
                h.UUID in (?)",
            [$uuids],
            [Connection::PARAM_STR_ARRAY]
        );
        $extraRows = $q->fetchAll(FetchMode::ASSOCIATIVE);
        $extraRows = it($extraRows)->reindex(function (array $row) { return $row['UUID']; })->toArrayWithKeys();

        return
            it($rows)
                ->map(function (array $row) use ($extraRows) {
                    $extra = $extraRows[$row['UUID']] ?? ['Description' => '', 'Category' => null];
                    $row = array_merge($row, $extra);

                    return $row;
                })
                ->toArray();
    }
}
