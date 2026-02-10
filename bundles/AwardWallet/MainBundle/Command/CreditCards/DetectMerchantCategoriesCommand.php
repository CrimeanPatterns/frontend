<?php

namespace AwardWallet\MainBundle\Command\CreditCards;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class DetectMerchantCategoriesCommand extends Command
{
    public static $defaultName = 'aw:credit-cards:detect-merchant-categories';
    private Connection $clickhouse;
    private OutputInterface $output;

    private Connection $connection;

    public function __construct(Connection $clickhouse, Connection $connection)
    {
        parent::__construct();
        $this->clickhouse = $clickhouse;
        $this->connection = $connection;
    }

    public function configure()
    {
        $this
            ->setDescription('detect merchant categories, basing on what credit cards earns on this merchant transactions')
            ->addOption('merchantId', null, InputOption::VALUE_REQUIRED, 'process only this merchant id')
            ->addOption('merchantLike', null, InputOption::VALUE_REQUIRED, 'process only merchants with name like this sql like expression')
            ->addOption('source', null, InputOption::VALUE_REQUIRED, 'data source, clickhouse or sql')
            ->addOption('days', null, InputOption::VALUE_REQUIRED, 'process only N last days')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;

        /**
         * 1. load all transactions of this merchant with non-empty multiplier
         * 2. map every transaction to CreditCardShoppingCategoryGroup by Multiplier + CreditCardID + ShoppingCategoryID (optional)
         * 3. fetch common ShoppingCategoryGroupID for mapped records.
         */
        $output->writeln("matching transaction to shopping category groups");
        $merchants = $this->loadMerchants($input);
        $rows = $this->loadSimpleCategoryGroupByDate($merchants, $input->getOption('days'));
        $output->writeln("matched to " . count($rows) . " rows, total " . it($rows)->map(fn ($row) => $row['Transactions'])->sum());
        $output->writeln("by date");
        $this->show($rows);
        $output->writeln("most used by date");
        $this->show($this->groupByDate($rows));

        return 0;
    }

    private function loadFromMerchantReport(array $merchantIds, ?int $days): array
    {
        if ($days !== null) {
            throw new \Exception("unsupported days filter on this method");
        }

        $sql = "select
            ccscg.ShoppingCategoryGroupID,
            scg.Name as Name,   
            sum(mr.ExpectedMultiplierTransactions) as ExpectedMultiplierTransactions,
            sum(mr.Transactions) as Transactions,
            case when sum(mr.Transactions) > 0 then sum(mr.ExpectedMultiplierTransactions) / sum(mr.Transactions) else 0 end as Ratio
        from
            MerchantReport mr
            join ShoppingCategory sc on mr.ShoppingCategoryID = sc.ShoppingCategoryID    
            join CreditCardShoppingCategoryGroup ccscg on sc.ShoppingCategoryGroupID = ccscg.ShoppingCategoryGroupID and mr.CreditCardID = ccscg.CreditCardID    
            join ShoppingCategoryGroup scg on ccscg.ShoppingCategoryGroupID = scg.ShoppingCategoryGroupID
        where
            mr.MerchantID in (:merchantIds)
            and (ccscg.StartDate is null or (ccscg.StartDate <= now() and (ccscg.EndDate is null or now() < ccscg.EndDate)))
        group by
            ccscg.ShoppingCategoryGroupID
        ";
        $this->output->writeln($sql);
        $categoryGroups = $this->connection->executeQuery($sql, ["merchantIds" => $merchantIds], ["merchantIds" => Connection::PARAM_INT_ARRAY])->fetchAll(FetchMode::ASSOCIATIVE);

        return $categoryGroups;
    }

    private function loadCategoryGroupByDate(array $merchantIds, ?int $days): array
    {
        $filters = "";

        if ($days !== null) {
            $filters .= " and ah.PostingDate >= '" . date("Y-m-d", strtotime("-{$days} day")) . "'";
        }

        $sql = "select
            formatDateTime(ah.PostingDate, '%Y-%m') as Date,
            ccscg.ShoppingCategoryGroupID as ShoppingCategoryGroupID,
            count(ah.UUID) as Transactions,
            SUM(case when toFloat64(abs(ah.Multiplier - ccscg.Multiplier)) < 0.5 then 1 else 0 end) as ExpectedMultiplierTransactions,
            case when count(ah.UUID) > 0 then SUM(case when toFloat64(abs(ah.Multiplier - ccscg.Multiplier)) < 0.5 then 1 else 0 end) / count(ah.UUID) else 0 end as Ratio
        from
            AccountHistory ah
            join SubAccount sa on ah.SubAccountID = sa.SubAccountID
            join ShoppingCategory sc on ah.ShoppingCategoryID = sc.ShoppingCategoryID
            join CreditCardShoppingCategoryGroup ccscg on sc.ShoppingCategoryGroupID = ccscg.ShoppingCategoryGroupID and ccscg.CreditCardID = sa.CreditCardID
        where
            ah.MerchantID in (:merchantIds)
            $filters
            and
            ah.Amount > 0
            and
            ah.Miles > 0
            and 
            sa.CreditCardID is not null
            and (
                /* emulate complex join condition, clickhouse does not support them */
                ccscg.ShoppingCategoryGroupID is null
                or
                ccscg.StartDate is null
                or 
                (ccscg.StartDate <= DATE(ah.PostingDate) and (ccscg.EndDate is null or DATE(ah.PostingDate) < ccscg.EndDate))
            )
        group by
            Date,
            ccscg.ShoppingCategoryGroupID
        order by
            Date,
            ccscg.ShoppingCategoryGroupID
        ";
        $this->output->writeln($sql);
        $categoryGroups = $this->clickhouse->executeQuery($sql, ["merchantIds" => $merchantIds], ["merchantIds" => Connection::PARAM_INT_ARRAY])->fetchAll(FetchMode::ASSOCIATIVE);

        return it($categoryGroups)
            ->map(function (array $row) {
                ArrayInsert($row, "ShoppingCategoryGroupID", true, ["Name" => $this->connection->executeQuery("select Name from ShoppingCategoryGroup where ShoppingCategoryGroupID = ?", [$row['ShoppingCategoryGroupID']])->fetchColumn()]);

                return $row;
            })
            ->toArray()
        ;
    }

    private function loadSimpleCategoryGroupByDate(array $merchantIds, ?int $days): array
    {
        $filters = "";

        if ($days !== null) {
            $filters .= " and ah.PostingDate >= '" . date("Y-m-d", strtotime("-{$days} day")) . "'";
        }

        $sql = "select
            formatDateTime(ah.PostingDate, '%Y-%m') as Date,
            sc.ShoppingCategoryGroupID as ShoppingCategoryGroupID,
            count(ah.UUID) as Transactions
        from
            AccountHistory ah
            join SubAccount sa on ah.SubAccountID = sa.SubAccountID
            join ShoppingCategory sc on ah.ShoppingCategoryID = sc.ShoppingCategoryID
        where
            ah.MerchantID in (:merchantIds)
            $filters
            and
            ah.Amount > 0
            and
            ah.Miles > 0
            and 
            sa.CreditCardID is not null
        group by
            Date,
            sc.ShoppingCategoryGroupID
        order by
            Date,
            sc.ShoppingCategoryGroupID
        ";
        $this->output->writeln($sql);
        $categoryGroups = $this->clickhouse->executeQuery($sql, ["merchantIds" => $merchantIds], ["merchantIds" => Connection::PARAM_INT_ARRAY])->fetchAll(FetchMode::ASSOCIATIVE);

        return it($categoryGroups)
            ->map(function (array $row) {
                ArrayInsert($row, "ShoppingCategoryGroupID", true, ["Name" => $this->connection->executeQuery("select Name from ShoppingCategoryGroup where ShoppingCategoryGroupID = ?", [$row['ShoppingCategoryGroupID']])->fetchColumn()]);

                return $row;
            })
            ->toArray()
        ;
    }

    private function loadCategoryByDate(array $merchantIds, ?int $days): array
    {
        $filters = "";

        if ($days !== null) {
            $filters .= " and ah.PostingDate >= '" . date("Y-m-d", strtotime("-{$days} day")) . "'";
        }

        $sql = "select
            formatDateTime(ah.PostingDate, '%Y-%m') as Date,
            ah.ShoppingCategoryID as ShoppingCategoryID,
            sc.ShoppingCategoryGroupID as ShoppingCategoryGroupID, 
            count(ah.UUID) as Transactions
        from
            AccountHistory ah
            join SubAccount sa on ah.SubAccountID = sa.SubAccountID
            join ShoppingCategory sc on ah.ShoppingCategoryID = sc.ShoppingCategoryID
        where
            ah.MerchantID in (:merchantIds)
            $filters
            and
            ah.Amount > 0
            and
            ah.Miles > 0
            and 
            sa.CreditCardID is not null
        group by
            Date,
            ShoppingCategoryID,
            ShoppingCategoryGroupID    
        order by
            Date, 
            ShoppingCategoryID,
            ShoppingCategoryGroupID    
        ";
        $this->output->writeln($sql);
        $result = $this->clickhouse->executeQuery($sql, ["merchantIds" => $merchantIds], ["merchantIds" => Connection::PARAM_INT_ARRAY])->fetchAll(FetchMode::ASSOCIATIVE);

        return it($result)
            ->map(function (array $row) {
                ArrayInsert($row, "ShoppingCategoryID", true, ["Name" => $this->connection->executeQuery("select Name from ShoppingCategory where ShoppingCategoryID = ?", [$row['ShoppingCategoryID']])->fetchColumn()]);

                return $row;
            })
            ->toArray()
        ;
    }

    private function loadFromSql(array $merchantIds, ?int $days): array
    {
        $filters = "";

        if ($days !== null) {
            $filters .= " and ah.PostingDate >= '" . date("Y-m-d", strtotime("-{$days} day")) . "'";
        }

        $sql = "
        select
            ccscg.ShoppingCategoryGroupID,
            scg.Name as Name,   
            count(ah.UUID) as Transactions
        from
            SubAccount sa
            join AccountHistory ah on sa.SubAccountID = ah.SubAccountID
            join CreditCardShoppingCategoryGroup ccscg on sa.CreditCardID = ccscg.CreditCardID and abs(ah.Multiplier - ccscg.Multiplier) < 0.05
            join ShoppingCategoryGroup scg on ccscg.ShoppingCategoryGroupID = scg.ShoppingCategoryGroupID
        where
            ah.MerchantID = :merchantIds
            and ((DATE(ah.PostingDate) >= ccscg.StartDate and (DATE(ah.PostingDate) < ccscg.EndDate or ccscg.EndDate is null)) or ccscg.StartDate is null)
            and ah.Multiplier > 1.1      
            $filters
            and ccscg.ShoppingCategoryGroupID not in (
                select
                    ccscg.ShoppingCategoryGroupID
                from
                    SubAccount sa
                    join AccountHistory ah on sa.SubAccountID = ah.SubAccountID
                    join CreditCardShoppingCategoryGroup ccscg on sa.CreditCardID = ccscg.CreditCardID and ccscg.Multiplier > 1.1
                    join ShoppingCategoryGroup scg on ccscg.ShoppingCategoryGroupID = scg.ShoppingCategoryGroupID
                where
                    ah.MerchantID = :merchantIds
                    and ((ah.PostingDate >= ccscg.StartDate and (ah.PostingDate < ccscg.EndDate or ccscg.EndDate is null)) or ccscg.StartDate is null)
                    and ah.Multiplier < 1.1
                    $filters
            )
        group by
            scg.Name,     
            ccscg.ShoppingCategoryGroupID            
        ";
        $this->output->writeln($sql);

        return $this->connection->executeQuery($sql, ["merchantIds" => $merchantIds], ["merchantIds" => Connection::PARAM_INT_ARRAY])->fetchAll(FetchMode::ASSOCIATIVE);
    }

    private function show(array $categoryGroups): void
    {
        if (count($categoryGroups) === 0) {
            $this->output->writeln("no data found");

            return;
        }

        $table = new Table($this->output);
        $table->setHeaders(array_keys(reset($categoryGroups)));
        $table->setRows($categoryGroups);
        $table->render();
    }

    private function loadMerchants(InputInterface $input): array
    {
        if ($like = $input->getOption('merchantLike')) {
            return $this->connection->executeQuery("select MerchantID from Merchant where Name like ?", [$like])->fetchAll(FetchMode::COLUMN);
        }

        if ($merchantId = $input->getOption('merchantId')) {
            return [$merchantId];
        }

        return [];
    }

    private function groupByDate(array $categoryGroups): array
    {
        return it($categoryGroups)
            ->reindexByColumn("Date")
            ->collapseByKey()
            ->mapIndexed(function (array $rows, string $date) {
                $total = it($rows)->map(fn ($row) => $row['Transactions'])->sum();
                $mostUsed = it($rows)
                    ->usort(function (array $a, array $b) {
                        return $b['Transactions'] <=> $a['Transactions'];
                    })
                    ->first()
                ;

                return ["Date" => $date, "Name" => $mostUsed['Name'], "Percent of transactions" => round($mostUsed['Transactions'] / $total * 100), "Transactions" => $mostUsed['Transactions']];
            })
            ->toArray()
        ;
    }
}
