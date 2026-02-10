<?php

namespace AwardWallet\MainBundle\Service\CreditCards\Commands;

use AwardWallet\MainBundle\Entity\Repositories\ParameterRepository;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Globals\Utils\BinaryLogger\BinaryLoggerFactory;
use AwardWallet\MainBundle\Globals\Utils\BinaryLogger\LoggerInterfaceCallableAdapder;
use AwardWallet\MainBundle\Service\CreditCards\Commands\Helpers\SnapshotTable;
use AwardWallet\MainBundle\Service\CreditCards\Commands\Helpers\SnapshotTablesEnumerator;
use Clock\ClockInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\TransactionIsolationLevel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function Duration\seconds;

/**
 * @psalm-import-type SnapshotTable from SnapshotTablesEnumerator
 */
class MerchantTransactionsHelpersCommand extends Command
{
    public const TABLE_SUFFIX_DATE_FORMAT = 'YmdHis';
    private const MODE_CREATE = 'create';
    private const MODE_UPDATE = 'update';
    protected static $defaultName = 'aw:merchant:transactions:examples';
    private Connection $dbConnection;
    private ClockInterface $clock;
    private ParameterRepository $parameterRepository;
    private Connection $replicaConnection;
    private SnapshotTablesEnumerator $snapshotTablesEnumerator;
    private BinaryLoggerFactory $check;

    public function __construct(
        Connection $dbConnection,
        Connection $replicaConnection,
        ClockInterface $clock,
        ParameterRepository $paramRepository,
        SnapshotTablesEnumerator $snapshotTablesEnumerator
    ) {
        parent::__construct();
        $this->dbConnection = $dbConnection;
        $this->clock = $clock;
        $this->parameterRepository = $paramRepository;
        $this->replicaConnection = $replicaConnection;
        $this->snapshotTablesEnumerator = $snapshotTablesEnumerator;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->check = (new BinaryLoggerFactory(
            new LoggerInterfaceCallableAdapder(fn (int $level, string $message, array $context = []) =>
                $output->writeln("{$message} " . \json_encode($context, JSON_PRETTY_PRINT)))
        ))->uppercaseInfix();
        $mode = $input->getArgument('mode');

        if (self::MODE_UPDATE === $mode) {
            $merchantsSuffix = $this->parameterRepository->getParam(ParameterRepository::MERCHANT_EXAMPLES_DATE);

            if (StringUtils::isEmpty($merchantsSuffix)) {
                $output->writeln('No suffix found for update mode');

                return 1;
            }

            $output->writeln("Updating MerchantRematchTransactionsExamples{$merchantsSuffix} table with Descriptions from AccountHistory...");
            $this->fillMerchantTables(
                $output,
                new SnapshotTable("MerchantRematchTransactionsExamples{$merchantsSuffix}", SnapshotTablesEnumerator::extractDate($merchantsSuffix)),
                (int) $input->getOption('transactions-by-merchant-limit'),
                (int) $input->getOption('update-limit'),
                (int) $input->getOption('update-seconds')
            );

            return 0;
        } elseif (self::MODE_CREATE === $mode) {
            $noMerchants = $input->getOption('no-merchants');
            $noTransactions = $input->getOption('no-transactions');
            $current = $this->clock->current()->getAsDateTimeImmutable();

            if ($noMerchants) {
                $merchantFrequencyDays = null;
            } else {
                $merchantFrequencyDays = (int) $input->getOption('transactions-merchant-frequency-days');
            }

            if ($noTransactions) {
                $transactionsFrequencyDays = null;
            } else {
                $transactionsFrequencyDays = (int) $input->getOption('transactions-frequency-days');
            }

            [$accounHistoryTable, $merchantsTable] = $this->createTables(
                $output,
                $current,
                (int) $input->getOption('transactions-days'),
                $transactionsFrequencyDays,
                (bool) $input->getOption('transactions-force-create'),
                $merchantFrequencyDays,
                (bool) $input->getOption('transactions-merchant-force-create')
            );

            if (null !== $accounHistoryTable) {
                $this->fillAccountHistory($output, $accounHistoryTable);
            }

            if (null !== $merchantsTable) {
                $this->fillMerchantTables(
                    $output,
                    $merchantsTable,
                    (int) $input->getOption('transactions-by-merchant-limit'),
                    (int) $input->getOption('update-limit'),
                    (int) $input->getOption('update-seconds')
                );
            }

            $this->dbConnection->transactional(function () use ($accounHistoryTable, $merchantsTable, $output) {
                if (null !== $merchantsTable) {
                    $output->writeln("Updating Param " . ParameterRepository::MERCHANT_EXAMPLES_DATE . " with {$merchantsTable->getSuffix()}");
                    $this->parameterRepository->setParam(ParameterRepository::MERCHANT_EXAMPLES_DATE, $merchantsTable->getSuffix());
                }

                if (null !== $accounHistoryTable) {
                    $output->writeln("Updating Param " . ParameterRepository::LAST_TRANSACTIONS_DATE . " with {$accounHistoryTable->getSuffix()}");
                    $this->parameterRepository->setParam(ParameterRepository::LAST_TRANSACTIONS_DATE, $accounHistoryTable->getSuffix());
                }
            });

            return 0;
        } else {
            $output->writeln('Invalid mode');

            return 1;
        }
    }

    protected function configure()
    {
        $this
            ->setDescription('Create and fill tables for merchant transactions examples')
            ->addArgument('mode', InputOption::VALUE_REQUIRED, 'Mode of command: ' . \json_encode([self::MODE_CREATE, self::MODE_UPDATE]), self::MODE_CREATE)
            ->addOption('update-limit', 'u', InputOption::VALUE_REQUIRED, 'Limit of merchants to update in one batch', 10_000)
            ->addOption('update-seconds', 's', InputOption::VALUE_REQUIRED, 'Target time for update batch', 10)

            ->addOption('no-transactions', null, InputOption::VALUE_NONE, 'Do not create\fill transactions examples table')

            ->addOption('transactions-days', null, InputOption::VALUE_REQUIRED, 'Number of days to fill transaction examples', 120)
            ->addOption('transactions-frequency-days', null, InputOption::VALUE_REQUIRED, 'Frequency of periodic transaction examples snapshots', 3)
            ->addOption('transactions-force-create', null, InputOption::VALUE_NONE, 'Force create transactions examples table even if recent exists')

            ->addOption('no-merchants', null, InputOption::VALUE_NONE, 'Do not create\fill merchants examples table')

            ->addOption('transactions-merchant-frequency-days', null, InputOption::VALUE_REQUIRED, 'Frequency of periodic transactions by merchant examples snapshots', 1)
            ->addOption('transactions-by-merchant-limit', 't', InputOption::VALUE_REQUIRED, 'Limit of transactions to insert per merchant', 1)
            ->addOption('transactions-merchant-force-create', null, InputOption::VALUE_NONE, 'Force create merchants examples table even if recent exists')
        ;
    }

    /**
     * @return array{0: ?SnapshotTable, 1: ?SnapshotTable}
     */
    protected function createTables(
        OutputInterface $output,
        \DateTimeImmutable $current,
        int $transactionDays,
        ?int $transactionFrequencyDays,
        bool $transactionsForceCreate,
        ?int $merchantFrequencyDays,
        bool $transactionsMerchantForceCreate
    ): array {
        $accountHistoryNew = null;

        if (null !== $transactionFrequencyDays) {
            $accountHistoryLatest =
                it($this->snapshotTablesEnumerator->enumerate("LastTransactionsExamples%_{$transactionDays}d"))
                ->max(fn (SnapshotTable $tableA, SnapshotTable $tableB) => $tableA->getMaxDate() <=> $tableB->getMaxDate());

            if (
                $this->check->that('latest LastTransactionsExamples')->doesNot('exist')
                    ->on(!$accountHistoryLatest, ['accountHistoryLatest' => $accountHistoryLatest])

                || $this->check->that('account history max date')->is("more than {$transactionFrequencyDays} day(s) ago")
                    ->on($accountHistoryLatest->getMaxDate()->modify("+{$transactionFrequencyDays} day") < $current)

                || $this->check->that('force create LastTransactionsExamples flag')->is('enabled')
                    ->on($transactionsForceCreate)
            ) {
                $accountHistorySuffix = SnapshotTable::makeSuffix($current, $transactionDays);
                $transactionsTableName = "LastTransactionsExamples{$accountHistorySuffix}";
                $this->dbConnection->executeStatement("create table {$transactionsTableName} like AccountHistory");
                $output->writeln("Table {$transactionsTableName} was created");
                $accountHistoryNew = new SnapshotTable(
                    $transactionsTableName,
                    $current,
                    $transactionDays
                );
            }
        }

        $merchantsNew = null;

        if (null !== $merchantFrequencyDays) {
            $merchantLatest =
                it($this->snapshotTablesEnumerator->enumerate("MerchantRematchTransactionsExamples%"))
                ->max(fn (SnapshotTable $tableA, SnapshotTable $tableB) => $tableA->getMaxDate() <=> $tableB->getMaxDate());

            if (
                $this->check->that('latest MerchantRematchTransactionsExamples')->doesNot('exist')
                ->on(!$merchantLatest, ['merchantLatest' => $merchantLatest])

                || $this->check->that('merchant examples max date')->is("more than {$merchantFrequencyDays} day(s) ago")
                    ->on($merchantLatest->getMaxDate()->modify("+{$merchantFrequencyDays} day") < $current)

                || $this->check->that('force create MerchantRematchTransactionsExamples flag')->is('enabled')
                    ->on($transactionsMerchantForceCreate)
            ) {
                $merchantSuffix = SnapshotTable::makeSuffix($current);
                $merchantsTableName = "MerchantRematchTransactionsExamples{$merchantSuffix}";
                $this->dbConnection->executeStatement("
                    create table {$merchantsTableName} (
                        MerchantID int not null,
                        Name varchar(250) null,
                        DisplayName varchar(250) null,
                        MerchantPatternID int null,
                        Descriptions JSON,
                        Filled bit(1) not null default b'0',
                        Primary Key (MerchantID),
                        KEY `idxFilled` (`Filled`),
                        CONSTRAINT `MRTExamples{$merchantSuffix}_MerchantPatternID` FOREIGN KEY (`MerchantPatternID`) REFERENCES `MerchantPattern` (`MerchantPatternID`) ON DELETE SET NULL
                    )
                ");
                $merchantsNew = new SnapshotTable(
                    $merchantsTableName,
                    $current,
                    null
                );
                $output->writeln("Table {$merchantsTableName} was created");
            }
        }

        return [$accountHistoryNew, $merchantsNew];
    }

    protected function fillAccountHistory(
        OutputInterface $output,
        SnapshotTable $snapshotTable
    ): void {
        $output->writeln("Filling {$snapshotTable->getName()} table...");
        $rightDate = $snapshotTable->getMaxDate();
        $days = $snapshotTable->getDays();
        $this->dbConnection->executeStatement("
                insert into {$snapshotTable->getName()}
                select * from AccountHistory ah 
                where ah.PostingDate between ? and ?
            ",
            [
                $rightDate->modify("-{$days} days")->format('Y-m-d H:i:s'),
                $rightDate->format('Y-m-d H:i:s'),
            ]
        );
        $output->writeln('FILLED');
    }

    protected function fillMerchantTables(OutputInterface $output, SnapshotTable $snapshotTable, int $maxTransactions, int $updateLimitTarget, int $updateSeconds): void
    {
        $tableName = $snapshotTable->getName();
        $output->writeln("Filling {$tableName} table with empty values...");
        $this->dbConnection->executeStatement("
               insert into {$tableName}
               select
                    m.MerchantID,
                    m.Name,
                    m.DisplayName,
                    m.MerchantPatternID,
                    '[]' as Description,
                    b'0' as Filled
               from Merchant m
               left join {$tableName} mrt
                    on m.MerchantID = mrt.MerchantID
               where mrt.MerchantID is null"
        );
        $output->writeln('FILLED');
        $output->writeln("Filling {$tableName} table with Descriptions from AccountHistory...");
        $totalMerchantCount = (int) $this->dbConnection->fetchOne("
            select count(*) 
            from {$tableName}
            where Filled = b'0'
        ");
        $totalAffected = 0;
        $updateLimit = $updateLimitTarget;
        $queryTimeLimit = seconds($updateSeconds);

        do {
            $output->writeln("-== BATCH " . \str_pad($totalAffected, \strlen((string) $totalMerchantCount), " ", \STR_PAD_LEFT) . "/{$totalMerchantCount} ==-");
            $output->writeln("Querying {$updateLimit} merchant data...");
            $startTime = $this->clock->current();
            /** @var list<int> $merchantIdsList */
            $merchantIdsList = $this->dbConnection
                ->executeQuery("
                    select MerchantID
                    from {$tableName}
                    where Filled = b'0'
                    limit {$updateLimit}"
                )
                ->fetchFirstColumn();

            if (!$merchantIdsList) {
                break;
            }

            // $this->dbConnection->setTransactionIsolation(TransactionIsolationLevel::READ_UNCOMMITTED);
            // $this->replicaConnection->executeStatement("SET SESSION sql_mode = ''");
            // $this->replicaConnection->executeStatement('SET SESSION group_concat_max_len = ' . (36 + (36 + 3) * ($maxTransactions - 1)));
            $merchantDataMap = $this->replicaConnection
                ->executeQuery("
                    select
                        ah_batch.MerchantID,
                        IFNULL(JSON_ARRAYAGG(JSON_OBJECT(
                            'Description', ah.Description,
                            'UUID', ah.UUID,
                            'ShoppingCategoryID', ah.ShoppingCategoryID
                        )), '[]') as Descriptions,
                        b'1' as Filled
                    from (
                        select
                            mrt.MerchantID,
                            ah.UUID,
                            @merchant_nn := if(@current_merchant = mrt.MerchantID, @merchant_nn + 1, 1) as merchant_nn,
                            @current_merchant := mrt.MerchantID 
                        from Merchant mrt
                        left join AccountHistory ah use index (MerchantID)
                            on mrt.MerchantID = ah.MerchantID
                        where mrt.MerchantID in (?)
                        order by mrt.MerchantID
                    ) ah_batch
                    left join AccountHistory ah use index (`PRIMARY`)
                        on ah_batch.UUID = ah.UUID
                    where ah_batch.merchant_nn <= ?
                    group by ah_batch.MerchantID",
                    [$merchantIdsList, $maxTransactions],
                    [Connection::PARAM_INT_ARRAY, \PDO::PARAM_INT]
                )
                ->fetchAllAssociativeIndexed();
            $tookTime = $this->clock->current()->sub($startTime);
            $output->writeln('DONE');

            if ($tookTime->greaterThan($queryTimeLimit)) {
                $updateLimit = \max((int) ($updateLimit / 8), 100);
                $output->writeln("WARNING: Batch took " . $tookTime->getAsSecondsFractionFloat() . " seconds to query, it is more than {$queryTimeLimit->getAsSecondsFractionFloat()} seconds, decrease the update limit to {$updateLimit} merchants");
            } else {
                $increaseHeadroom = $queryTimeLimit->sub($tookTime)->getAsSecondsFractionFloat() / $tookTime->getAsSecondsFractionFloat();
                $updateLimit = \min($updateLimitTarget, (int) ($updateLimit * (1 + $increaseHeadroom * 0.2)));
                $output->writeln("Batch took " . $tookTime->getAsSecondsFractionFloat() . " seconds to query, it is less than {$queryTimeLimit->getAsSecondsFractionFloat()} seconds, increase the update limit to {$updateLimit} merchants");
            }

            $paramsList = [];
            $typesList = [];

            foreach ($merchantIdsList as $merchantId) {
                $paramsList[] = $merchantId;
                $typesList[] = \PDO::PARAM_INT;
                $paramsList[] = $merchantDataMap[$merchantId]['Descriptions'] ?? '[]';
                $typesList[] = \PDO::PARAM_STR;
            }

            $valuesSql = \implode(', ', \array_fill(0, \count($merchantIdsList), "(?, ?, b'1')"));
            $output->writeln("Inserting " . \count($merchantIdsList) . " merchant data...");
            // $this->dbConnection->setTransactionIsolation(TransactionIsolationLevel::REPEATABLE_READ);
            $affectedRows = $this->dbConnection->executeStatement("
                insert into {$tableName} (MerchantID, Descriptions, Filled)
                values {$valuesSql}
                on duplicate key update 
                    Descriptions = VALUES(Descriptions), 
                    Filled = b'1'",
                $paramsList,
                $typesList
            );
            $output->writeln("DONE, affected: {$affectedRows}");
            $totalAffected += \count($merchantIdsList);
            $output->writeln("-== BATCH DONE ==-");
        } while ($totalAffected < $totalMerchantCount);

        $output->writeln('FILLED');
    }
}
