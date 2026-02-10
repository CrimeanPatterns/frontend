<?php

namespace AwardWallet\MainBundle\Service\CreditCards\MerchantMatcher\Async;

use AwardWallet\MainBundle\Command\CreditCards\CompleteTransactionsCommand\CompleteTransactionsCommand;
use AwardWallet\MainBundle\Command\CreditCards\MatchMerchantsAgainstMerchantPatternsCommand\MerchantRow;
use AwardWallet\MainBundle\Entity\Repositories\ParameterRepository;
use AwardWallet\MainBundle\Globals\Utils\ConcurrentArrayFactory;
use AwardWallet\MainBundle\Globals\Utils\IteratorFluent;
use AwardWallet\MainBundle\Globals\Utils\RingBuffer;
use AwardWallet\MainBundle\Security\StringSanitizer;
use AwardWallet\MainBundle\Service\CreditCards\MerchantMatcher\MerchantMatcher;
use AwardWallet\MainBundle\Service\CreditCards\MerchantNameNormalizer;
use AwardWallet\MainBundle\Service\LockWrapper;
use AwardWallet\MainBundle\Service\MerchantLookup;
use AwardWallet\MainBundle\Service\SocksMessaging\Client as SocksClient;
use AwardWallet\MainBundle\Worker\AsyncProcess\ExecutorInterface;
use AwardWallet\MainBundle\Worker\AsyncProcess\Response;
use AwardWallet\MainBundle\Worker\AsyncProcess\Task;
use AwardWallet\MainBundle\Worker\AsyncProcess\TaskNeedsRetryException;
use Clock\ClockInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Statement;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\Exception\LockConflictedException;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtObj;
use function Duration\minutes;
use function Duration\seconds;

class RelinkMerchantsExecutor implements ExecutorInterface
{
    private const DIFF_MAX_SIZE = 10;

    private Connection $unbufConnection;
    private MerchantMatcher $merchantMatcher;
    private MerchantNameNormalizer $merchantNameNormalizer;
    private LockWrapper $lockWrapper;
    private SocksClient $messaging;
    private ConcurrentArrayFactory $concurrentArrayFactory;
    private ClockInterface $clock;
    private Connection $connection;
    private ParameterRepository $paramRepository;
    private LoggerInterface $logger;
    private Statement $updateStmt;
    private MerchantLookup $merchantLookup;

    public function __construct(
        Connection $connection,
        Connection $unbufConnection,
        MerchantMatcher $merchantMatcher,
        MerchantNameNormalizer $merchantNameNormalizer,
        LockWrapper $lockWrapper,
        SocksClient $messaging,
        ConcurrentArrayFactory $concurrentArrayFactory,
        ClockInterface $clock,
        ParameterRepository $paramRepository,
        MerchantLookup $merchantLookup,
        LoggerInterface $logger
    ) {
        $this->unbufConnection = $unbufConnection;
        $this->merchantMatcher = $merchantMatcher;
        $this->merchantNameNormalizer = $merchantNameNormalizer;
        $this->lockWrapper = $lockWrapper;
        $this->messaging = $messaging;
        $this->concurrentArrayFactory = $concurrentArrayFactory;
        $this->clock = $clock;
        $this->connection = $connection;
        $this->paramRepository = $paramRepository;
        $this->logger = $logger;
        $this->merchantLookup = $merchantLookup;
    }

    /**
     * @param RelinkMerchantsTask $task
     */
    public function execute(Task $task, $delay = null)
    {
        try {
            return $this->lockWrapper->wrap(
                CompleteTransactionsCommand::MATCHER_LOCK_KEY,
                fn () => $this->doExecute($task, $delay),
                minutes(20)
            );
        } catch (LockConflictedException $conflictedException) {
            throw new TaskNeedsRetryException(minutes(10));
        }
    }

    /**
     * @param RelinkMerchantsTask $task
     */
    public function doExecute(Task $task, $delay = null)
    {
        $channelsMap = $this->concurrentArrayFactory->create('merchant_pattern_save_progress', minutes(30));
        $cancelled = false;

        if (!isset($channelsMap[$task->getResponseChannel()])) {
            return new Response();
        }

        $affectedMerchantPatternsIdsList = it($channelsMap)
            ->flatMap(fn (array $state) => $state['affected_merchant_pattern_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->toArray();
        $updateTask = function (string $channel, array $update, array $stateOnlyUpdate = []) use ($channelsMap) {
            $lastUpdate = $this->clock->current();
            $update = \array_merge(
                $update,
                ['last_updated' => $lastUpdate->format('Y-m-d H:i:s') . ' UTC'],
            );
            $channelsMap->update(function (array $map) use ($channel, $update, $lastUpdate, $stateOnlyUpdate) {
                $map[$channel] = \array_merge(
                    $map[$channel] ?? [],
                    $update,
                    $stateOnlyUpdate,
                    ['last_updated_internal' => $lastUpdate]
                );

                return $map;
            });

            $this->messaging->publish($channel, \array_merge($update, ['type' => 'log']));
        };

        [$availablePatterns, $positiveMegaPatterns, $negativeMegaPatterns] = $this->merchantMatcher->loadMerchantPatterns();
        $merchantExamplesDateSuffix = $this->paramRepository->getParam(ParameterRepository::MERCHANT_EXAMPLES_DATE);
        $this->logger->info('Using tables suffix: ' . $merchantExamplesDateSuffix, ['merchantExamplesDateSuffix' => $merchantExamplesDateSuffix]);
        $currentChannel = null;
        $channelsMap->update(function (array $map) use (&$currentChannel): array {
            $currentChannel = \array_key_last($map);
            $timeInternal = $this->clock->current();

            return
                it($map)
                ->filterIndexed(fn (array $channelData, string $key) =>
                    ($key === $currentChannel)
                    || $timeInternal->sub($channelData['last_updated_internal'])->lessThan(minutes(5))
                )
                ->filterNot(fn (array $channelData) =>
                    ('squashed' === $channelData['state'])
                    && $timeInternal->sub($channelData['last_updated_internal'])->greaterThan(minutes(1))
                )
                ->toArrayWithKeys();
        });

        foreach (
            it($channelsMap)
            ->reverse()
            ->drop(1)
            ->filter(fn (array $channelData) => 'queued' === $channelData['state'])
            ->keys() as $channelIdx
        ) {
            $updateTask($channelIdx, [
                'state' => 'squashed',
                'state_info' => 'Squashed into the latest rematch task',
            ]);
        }

        $updatedCount = 0;
        $lastIterationStop = 0;
        $lastStopTime = $this->clock->current();
        /** @var array<?int, RingBuffer<string>> $diffMap */
        $diffMap = [];
        $updateDiff = function (MerchantRow $merchant, ?array $newMerchantPattern) use (&$diffMap) {
            [$newMerchantPatternId, $newMerchantPatternName] = $newMerchantPattern;
            $oldMerchantPatternName = isset($merchant->MerchantPatternID) ? StringSanitizer::encodeHtmlEntities($merchant->MerchantPatternName) : null;
            $newMerchantPatternName = isset($newMerchantPatternName) ? StringSanitizer::encodeHtmlEntities($newMerchantPatternName) : null;
            /** @var RingBuffer<string> $ringBuffer */
            $ringBuffer = $diffMap[$merchant->MerchantPatternID] = ($diffMap[$merchant->MerchantPatternID] ?? new RingBuffer(self::DIFF_MAX_SIZE));
            $merchantName = StringSanitizer::encodeHtmlEntities($merchant->DisplayName);
            $log = "<a href='/manager/list.php?MerchantID={$merchant->MerchantID}&Schema=Merchant' target='_blank'>{$merchantName}</a>: ";

            if ($merchant->MerchantPatternID) {
                if ($newMerchantPatternId) {
                    $log .=
                        "<span class='pattern-changed'>MP CHANGED FROM</span> <a href='/manager/list.php?MerchantPatternID={$merchant->MerchantPatternID}&Schema=MerchantPattern' target='_blank'>{$oldMerchantPatternName}</a> "
                        . "<span class='pattern-changed'>TO</span>"
                        . " <a href='/manager/list.php?MerchantPatternID={$newMerchantPatternId}&Schema=MerchantPattern' target='_blank'>{$newMerchantPatternName}</a>";
                } else {
                    $log .= "<span class='pattern-removed'>MP REMOVED</span> <a href='/manager/list.php?MerchantPatternID={$merchant->MerchantPatternID}&Schema=MerchantPattern' target='_blank'>{$oldMerchantPatternName}</a>";
                }
            } else {
                if ($newMerchantPatternId) {
                    $log .= "<span class='pattern-added'>MP ADDED</span> <a href='/manager/list.php?MerchantPatternID={$newMerchantPatternId}&Schema=MerchantPattern' target='_blank'>{$newMerchantPatternName}</a>";
                } else {
                    throw new \LogicException('Both old and new merchant pattern IDs are null!');
                }
            }

            $log .= "<br/>";
            $ringBuffer->push($log);
        };
        $exportDiff = function () use (&$diffMap) {
            return
                it($diffMap)
                ->flatMap(fn (RingBuffer $buffer) => $buffer->all())
                ->toArray();
        };

        $merchantsCount = $this->merchantExamplesCount($merchantExamplesDateSuffix);

        if (!$merchantsCount) {
            $updateTask(
                $currentChannel,
                [
                    'state' => 'finished',
                    'state_info' => "Finished!",
                ]
            );

            return new Response();
        }

        $merchantsCount = $this->merchantsCount();

        $ticker = function ($_, $ticksCounter, $__, $___, bool $isLast) use ($exportDiff, &$updatedCount, $merchantsCount, $channelsMap, &$lastIterationStop, &$lastStopTime, $currentChannel, $updateTask, &$cancelled) {
            $time = $this->clock->current();
            $channelData = $channelsMap[$currentChannel] ?? null;

            if (!$channelData) {
                throw new \RuntimeException("Channel data not found for {$currentChannel}");
            }

            if ('cancelled' === $channelData['state']) {
                $cancelled = true;

                return;
            }

            $startTime = $channelData["start_date_internal"];
            $updateTask(
                $currentChannel,
                [
                    "state" => $isLast ? "finished" : 'processing',
                    "progress" => $progress = $merchantsCount ? \sprintf("%.2f", ($ticksCounter / $merchantsCount) * 100) : "0.00",
                    "state_info" => $isLast ? "Finished!" : "{$progress}%",
                    "elapsed_mins" => $time->sub($startTime)->format('i:s'),
                    "processed_count" => $ticksCounter,
                    "updated_count" => $updatedCount,
                    "speed" =>
                        \number_format(($ticksCounter - $lastIterationStop) / 1000 / $time->sub($lastStopTime)->getAsSecondsFractionFloat(), 2)
                        . " K/sec",
                ],
                ['diff' => $exportDiff()]
            );
            $lastIterationStop = $ticksCounter;
            $lastStopTime = $time;
        };
        $ticker(null, 0, null, null, false);
        /** @var ?Result $executingStmt */
        $executingStmt = null;

        /** @var MerchantRow $merchantRow */
        foreach (
            $this->loadMerchantRows($affectedMerchantPatternsIdsList, $merchantExamplesDateSuffix, $executingStmt)
            ->onNthMillisAndLast(seconds(2)->getAsMillisInt(), $ticker) as $merchantRow
        ) {
            if ($cancelled) {
                $this->logger->info('Task is cancelled.');
                $executingStmt->free();

                break;
            }

            if (!$merchantRow->Descriptions) {
                continue;
            }

            if ($merchantRow->MerchantPatternID) {
                foreach ($merchantRow->Descriptions as [
                    'Description' => $description,
                    'UUID' => $uuid,
                    'ShoppingCategoryID' => $shoppingCategoryId,
                ]) {
                    if (null === $description) {
                        continue;
                    }

                    $normalizedDesc = $this->merchantNameNormalizer->normalize($description);
                    $matchDescResult = $this->merchantMatcher->processPatterns(
                        $availablePatterns,
                        $positiveMegaPatterns,
                        $negativeMegaPatterns,
                        $normalizedDesc
                    );
                    $scGroupId = $this->merchantMatcher->detectCategoryGroupId($shoppingCategoryId);

                    if ($matchDescResult) {
                        [$merchantPatternId, $merchantPatternName] = $matchDescResult;
                        $isSameMerchantPattern = $merchantPatternId === $merchantRow->MerchantPatternID;

                        if (!$isSameMerchantPattern) {
                            $merchantPatternIdByGroupID = $this->loadExistingMerchantPatternId($merchantPatternName, $scGroupId);
                            $isSameMerchantPattern = $merchantPatternIdByGroupID === $merchantRow->MerchantPatternID;

                            if (!$isSameMerchantPattern) {
                                $this->update($merchantRow->MerchantID, $merchantPatternId);
                                $updateDiff(
                                    $merchantRow,
                                    [$merchantPatternId, $merchantPatternName]
                                );
                                $updatedCount++;

                                continue 2;
                            }
                        }
                    } else {
                        $merchantPatternIdByGroupID = $this->loadExistingMerchantPatternId($normalizedDesc, $scGroupId);
                        $isSameMerchantPattern = $merchantPatternIdByGroupID === $merchantRow->MerchantPatternID;

                        if (!$isSameMerchantPattern) {
                            $this->update($merchantRow->MerchantID, null);
                            $updateDiff(
                                $merchantRow,
                                [null, null]
                            );
                            $updatedCount++;

                            // remove
                            continue 2;
                        }
                    }

                    break;
                }
            } else {
                foreach ($merchantRow->Descriptions as [
                    'Description' => $description,
                    'UUID' => $uuid,
                    'ShoppingCategoryID' => $shoppingCategoryId,
                ]) {
                    if (null === $description) {
                        continue;
                    }

                    $normalizedDesc = $this->merchantNameNormalizer->normalize($description);
                    $matchResult = $this->merchantMatcher->processPatterns(
                        $availablePatterns,
                        $positiveMegaPatterns,
                        $negativeMegaPatterns,
                        $normalizedDesc,
                    );

                    if ($matchResult) {
                        [$merchantPatternId, $merchantPatternName] = $matchResult;
                        $this->update($merchantRow->MerchantID, $merchantPatternId);
                        $updateDiff(
                            $merchantRow,
                            [$merchantPatternId, $merchantPatternName]
                        );
                        $updatedCount++;
                    }

                    break;
                }
            }
        }

        $this->logger->info('Saving diff');
        $updateTask($currentChannel, ['diff' => $exportDiff()]);

        return new Response();
    }

    private function loadMerchantRows(array $firstToCheckMerchantPatternsIds, ?string $merchantExamplesDateSuffix, ?Result &$stmtControl): IteratorFluent
    {
        if (null === $merchantExamplesDateSuffix) {
            return it([]);
        }

        if ($firstToCheckMerchantPatternsIds) {
            $patternNames =
                $this->connection
                    ->executeQuery(
                        'select mp.Name from MerchantPattern mp where mp.MerchantPatternID in (?)',
                        [$firstToCheckMerchantPatternsIds],
                        [Connection::PARAM_INT_ARRAY]
                    )
                    ->fetchFirstColumn();
            $firstToCheckMerchantIds =
                it($patternNames)
                ->map(fn (string $name) => $this->merchantLookup->getMerchantFullTextOnlyList($name)['merchants'])
                ->flatten(1)
                ->toArray();

            if (!$firstToCheckMerchantIds) {
                $firstToCheckMerchantIds = [-1];
            }

            $stmtControl = $this->unbufConnection->executeQuery(/** @lang MySQL */ "
                (
                    select
                        m.MerchantID,
                        m.Name,
                        m.DisplayName,
                        m.MerchantPatternID,
                        mp.Name as MerchantPatternName,
                        mrt.Descriptions
                    from MerchantPattern mp
                    join Merchant m on mp.MerchantPatternID = m.MerchantPatternID
                    join MerchantRematchTransactionsExamples{$merchantExamplesDateSuffix} mrt on 
                        mrt.MerchantID = m.MerchantID and
                        mrt.Filled = b'1'
                    where mp.MerchantPatternID in (:firstPatternsToCheck)
                    union
                    select
                        m.MerchantID,
                        m.Name,
                        m.DisplayName,
                        m.MerchantPatternID,
                        mp.Name as MerchantPatternName,
                        mrt.Descriptions
                    from Merchant m
                    left join MerchantPattern mp on m.MerchantPatternID = mp.MerchantPatternID
                    join MerchantRematchTransactionsExamples{$merchantExamplesDateSuffix} mrt on 
                        mrt.MerchantID = m.MerchantID and
                        mrt.Filled = b'1'
                    where m.MerchantID in (:firstMerchantsToCheck)
                )
                union all
                select
                    m.MerchantID,
                    m.Name,
                    m.DisplayName,
                    m.MerchantPatternID,
                    mp.Name as MerchantPatternName,
                    mrt.Descriptions
                from Merchant m
                join MerchantRematchTransactionsExamples{$merchantExamplesDateSuffix} mrt on 
                    mrt.MerchantID = m.MerchantID and
                    mrt.Filled = b'1'
                left join MerchantPattern mp on m.MerchantPatternID = mp.MerchantPatternID
                where 
                    m.MerchantID not in (:firstMerchantsToCheck)
                    and (
                        m.MerchantPatternID is null 
                        or m.MerchantPatternID not in (:firstPatternsToCheck)
                    )",
                [
                    ':firstPatternsToCheck' => $firstToCheckMerchantPatternsIds,
                    ':firstMerchantsToCheck' => $firstToCheckMerchantIds,
                ],
                [
                    ':firstPatternsToCheck' => Connection::PARAM_INT_ARRAY,
                    ':firstMerchantsToCheck' => Connection::PARAM_INT_ARRAY,
                ]
            );
        } else {
            $stmtControl = $this->unbufConnection->executeQuery("
                select
                    m.MerchantID,
                    m.Name,
                    m.DisplayName,
                    m.MerchantPatternID,
                    mp.Name as MerchantPatternName,
                    mrt.Descriptions
                from Merchant m
                join MerchantRematchTransactionsExamples{$merchantExamplesDateSuffix} mrt on 
                    mrt.MerchantID = m.MerchantID and
                    mrt.Filled = b'1'
                left join MerchantPattern mp on m.MerchantPatternID = mp.MerchantPatternID"
            );
        }

        $maxTransactions = 1;

        return
            stmtObj($stmtControl, MerchantRow::class)
            ->chain((function () use ($merchantExamplesDateSuffix, &$maxTransactions, &$stmtControl) {
                $stmtControl = $this->unbufConnection->executeQuery("
                    select 
                        mOuter.MerchantID,
                        mOuter.Name,
                        mOuter.DisplayName,
                        mp.MerchantPatternID,
                        mp.Name as MerchantPatternName,
                        grouped.Descriptions
                    from (
                        select
                            ah_batch.MerchantID,
                            IFNULL(JSON_ARRAYAGG(JSON_OBJECT(
                                'Description', ah.Description,
                                'UUID', ah.UUID,
                                'ShoppingCategoryID', ah.ShoppingCategoryID
                            )), '[]') as Descriptions
                        from (
                            select
                                m.MerchantID,
                                ah.UUID,
                                @merchant_nn := if(@current_merchant = m.MerchantID, @merchant_nn + 1, 1) as merchant_nn,
                                @current_merchant := m.MerchantID 
                            from Merchant m
                            left join MerchantRematchTransactionsExamples{$merchantExamplesDateSuffix} mrt on m.MerchantID = mrt.MerchantID
                            left join AccountHistory ah use index (MerchantID)
                                on m.MerchantID = ah.MerchantID
                            where mrt.MerchantID is null
                            order by m.MerchantID
                        ) ah_batch
                        left join AccountHistory ah use index (`PRIMARY`)
                            on ah_batch.UUID = ah.UUID
                        where ah_batch.merchant_nn <= ?
                        group by ah_batch.MerchantID
                    ) grouped
                    join Merchant mOuter on mOuter.MerchantID = grouped.MerchantID
                    left join MerchantPattern mp on mOuter.MerchantPatternID = mp.MerchantPatternID",
                    [$maxTransactions]
                );

                yield from stmtObj($stmtControl, MerchantRow::class);
            })())
            ->filter(static fn (MerchantRow $row) => $row->Descriptions !== null)
            ->onEach(static function (MerchantRow $row) use (&$maxTransactions) {
                $row->init();

                if (null !== $row->Descriptions) {
                    $count = \count($row->Descriptions);

                    if ($count > $maxTransactions) {
                        $maxTransactions = $count;
                    }
                }
            });
    }

    private function update(int $merchantId, ?int $newMerchantPatternId): void
    {
        if (!isset($this->updateStmt)) {
            $this->updateStmt = $this->connection->prepare('update Merchant set MerchantPatternID = :MerchantPatternID where MerchantID = :MerchantID');
        }

        $this->updateStmt->executeStatement([
            ':MerchantID' => $merchantId,
            ':MerchantPatternID' => $newMerchantPatternId,
        ]);
    }

    private function merchantExamplesCount(?string $merchantExamplesDateSuffix): int
    {
        if (null === $merchantExamplesDateSuffix) {
            return 0;
        }

        return (int) $this->connection
            ->executeQuery("select count(*) from MerchantRematchTransactionsExamples{$merchantExamplesDateSuffix} where Filled = b'1'")
            ->fetchOne();
    }

    private function merchantsCount(): int
    {
        return (int) $this->connection
            ->executeQuery("select count(*) from Merchant")
            ->fetchOne();
    }

    private function loadExistingMerchantPatternId(string $merchantPatternName, ?int $scGroupId): ?int
    {
        $id = $this->connection->fetchOne('
            select m.MerchantPatternID
            from Merchant m
            where
                m.Name = ?
                and m.NotNullGroupID = ?',
            [
                $merchantPatternName,
                $scGroupId ?? 0,
            ]
        );

        return $id === null ? null : (int) $id;
    }
}
