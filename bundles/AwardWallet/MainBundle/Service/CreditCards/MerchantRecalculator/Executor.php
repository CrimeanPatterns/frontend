<?php

namespace AwardWallet\MainBundle\Service\CreditCards\MerchantRecalculator;

use AwardWallet\Common\Monolog\Handler\ArrayHandler;
use AwardWallet\MainBundle\Service\AppBot\Adapter\Slack;
use AwardWallet\MainBundle\Service\AppBot\AppBot;
use AwardWallet\MainBundle\Service\CreditCards\MerchantCategoryDetector;
use AwardWallet\MainBundle\Service\CreditCards\MerchantMatcher\MerchantMatcher;
use AwardWallet\MainBundle\Worker\AsyncProcess\ExecutorInterface;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use AwardWallet\MainBundle\Worker\AsyncProcess\Response;
use AwardWallet\MainBundle\Worker\AsyncProcess\Task as AsyncProcessTask;
use AwardWallet\MainBundle\Worker\AsyncProcess\TaskNeedsRetryException;
use Doctrine\DBAL\Connection;
use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use Symfony\Component\Lock\LockFactory;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtAssoc;
use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtColumn;

class Executor implements ExecutorInterface
{
    private Connection $connection;
    private AppBot $appBot;
    private MerchantMatcher $merchantMatcher;
    private LockFactory $lockFactory;
    private Logger $logger;
    private Process $process;
    private MerchantCategoryDetector $merchantCategoryDetector;

    private Connection $replicaUnbufferedConnection;

    public function __construct(
        Connection $connection,
        Connection $replicaUnbufferedConnection,
        AppBot $appBot,
        MerchantMatcher $merchantMatcher,
        LockFactory $lockFactory,
        Logger $logger,
        Process $process,
        MerchantCategoryDetector $merchantCategoryDetector
    ) {
        $this->connection = $connection;
        $this->appBot = $appBot;
        $this->merchantMatcher = $merchantMatcher;
        $this->lockFactory = $lockFactory;
        $this->logger = $logger;
        $this->process = $process;
        $this->merchantCategoryDetector = $merchantCategoryDetector;
        $this->replicaUnbufferedConnection = $replicaUnbufferedConnection;
    }

    /**
     * @param Task $task
     */
    public function execute(AsyncProcessTask $task, $delay = null)
    {
        $prefix = "[merchant recalculation]";

        if ($task->retry >= 8) {
            $this->appBot->send(Slack::CHANNEL_AW_JENKINS, "{$prefix} cancelled recalculation, could not acquire lock");

            return new Response();
        }

        $lock = $this->lockFactory->createLock("merchant_recalculation", 3600);

        if (!$lock->acquire()) {
            $this->logger->info("could not acquire lock for merchant recalculation, will retry in 5 min");

            throw new TaskNeedsRetryException(2 ^ $task->retry + random_int(0, 15));
        }

        try {
            $taskMerchantsIds =
                it(stmtColumn($this->replicaUnbufferedConnection->executeQuery('
                    select m.MerchantID
                    from MerchantPattern mp
                    join Merchant m on mp.MerchantPatternID = m.MerchantPatternID
                    where mp.MerchantPatternID in (?)',
                    [$task->getMerchantPatternIds()],
                    [Connection::PARAM_INT_ARRAY]
                )))
                ->toArray();

            $readQuery = $this->replicaUnbufferedConnection->executeQuery("select UUID, Description, ShoppingCategoryID, MerchantID from AccountHistory where MerchantID in (?)",
                [$taskMerchantsIds], [Connection::PARAM_INT_ARRAY]);
            $updateQuery = $this->connection->prepare("update AccountHistory set MerchantID = :merchantId where UUID = :uuid");
            $changedTransactions = [];
            $totalTransactions = [];
            $newMerchantIds = [];

            stmtAssoc($readQuery)
                ->onNthMillis(600000,
                    function (int $millisFromStart, int $iteration, $currentValue, $currentKey) use ($prefix) {
                        $this->appBot->send(Slack::CHANNEL_AW_JENKINS,
                            "{$prefix} processed " . number_format(count($iteration)) . " transactions..");
                    })
                ->apply(function (array $tx) use ($updateQuery, &$changedTransactions, &$totalTransactions, &$newMerchantIds) {
                    $totalTransactions[$tx['MerchantID']] = ($totalTransactions[$tx['MerchantID']] ?? 0) + 1;
                    $merchantId = $this->merchantMatcher->identify($tx["Description"], $tx["ShoppingCategoryID"]);

                    if ((int) $merchantId !== (int) $tx['MerchantID']) {
                        $updateQuery->executeStatement(["merchantId" => $merchantId, "uuid" => $tx['UUID']]);
                        $changedTransactions[$tx['MerchantID']] = ($changedTransactions[$tx['MerchantID']] ?? 0) + 1;
                        $newMerchantIds[$merchantId] = ($newMerchantIds[$merchantId] ?? 0) + 1;
                    }
                });

            $logs = new ArrayHandler(Logger::DEBUG, true, true);
            $logs->setFormatter(new LineFormatter());
            $this->logger->pushHandler($logs);

            try {
                it($taskMerchantsIds)
                ->apply(function ($merchantId) use ($totalTransactions, $changedTransactions) {
                    $newTransactions = ($totalTransactions[$merchantId] ?? 0) - ($changedTransactions[$merchantId] ?? 0);

                    $shoppingCategoryId = $this->merchantCategoryDetector->detectCategory(
                        $merchantId,
                        $newTransactions,
                        true
                    );

                    $this->connection->executeStatement(
                        "update Merchant set Transactions = :transactions, ShoppingCategoryID = :shoppingCategoryId where MerchantID = :merchantId",
                        [
                            "transactions" => $newTransactions,
                            "shoppingCategoryId" => $shoppingCategoryId,
                            "merchantId" => $merchantId,
                        ]
                    );
                });
            } finally {
                $this->logger->popHandler();
            }

            //            $categoryDetectorLog = it($logs->getRecords())->map(fn (array $record) => $record['message'])->joinToString("\n");

            it($newMerchantIds)
                ->applyIndexed(function (int $transactionsAdded, int $merchantId) {
                    $this->connection->executeStatement(
                        "update Merchant set Transactions = Transactions + :transactions where MerchantID = :merchantId",
                        [
                            "transactions" => $transactionsAdded,
                            "merchantId" => $merchantId,
                        ]
                    );
                });

            $info = "{$prefix} finished, processed " . number_format(array_sum($totalTransactions)) . " transactions. Merchant has been changed for " . number_format(array_sum($changedTransactions)) . " transactions.";

            if (count($newMerchantIds) > 0) {
                $info .= "Transactions have been moved to these merchants:\n" .
                    it($newMerchantIds)
                        ->mapIndexed(function (int $count, int $merchantId) {
                            return "{$merchantId} " . $this->connection->executeQuery("select Name from Merchant where MerchantID = ?", [$merchantId])->fetchOne() . ": {$count}";
                        })
                        ->joinToString("\n")
                ;
            }

            $this->appBot->send(Slack::CHANNEL_AW_JENKINS, $info);
        } finally {
            $lock->release();
        }

        return new Response();
    }

    public function getMaxRetriesCount(): int
    {
        return 10;
    }
}
