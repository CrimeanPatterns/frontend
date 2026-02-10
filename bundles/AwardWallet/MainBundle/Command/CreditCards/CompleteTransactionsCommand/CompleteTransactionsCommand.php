<?php

namespace AwardWallet\MainBundle\Command\CreditCards\CompleteTransactionsCommand;

use AwardWallet\Common\Doctrine\BatchUpdater;
use AwardWallet\MainBundle\Service\LockWrapper;
use Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\Exception\LockConflictedException;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function Duration\hours;
use function Duration\minutes;
use function Duration\seconds;

class CompleteTransactionsCommand extends Command
{
    public const MATCHER_LOCK_KEY = 'merchant_matcher_lock_v2';
    public const PACKAGE_SIZE = 50;
    public const DUMP_STATUS_TIME = 30;

    private const UPDATE_SQL = "UPDATE AccountHistory set MerchantID = :MerchantID, ShoppingCategoryID = :ShoppingCategoryID, Multiplier = :Multiplier WHERE UUID = :UUID";
    protected static $defaultName = 'aw:credit-cards:complete-transactions';

    private LoggerInterface $logger;
    private LockWrapper $lockWrapper;
    private ClockInterface $clock;
    private MainQuery $historyRowsQuery;
    private HistoryRowsProcessor $historyRowsProcessor;
    private BatchUpdater $batchUpdater;

    public function __construct(
        LoggerInterface $logger,
        LockWrapper $lockWrapper,
        MainQuery $historyRowsQuery,
        HistoryRowsProcessor $historyRowsProcessor,
        BatchUpdater $batchUpdater,
        ClockInterface $clock
    ) {
        $this->logger = $logger;
        parent::__construct();
        $this->lockWrapper = $lockWrapper;
        $this->clock = $clock;
        $this->historyRowsQuery = $historyRowsQuery;
        $this->historyRowsProcessor = $historyRowsProcessor;
        $this->batchUpdater = $batchUpdater;
    }

    protected function configure()
    {
        $this
            ->addOption('update', null, InputOption::VALUE_NONE)
            ->addOption('fetch-only', null, InputOption::VALUE_NONE)
            ->addOption('dry-run', null, InputOption::VALUE_NONE)
            ->addOption('source', null, InputOption::VALUE_OPTIONAL,
                "Reading from param. Available: replica (default), staging, master", MainQuery::SOURCE_REPLICA)
            ->addOption('where', null, InputOption::VALUE_REQUIRED, 'sql where')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED)
            ->addOption('uuid', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $endTime = $this->clock->current()->add(minutes(20));

        while (true) {
            try {
                return $this->lockWrapper->wrap(
                    self::MATCHER_LOCK_KEY,
                    fn () => $this->doExecute($input, $output),
                    hours(4)
                );
            } catch (LockConflictedException $e) {
                if ($endTime->greaterThan($this->clock->current())) {
                    $sleep = seconds(\random_int(1, 10));
                    $output->writeln("Lock conflict, retrying in {$sleep}...");
                    $this->clock->sleep($sleep);
                } else {
                    $this->logger->error("Lock awaiting timeout, exiting");

                    return 1;
                }
            }
        }
    }

    protected function doExecute(InputInterface $input, OutputInterface $output): int
    {
        $fetchOnly = (bool) $input->getOption('fetch-only');
        $dryRun = $input->getOption('dry-run');
        $update = $input->getOption('update');
        $source = $input->getOption('source');
        $this->logger->info("Select " . ($update ? "all" : "incremental") . " transactions from {$source}" . ($fetchOnly ? ", fetch only" : "") . ($dryRun ? ", dry run" : ""));
        $historyRowsIterable = $this->getMainQueryResult($input);
        $rowsGen = $this->historyRowsProcessor->process(
            $historyRowsIterable,
            $fetchOnly,
            $dryRun
        );
        // ---------------------------------------------------------
        // обновляем записи в AccountHistory (изменения MerchantID и Multiplier)
        $updatedHistoryRowsCount = 0;

        foreach (
            it($rowsGen)
            ->filter(static function (AccountHistoryRow $row) use ($output) {
                try {
                    return !$row->isFresh();
                } catch (\LogicException $e) {
                    $output->writeln(
                        "[!!!] Unmaterialized PostponedMerchantUpdate: "
                        . \json_encode($row)
                    );

                    return false;
                }
            })
            ->map(static fn (AccountHistoryRow $row) => [
                "UUID" => $row->UUID,
                "MerchantID" => ($calculated = $row->CalculatedMerchantData)->merchantId,
                "ShoppingCategoryID" => $calculated->categoryId,
                "Multiplier" => $calculated->multiplier,
            ])
            ->chunk(self::PACKAGE_SIZE) as $historyRowsUpdatesChunk
        ) {
            $this->logger->info("updating " . \count($historyRowsUpdatesChunk) . ' history rows');
            $updatedHistoryRowsCount += \count($historyRowsUpdatesChunk);
            $this->batchUpdater->batchUpdate(
                $historyRowsUpdatesChunk,
                self::UPDATE_SQL,
                0
            );
        }

        [
            'categoriesMap' => $logParsedCategoriesMap,
            'processedHistoryRowsCount' => $processedHistoryRowsCount,
            'upsertedMerchantsCount' => $upsertedMerchantsCount,
            'merchantMatcherStats' => $merchantMatcherStats,
        ] = $rowsGen->getReturn();

        $this->logger->notice("$processedHistoryRowsCount history rows processed. $updatedHistoryRowsCount history rows updated. {$upsertedMerchantsCount} merchants upserted");

        $this->logger->notice("Parsed categories list:");

        foreach ($logParsedCategoriesMap as $item => $_) {
            $this->logger->info(" - " . $item);
        }

        $this->logger->info("performance stats: " . json_encode($merchantMatcherStats));

        return 0;
    }

    /**
     * @return iterable<AccountHistoryRow>
     */
    private function getMainQueryResult(InputInterface $input): iterable
    {
        return $this->historyRowsQuery->execute(
            $input->getOption('source'),
            (bool) $input->getOption('update'),
            $input->getOption('where'),
            $input->getOption('uuid'),
            $input->getOption('limit')
        );
    }
}
