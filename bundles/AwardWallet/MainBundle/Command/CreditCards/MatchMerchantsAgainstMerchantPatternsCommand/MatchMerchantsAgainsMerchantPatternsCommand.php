<?php

namespace AwardWallet\MainBundle\Command\CreditCards\MatchMerchantsAgainstMerchantPatternsCommand;

use AwardWallet\MainBundle\Globals\Utils\IteratorFluent;
use AwardWallet\MainBundle\Service\CreditCards\MerchantMatcher\MerchantMatcher;
use AwardWallet\MainBundle\Service\CreditCards\MerchantNameNormalizer;
use Clock\ClockInterface;
use Doctrine\DBAL\Connection;
use Duration\Duration;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtObj;
use function Duration\seconds;

class MatchMerchantsAgainsMerchantPatternsCommand extends Command
{
    private const SOURCE_MASTER = 'master';
    private const SOURCE_MASTER_UNBUFFERED = 'master_unbuffered';
    private const SOURCE_REPLICA_UNBUFFERED = 'replica_unbuffered';
    private const SOURCES_LIST = [
        self::SOURCE_MASTER,
        self::SOURCE_MASTER_UNBUFFERED,
        self::SOURCE_REPLICA_UNBUFFERED,
    ];
    protected static $defaultName = 'match-merchants-against-merchantpatterns';
    private MerchantMatcher $merchantMatcher;
    private Connection $masterConnection;
    private Connection $replicaUnbufferedConnection;
    private LoggerInterface $logger;
    private ClockInterface $clock;
    private Connection $masterUnbufferedConnection;
    private MerchantNameNormalizer $merchantNameNormalizer;

    public function __construct(
        MerchantMatcher $merchantMatcher,
        Connection $connection,
        Connection $replicaUnbufferedConnection,
        Connection $unbufConnection,
        LoggerInterface $logger,
        ClockInterface $clock,
        MerchantNameNormalizer $merchantNameNormalizer
    ) {
        parent::__construct();

        $this->merchantMatcher = $merchantMatcher;
        $this->masterConnection = $connection;
        $this->replicaUnbufferedConnection = $replicaUnbufferedConnection;
        $this->logger = $logger;
        $this->clock = $clock;
        $this->masterUnbufferedConnection = $unbufConnection;
        $this->merchantNameNormalizer = $merchantNameNormalizer;
    }

    protected function configure()
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE)
            ->addOption('source', null, InputOption::VALUE_REQUIRED,
                'Database to read from. Available: ' . \json_encode(self::SOURCES_LIST), self::SOURCE_REPLICA_UNBUFFERED)
            ->addOption('w-mp', null, InputOption::VALUE_NONE, 'Match merchants with patterns')
            ->addOption('wo-mp', null, InputOption::VALUE_NONE, 'Math merchants without patterns')
            ->addOption('merchant', 'm', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Merchant IDs to process');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $isDryRun = $input->getOption('dry-run');
        [$availablePatterns, $positiveMegaPatterns, $negativeMegaPatterns] = $this->merchantMatcher->loadMerchantPatterns();
        $firstShot = true;
        $updatedCount = 0;
        $matchedCount = 0;
        $patternsMap = $this->loadMerchantPatternsMap();

        /** @var MerchantRow $merchantRow */
        foreach ($this->loadMerchantRows($input, $output) as $merchantRow) {
            continue;

            if ($firstShot) {
                $output->writeln('Processing first row...');
                $firstShot = false;
            }

            if ($merchantRow->MerchantPatternID) {
                if (!$merchantRow->Descriptions) {
                    continue;
                }

                foreach ($merchantRow->Descriptions as [
                    'Description' => $description,
                    'UUID' => $uuid,
                    'ShoppingCategoryID' => $shoppingCategoryId,
                ]) {
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
                                ++$updatedCount;
                                $this->logger->debug(
                                    "Merchant pattern changed"
                                    . ' for merchant ' . $merchantRow->Name
                                    . ' (id: ' . $merchantRow->MerchantID . ')'
                                    . ' from ' . ($merchantRow->MerchantPatternID ?? 'null')
                                    . ' (' . ($patternsMap[$merchantRow->MerchantPatternID]['Name'] ?? 'null') . ')'
                                    . ' to ' . ($merchantPatternId ?? 'null')
                                    . ' (' . ($merchantPatternName ?? 'null') . ')'
                                    . ' based on description: ' . $normalizedDesc
                                    . ', raw: ' . $description
                                    . ', SCID: ' . $shoppingCategoryId
                                    . ', MPIDByGroupID: ' . $merchantPatternIdByGroupID
                                    . ', UUID: ' . $uuid
                                );

                                continue 2;
                            }
                        }
                    } else {
                        $merchantPatternIdByGroupID = $this->loadExistingMerchantPatternId($normalizedDesc, $scGroupId);
                        $isSameMerchantPattern = $merchantPatternIdByGroupID === $merchantRow->MerchantPatternID;

                        if (!$isSameMerchantPattern) {
                            ++$updatedCount;
                            $this->logger->debug(
                                'Merchant pattern removed for merchant ' . $merchantRow->Name
                                . ' (id: ' . $merchantRow->MerchantID . ')'
                                . ' from ' . ($merchantRow->MerchantPatternID ?? 'null')
                                . ' based on description: ' . $normalizedDesc
                                . ', raw: ' . $description
                                . ', SCID: ' . $shoppingCategoryId
                                . ', MPIDByGroupID: ' . $merchantPatternIdByGroupID
                                . ', UUID: ' . $uuid
                            );

                            continue 2;
                        }
                    }
                }
            } else {
                $matchResult = $this->merchantMatcher->processPatterns(
                    $availablePatterns,
                    $positiveMegaPatterns,
                    $negativeMegaPatterns,
                    $merchantRow->Name
                );

                if ($matchResult) {
                    [$merchantPatternId, $merchantPatternName] = $matchResult;
                    $isSameMerchantPattern = $merchantPatternId === $merchantRow->MerchantPatternID;

                    // log that merchant pattern was changed or not
                    if (!$isSameMerchantPattern) {
                        ++$updatedCount;
                        $this->logger->debug(
                            "Merchant pattern introduced"
                            . ' for merchant ' . $merchantRow->Name
                            . ' (id: ' . $merchantRow->MerchantID . ')'
                            . ' from ' . ($merchantRow->MerchantPatternID ?? 'null')
                            . ' (' . ($patternsMap[$merchantRow->MerchantPatternID]['Name'] ?? 'null') . ')'
                            . ' to ' . ($merchantPatternId ?? 'null')
                            . ' (' . ($merchantPatternName ?? 'null') . ')'
                            . ' based on name: ' . $merchantRow->Name
                        );

                        if (!$isDryRun) {
                            // TODO: update here
                        }
                    }
                }
            }
        }

        // $output->writeln('Matched: ' . $matchedCount);
        $output->writeln('Updated\removed: ' . $updatedCount);

        return 0;
    }

    /**
     * @return IteratorFluent<MerchantRow>
     */
    private function loadMerchantRows(InputInterface $input, OutputInterface $output): IteratorFluent
    {
        $output->writeln('Loading merchants...');
        $lastStopTime = $startTime = $this->clock->current();
        $lastStopIteration = 0;
        $whereList = [];
        $paramsList = [];
        $typesList = [];

        if ($input->getOption('merchant')) {
            $whereList[] = 'm.MerchantID in (?)';
            $paramsList[] =
                it($input->getOption('merchant'))
                ->flatMap(fn (string $m) => \explode(',', $m))
                ->filterNotEmptyString()
                ->map(fn (string $m) => (int) \trim($m))
                ->toArray();
            $typesList[] = Connection::PARAM_INT_ARRAY;
        } else {
            if ($input->getOption('w-mp')) {
                $whereList[] = 'm.MerchantPatternID is not null';
            }

            if ($input->getOption('wo-mp')) {
                $whereList[] = 'm.MerchantPatternID is null';
            }
        }

        $where = $whereList ?
            'and (' . \implode(' OR ', $whereList) . ')' :
            '';

        $stmt = $this
            ->selectConnection($input->getOption('source'), $output)
            ->executeQuery("
                select
                    m.MerchantID,
                    m.Name,
                    m.DisplayName,
                    m.MerchantPatternID,
                    /** (
                        select JSON_ARRAYAGG(JSON_OBJECT(
                            'Description', sub.Description,
                            'UUID', sub.UUID,
                            'ShoppingCategoryID', sub.ShoppingCategoryID
                        ))
                        from (
                            select 
                                ah.Description, 
                                ah.UUID,
                                ah.ShoppingCategoryID
                            from AccountHistory ah
                            where ah.MerchantID = m.MerchantID
                            limit 1
                        ) sub
                    ) as Descriptions */
                    '[]' as Descriptions
                from Merchant m
                where 1=1 " . $where,
                $paramsList,
                $typesList
            );

        return
            stmtObj($stmt, MerchantRow::class)
            ->filter(fn (MerchantRow $row) => $row->Descriptions !== null)
            ->onEach(fn (MerchantRow $row) => $row->init())
            ->onNthMillisAndLast(
                seconds(5)->getAsMillisecondsInt(),
                $this->makePeriodicLogger(
                    $output,
                    'Merchants: ',
                    $startTime,
                    $lastStopIteration,
                    $lastStopTime
                )
            );
    }

    private function makePeriodicLogger(OutputInterface $output, string $logPrefix, Duration $startTime, int &$lastStopIteration, Duration &$lastStopTime)
    {
        return function ($_1, int $iteration, $_2, $_3, bool $isLast) use (
            &$lastStopIteration,
            &$lastStopTime,
            $startTime,
            $logPrefix,
            $output
        ) {
            $stopTime = $this->clock->current();
            $output->writeln(
                $logPrefix . 'running for: '
                . \number_format($stopTime->sub($startTime)->getAsMinutesFractionFloat(), 2)
                . ' min(s), '
                . "read: " . \number_format($iteration) . " records, "
                . "speed: " . \number_format(($iteration - $lastStopIteration) / 1000 / $stopTime->sub($lastStopTime)->getAsSecondsFractionFloat(), 2)
                . " K/sec, "
                . "memory " . \number_format((int) \round(\memory_get_usage(true) / (1024 * 1024))) . " MB"
            );
            $lastStopIteration = $iteration;
            $lastStopTime = $stopTime;
        };
    }

    private function selectConnection(string $source, OutputInterface $output): Connection
    {
        try {
            switch ($source) {
                case self::SOURCE_MASTER:
                    return $this->masterConnection;

                case self::SOURCE_MASTER_UNBUFFERED:
                    return $this->masterUnbufferedConnection;

                case self::SOURCE_REPLICA_UNBUFFERED:
                    return $this->replicaUnbufferedConnection;

                default:
                    throw new \InvalidArgumentException('Unknown source: ' . $source);
            }
        } finally {
            $output->writeln('Using source: ' . $source);
        }
    }

    private function loadMerchantPatternsMap(): array
    {
        $qb = $this->masterConnection->createQueryBuilder();
        $qb
            ->select(
                'MerchantPatternID',
                'Name',
                'Patterns'
            )
            ->from('MerchantPattern');

        return $qb->execute()->fetchAllAssociativeIndexed();
    }

    private function loadExistingMerchantPatternId(string $merchantPatternName, ?int $scGroupId): ?int
    {
        $id = $this->masterConnection->fetchOne('
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
