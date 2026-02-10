<?php

namespace AwardWallet\MainBundle\Service\CreditCards\Commands {
    use AwardWallet\MainBundle\Entity\Merchant;
    use AwardWallet\MainBundle\Entity\Repositories\ParameterRepository;
    use AwardWallet\MainBundle\Entity\ShoppingCategory;
    use AwardWallet\MainBundle\Globals\StringUtils;
    use AwardWallet\MainBundle\Globals\Utils\IteratorFluent;
    use AwardWallet\MainBundle\Service\CreditCards\Commands\AnalyzeMerchantStatsCommand\Data\Table;
    use Clock\ClockNative;
    use Doctrine\DBAL\Connection;
    use Doctrine\DBAL\Exception\DeadlockException;
    use Doctrine\DBAL\Exception\LockWaitTimeoutException;
    use Doctrine\DBAL\ParameterType;
    use Doctrine\DBAL\TransactionIsolationLevel;
    use Duration\Duration;
    use Psr\Log\LoggerInterface;
    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Input\InputOption;
    use Symfony\Component\Console\Output\OutputInterface;

    use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
    use function AwardWallet\MainBundle\Globals\Utils\stmt\stmt;
    use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtColumn;
    use function Duration\milliseconds;

    class AnalyzeMerchantStatsCommand extends Command
    {
        public const MAX_STATEMENT_ATTEMPTS = 20;
        public const MAX_TEMPORAL_TEMPLATE_COUNT = 10;
        private const REDUCTION_DATA_INDEX = 0;
        private const UPDATE_BATCH_SIZE = 10_000;
        private const BATCH_RESOLUTION_SIZE = 5_000;
        private const REDUCTION_TOTAL_LENGTH_INDEX = 1;
        public static $defaultName = 'aw:credit-cards:analyze-merchant-stats';

        private Connection $replicaUnbufferedConnection;
        private Connection $connection;
        private LoggerInterface $logger;

        private array $transactionsCountMap = [];
        private array $merchantReportTransactionsCount = [];
        private array $merchantReportExpectedMultiplierTransactionsCount = [];
        private array $transactionsLast3MonthsCountMap = [];
        private array $popularCategoryCountMap = [];
        private array $statByCardCountMap = [];
        private array $statByMultiplierCountMap = [];
        private ClockNative $clock;
        /**
         * @var Table[]
         */
        private array $tempTablesDefinitions;
        private ParameterRepository $paramRepository;
        private array $PERIODS;
        private static array $TEMPORAL_TEMPLATES;
        private int $PERIODS_COUNT;
        private array $merchantBuckets = [];
        private Connection $sphinxConnection;

        public function __construct(
            Connection $replicaUnbufferedConnection,
            Connection $connection,
            ParameterRepository $paramRepository,
            LoggerInterface $logger,
            Connection $sphinxConnection
        ) {
            parent::__construct();
            $this->replicaUnbufferedConnection = $replicaUnbufferedConnection;
            $this->connection = $connection;
            $this->logger = $logger;
            $this->clock = new ClockNative();
            $this->tempTablesDefinitions = [
                new Table(
                    'MerchantTransactionsStatsTemp',
                    '(
                        MerchantID int not null,
                        PeriodsOffset int not null,
                        Transactions int not null
                    )',
                    'add index idxMerchantID (MerchantID, PeriodsOffset)'
                ),
                new Table(
                    'MerchantReportTransactionsStatsTemp',
                    '(
                        MerchantID int not null,
                        CreditCardID int not null,
                        ShoppingCategoryID int not null,
                        PeriodsOffset int not null,
                        Transactions int not null
                    )',
                    'add index idxMerchantID (MerchantID, CreditCardID, ShoppingCategoryID, PeriodsOffset)'
                ),
                new Table(
                    'MerchantReportExpectedTransactionsStatsTemp',
                    '(
                        MerchantID int not null,
                        CreditCardID int not null,
                        ShoppingCategoryID int not null,
                        PeriodsOffset int not null,
                        Transactions int not null
                    )',
                    'add index idxMerchantID (MerchantID, CreditCardID, ShoppingCategoryID, PeriodsOffset)'
                ),
                new Table(
                    'MerchantTransactionsLast3MonthsStatsTemp',
                    '(
                        MerchantID int not null,
                        Transactions int not null
                    )',
                    'add index idxMerchantID (MerchantID)'
                ),
                new Table(
                    'MerchantPopularShoppingCategoryStatsTemp',
                    '(
                        MerchantID int not null,
                        ShoppingCategoryID int not null,
                        PeriodsOffset int not null,
                        Transactions int not null
                    )',
                    'add index idxMerchantIDShoppingCategoryID (MerchantID, ShoppingCategoryID, PeriodsOffset)'
                ),
                new Table(
                    'MerchantCacheByCardStatsTemp',
                    '(
                        MerchantID int unsigned not null,
                        CardID int unsigned not null,
                        PeriodsOffset int not null,
                        CardCount int unsigned not null
                    )',
                    'add index idxMerchantIDCardID (MerchantID, CardID, PeriodsOffset)'
                ),
                new Table(
                    'MerchantCacheByMultiplierStatsTemp',
                    '(
                        MerchantID int unsigned not null,
                        CardID varchar(64) not null,
                        PeriodsOffset int not null,
                        CardCount int unsigned not null
                    )',
                    'add index idxMerchantIDCardID (MerchantID, CardID, PeriodsOffset)'
                ),
            ];
            $this->paramRepository = $paramRepository;
            $this->sphinxConnection = $sphinxConnection;
        }

        public function configure()
        {
            $this
                ->addOption('max-memory', null, InputOption::VALUE_REQUIRED, 'maximum memory allowed im MB, default: 256 MB', 256)
                ->addOption('max-update-packet', null, InputOption::VALUE_REQUIRED, 'max update packet in MB, default: 16', 16)
                ->addOption('merchantId', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'merchantID, may be multiple')
                ->addOption('start-date', null, InputOption::VALUE_REQUIRED)
                ->addOption('min-period-length', null, InputOption::VALUE_REQUIRED, 'period length in days, default: 14 days (two weeks)', 14)
                ->addOption('merchant-confidence-trx-amount', null, InputOption::VALUE_REQUIRED, 'merchant confidence transactions amount', \PHP_INT_MAX)
                ->addOption('now-date', null, InputOption::VALUE_REQUIRED, 'now date', 'now')
            ;
        }

        public function execute(InputInterface $input, OutputInterface $output)
        {
            $merchantIds = it($input->getOption('merchantId'))
                ->map(fn ($id) => (int) $id)
                ->toArray();

            $minPeriod = (int) $input->getOption('min-period-length');
            $nowDate = new \DateTimeImmutable($input->getOption('now-date'));
            $periodsCount = 10;
            $exponentBase = 2;
            // periods with exponential growths
            $this->PERIODS =
                it(\range(0, $periodsCount - 1))
                ->map(fn ($exp) => $exponentBase ** $exp)
                ->prepend(0)
                ->sliding(2)
                ->reductions(
                    fn (array $acc, array $window, int $idx) => [
                        [
                            $acc[1],
                            $rightBound = $window[1] * $minPeriod + $acc[1],
                            $nowDate->modify("-{$rightBound} days"),
                        ],
                        $rightBound,
                    ],
                    [
                        null,
                        0, // base offset
                    ]
                )
                ->map(fn (array $reduction) => $reduction[0])
                ->toArray();
            $this->PERIODS_COUNT = \count($this->PERIODS);
            $unixStart = new \DateTimeImmutable('@0');
            $this->PERIODS[$this->PERIODS_COUNT - 1][1] = max(0, \abs($nowDate->diff($unixStart)->days));
            $this->PERIODS[$this->PERIODS_COUNT - 1][2] = $unixStart;
            self::$TEMPORAL_TEMPLATES =
                it($this->PERIODS)
                ->mapIndexed(fn ($_, $offset) =>
                    it(\range(1, self::MAX_TEMPORAL_TEMPLATE_COUNT))
                    ->map(fn ($count) => [$offset => $count])
                    ->toArray()
                )
                ->toArray();

            $startDate = StringUtils::isNotEmpty($startDate = $input->getOption('start-date')) ?
                new \DateTimeImmutable($startDate) :
                null;

            $this->smartStatement(
                "Building merchant ids range...",
                fn () => $this->buildMerchantBuckets()
            );

            $maxMerchantId = $this->calculateTempStats(
                (int) $input->getOption('max-memory'),
                (int) $input->getOption('max-update-packet'),
                $merchantIds,
                $startDate,
                $nowDate
            );
            $merchantReportTargetVersion = $nowDate->getTimestamp();
            $this->logger->info("MerchantReport will be updated to {$merchantReportTargetVersion}.");

            $this->smartChunkedStatement(
                'Updating Merchant transactions count...',
                fn (int $lowMerchantId, int $highMerchantId) => $this->connection->executeStatement('
                    update Merchant m
                    left join (
                        select 
                            trx.MerchantID,
                            sum(trx.Transactions) as TransactionsPerMerchant
                        from MerchantTransactionsStatsTemp trx
                        where
                            trx.MerchantID >= :lowMerchantId 
                            and trx.MerchantID < :highMerchantId
                        group by trx.MerchantID
                    ) MerchantStats on m.MerchantID = MerchantStats.MerchantID
                    set m.Transactions = IFNULL(MerchantStats.TransactionsPerMerchant, 0)
                    where 
                        (
                            m.MerchantID >= :lowMerchantId
                            and m.MerchantID < :highMerchantId
                        ) and '
                    . (
                        $merchantIds ?
                            ' m.MerchantID in (:merchantIds) ' :
                            ' m.MerchantID <= :maxMerchantId '
                    ),
                    \array_merge(
                        $merchantIds ?
                            ['merchantIds' => $merchantIds] :
                            ['maxMerchantId' => $maxMerchantId],
                        [
                            'lowMerchantId' => $lowMerchantId,
                            'highMerchantId' => $highMerchantId,
                        ]
                    ),
                    \array_merge(
                        $merchantIds ?
                            ['merchantIds' => Connection::PARAM_INT_ARRAY] :
                            ['maxMerchantId' => ParameterType::INTEGER],
                        [
                            'lowMerchantId' => ParameterType::INTEGER,
                            'highMerchantId' => ParameterType::INTEGER,
                        ]
                    )
                ),
                50_000
            );
            $this->deleteMerchantsWithoutTransactions();

            $this->smartChunkedStatement(
                'Updating Merchant last 3 months transactions count...',
                fn (int $lowMerchantId, int $highMerchantId) => $this->connection->executeStatement('
                    update Merchant m
                    left join (
                        select 
                            trx.MerchantID,
                            sum(trx.Transactions) as TransactionsPerMerchant
                        from MerchantTransactionsLast3MonthsStatsTemp trx
                        where
                            trx.MerchantID >= :lowMerchantId 
                            and trx.MerchantID < :highMerchantId
                        group by trx.MerchantID
                    ) MerchantStats on m.MerchantID = MerchantStats.MerchantID
                    set m.TransactionsLast3Months = IFNULL(MerchantStats.TransactionsPerMerchant, 0)
                    where 
                        (
                            m.MerchantID >= :lowMerchantId
                            and m.MerchantID < :highMerchantId
                        ) and '
                    . (
                        $merchantIds ?
                            ' m.MerchantID in (:merchantIds) ' :
                            ' m.MerchantID <= :maxMerchantId '
                    ),
                    \array_merge(
                        $merchantIds ?
                            ['merchantIds' => $merchantIds] :
                            ['maxMerchantId' => $maxMerchantId],
                        [
                            'lowMerchantId' => $lowMerchantId,
                            'highMerchantId' => $highMerchantId,
                        ]
                    ),
                    \array_merge(
                        $merchantIds ?
                            ['merchantIds' => Connection::PARAM_INT_ARRAY] :
                            ['maxMerchantId' => ParameterType::INTEGER],
                        [
                            'lowMerchantId' => ParameterType::INTEGER,
                            'highMerchantId' => ParameterType::INTEGER,
                        ]
                    )
                ),
                50_000
            );

            $confidenceIntervalTrxAmount = (int) $input->getOption('merchant-confidence-trx-amount');
            $this->calculateMerchantConfidencePeriodInterval($confidenceIntervalTrxAmount);
            $this->calculateMerchantPatternConfidencePeriodInterval($confidenceIntervalTrxAmount);

            $this->smartChunkedStatement(
                'Updating Merchant most popular ShoppingCategoryID...',
                fn (int $lowMerchantId, int $highMerchantId) => $this->connection->executeStatement('
                update Merchant m
                set m.ShoppingCategoryID = (
                    select stat.ShoppingCategoryID
                    from (
                        select
                            sum(scstat.Transactions) as TransactionsPerCategory,
                            scstat.ShoppingCategoryID
                        from MerchantPopularShoppingCategoryStatsTemp scstat
                        use index(idxMerchantIDShoppingCategoryID)
                        join MerchantPeriodlyStatTemp mmst on 
                            mmst.MerchantID = scstat.MerchantID
                            and scstat.PeriodsOffset <= mmst.MaxPeriodsOffset
                        where 
                            scstat.MerchantID >= :lowMerchantId 
                            and scstat.MerchantID < :highMerchantId
                            and scstat.MerchantID = m.MerchantID
                        group by scstat.ShoppingCategoryID
                    ) stat
                    join ShoppingCategory st on stat.ShoppingCategoryID = st.ShoppingCategoryID
                    order by
                        stat.TransactionsPerCategory DESC,
                        st.Name,
                        st.ShoppingCategoryID
                    limit 1
                ) where 
                    (
                        m.MerchantID >= :lowMerchantId
                        and m.MerchantID < :highMerchantId
                    ) and '
                    . (
                        $merchantIds ?
                            ' m.MerchantID in (:merchantIds) ' :
                            ' m.MerchantID <= :maxMerchantId '
                    ),
                    \array_merge(
                        $merchantIds ?
                            ['merchantIds' => $merchantIds] :
                            ['maxMerchantId' => $maxMerchantId],
                        [
                            'lowMerchantId' => $lowMerchantId,
                            'highMerchantId' => $highMerchantId,
                        ]
                    ),
                    \array_merge(
                        $merchantIds ?
                            ['merchantIds' => Connection::PARAM_INT_ARRAY] :
                            ['maxMerchantId' => ParameterType::INTEGER],
                        [
                            'lowMerchantId' => ParameterType::INTEGER,
                            'highMerchantId' => ParameterType::INTEGER,
                        ]
                    )
                ),
                5_000
            );

            $this->connection->executeStatement('SET session tmp_table_size = 16*16777216');
            $this->connection->executeStatement('SET session max_heap_table_size = 16*16777216');
            $this->smartChunkedStatement(
                'Updating Merchant.Stat credit cards cache...',
                fn (int $lowMerchantId, int $highMerchantId) => $this->connection->executeStatement("
                update Merchant m
                left join (
                    select
                        byCardInner.MerchantID,
                        JSON_OBJECTAGG(
                            byCardInner.CardID,
                            byCardInner.CardCount
                        ) as json
                    from (
                        select
                            bc.MerchantID,
                            bc.CardID,
                            sum(if(mmst.MerchantID is null, 0, bc.CardCount)) as CardCount
                        from MerchantCacheByCardStatsTemp bc
                        use index(idxMerchantIDCardID)
                        left join MerchantPeriodlyStatTemp mmst on 
                            mmst.MerchantID = bc.MerchantID
                            and bc.PeriodsOffset <= mmst.MaxPeriodsOffset
                        where
                            bc.MerchantID >= :lowMerchantId 
                            and bc.MerchantID < :highMerchantId
                        group by bc.MerchantID, bc.CardID
                    ) byCardInner
                    group by byCardInner.MerchantID
                ) byCard on m.MerchantID = byCard.MerchantID
                left join (
                    select
                        byCardAndMultiplierInner.MerchantID,
                        JSON_OBJECTAGG(
                            byCardAndMultiplierInner.CardID,
                            byCardAndMultiplierInner.CardCount
                        ) as json
                    from (
                        select
                            bcm.MerchantID,
                            bcm.CardID,
                            sum(if(mmst.MerchantID is null, 0, bcm.CardCount)) as CardCount
                        from MerchantCacheByMultiplierStatsTemp bcm
                        use index(idxMerchantIDCardID)
                        left join MerchantPeriodlyStatTemp mmst on 
                            mmst.MerchantID = bcm.MerchantID
                            and bcm.PeriodsOffset <= mmst.MaxPeriodsOffset
                        where
                            bcm.MerchantID >= :lowMerchantId 
                            and bcm.MerchantID < :highMerchantId
                        group by bcm.MerchantID, bcm.CardID
                    ) byCardAndMultiplierInner
                    group by byCardAndMultiplierInner.MerchantID
                ) byCardAndMultiplier on m.MerchantID = byCardAndMultiplier.MerchantID
                set
                    m.Stat = JSON_OBJECT(
                        'byCard', ifnull(byCard.json, json_array()),
                        'byCardAndMultiplier', ifnull(byCardAndMultiplier.json, json_array())
                    )
                where
                    (
                        m.MerchantID >= :lowMerchantId
                        and m.MerchantID < :highMerchantId
                    ) and "
                    . (
                        $merchantIds ?
                            ' m.MerchantID in (:merchantIds) ' :
                            ' m.MerchantID <= :maxMerchantId '
                    ),
                    \array_merge(
                        $merchantIds ?
                            ['merchantIds' => $merchantIds] :
                            ['maxMerchantId' => $maxMerchantId],
                        [
                            'lowMerchantId' => $lowMerchantId,
                            'highMerchantId' => $highMerchantId,
                        ]
                    ),
                    \array_merge(
                        $merchantIds ?
                            ['merchantIds' => Connection::PARAM_INT_ARRAY] :
                            ['maxMerchantId' => ParameterType::INTEGER],
                        [
                            'lowMerchantId' => ParameterType::INTEGER,
                            'highMerchantId' => ParameterType::INTEGER,
                        ]
                    )
                ),
                2_000
            );

            $this->smartStatement(
                'Updating MerchantPattern.Stat credit cards cache...',
                fn () => $this->connection->executeStatement("
                update MerchantPattern mp
                left join (
                    select
                        byCardInner.MerchantPatternID,
                        JSON_OBJECTAGG(
                            byCardInner.CardID,
                            byCardInner.CardCount
                        ) as json
                    from (
                        select
                            mp.MerchantPatternID,
                            bc.CardID,
                            sum(if(mppst.MerchantPatternID is null, 0, bc.CardCount)) as CardCount
                        from MerchantPattern mp 
                        join Merchant m on mp.MerchantPatternID = m.MerchantPatternID
                        join MerchantCacheByCardStatsTemp bc on bc.MerchantID = m.MerchantID
                        left join MerchantPatternPeriodlyStatTemp mppst on 
                            m.MerchantPatternID = mppst.MerchantPatternID
                            and bc.PeriodsOffset <= mppst.MaxPeriodsOffset
                        group by mp.MerchantPatternID, bc.CardID
                    ) byCardInner
                    group by byCardInner.MerchantPatternID
                ) byCard on mp.MerchantPatternID = byCard.MerchantPatternID
                left join (
                    select
                        byCardAndMultiplierInner.MerchantPatternID,
                        JSON_OBJECTAGG(
                            byCardAndMultiplierInner.CardID,
                            byCardAndMultiplierInner.CardCount
                        ) as json
                    from (
                        select
                            mp.MerchantPatternID,
                            bcm.CardID,
                            sum(if(mppst.MerchantPatternID is null, 0, bcm.CardCount)) as CardCount
                        from MerchantPattern mp 
                        join Merchant m on mp.MerchantPatternID = m.MerchantPatternID
                        join MerchantCacheByMultiplierStatsTemp bcm on bcm.MerchantID = m.MerchantID
                        left join MerchantPatternPeriodlyStatTemp mppst on 
                            m.MerchantPatternID = mppst.MerchantPatternID
                            and bcm.PeriodsOffset <= mppst.MaxPeriodsOffset
                        group by mp.MerchantPatternID, bcm.CardID
                    ) byCardAndMultiplierInner
                    group by byCardAndMultiplierInner.MerchantPatternID
                ) byCardAndMultiplier on mp.MerchantPatternID = byCardAndMultiplier.MerchantPatternID
                set
                    mp.Stat = JSON_OBJECT(
                        'byCard', ifnull(byCard.json, json_array()),
                        'byCardAndMultiplier', ifnull(byCardAndMultiplier.json, json_array())
                    )"
                )
            );

            $this->smartChunkedStatement(
                'Updating Merchant confidence intervals stat',
                fn (int $lowMerchantId, int $highMerchantId) => $this->connection->executeStatement('
                        update Merchant m
                        join MerchantPeriodlyStatTemp mmst on
                            mmst.MerchantID = m.MerchantID
                        set 
                            m.TransactionsConfidenceInterval = mmst.Transactions,
                            m.ConfidenceIntervalStartDate = mmst.StartDate
                        where 
                            (
                                m.MerchantID >= :lowMerchantId
                                and m.MerchantID < :highMerchantId
                            ) and '
                            . (
                                $merchantIds ?
                                    ' m.MerchantID in (:merchantIds) ' :
                                    ' m.MerchantID <= :maxMerchantId '
                            ),
                    \array_merge(
                        $merchantIds ?
                            ['merchantIds' => $merchantIds] :
                            ['maxMerchantId' => $maxMerchantId],
                        [
                            'lowMerchantId' => $lowMerchantId,
                            'highMerchantId' => $highMerchantId,
                        ]
                    ),
                    \array_merge(
                        $merchantIds ?
                            ['merchantIds' => Connection::PARAM_INT_ARRAY] :
                            ['maxMerchantId' => ParameterType::INTEGER],
                        [
                            'lowMerchantId' => ParameterType::INTEGER,
                            'highMerchantId' => ParameterType::INTEGER,
                        ]
                    )
                ),
                50_000
            );

            $this->smartStatement(
                'Updating MerchantPattern confidence intervals stat',
                fn () => $this->connection->executeStatement('
                        update MerchantPattern mp
                        join MerchantPatternPeriodlyStatTemp mmst on
                            mmst.MerchantPatternID = mp.MerchantPatternID
                        set 
                            mp.TransactionsConfidenceInterval = mmst.Transactions,
                            mp.ConfidenceIntervalStartDate = mmst.StartDate'
                )
            );

            $this->smartChunkedStatement(
                'updating Merchant Report',
                fn (int $lowMerchantId, int $highMerchantId) => $this->connection->executeStatement('
                    insert ignore into MerchantReport(
                        MerchantID,
                        CreditCardID,
                        ShoppingCategoryID,
                        Transactions,
                        Version,
                        ExpectedMultiplierTransactions
                    )
                    select 
                        MerchantReportStats.MerchantID,
                        MerchantReportStats.CreditCardID,
                        MerchantReportStats.ShoppingCategoryID,
                        MerchantReportStats.Transactions,
                        :version as Version,
                        coalesce(MerchantReportExpectedStats.ExpectedMultiplierTransactions, 0) as ExpectedMultiplierTransactions
                    from (
                        select 
                            trx.MerchantID,
                            trx.CreditCardID,
                            trx.ShoppingCategoryID,
                            sum(trx.Transactions) as Transactions
                        from MerchantReportTransactionsStatsTemp trx
                        join MerchantPeriodlyStatTemp mmst on 
                            mmst.MerchantID = trx.MerchantID
                            and trx.PeriodsOffset <= mmst.MaxPeriodsOffset
                        where
                            trx.MerchantID >= :lowMerchantId
                            and trx.MerchantID < :highMerchantId
                        group by 
                            trx.MerchantID,
                            trx.CreditCardID,
                            trx.ShoppingCategoryID
                    ) MerchantReportStats
                    left join (
                        select 
                            trx.MerchantID,
                            trx.CreditCardID,
                            trx.ShoppingCategoryID,
                            sum(trx.Transactions) as ExpectedMultiplierTransactions
                        from MerchantReportExpectedTransactionsStatsTemp trx
                        join MerchantPeriodlyStatTemp mmst on 
                            mmst.MerchantID = trx.MerchantID
                            and trx.PeriodsOffset <= mmst.MaxPeriodsOffset
                        where
                            trx.MerchantID >= :lowMerchantId
                            and trx.MerchantID < :highMerchantId
                        group by 
                            trx.MerchantID,
                            trx.CreditCardID,
                            trx.ShoppingCategoryID
                    ) MerchantReportExpectedStats on 
                        MerchantReportExpectedStats.MerchantID = MerchantReportStats.MerchantID
                        and MerchantReportExpectedStats.CreditCardID = MerchantReportStats.CreditCardID
                        and MerchantReportExpectedStats.ShoppingCategoryID = MerchantReportStats.ShoppingCategoryID
                    ',
                    [
                        'version' => $merchantReportTargetVersion,
                        'lowMerchantId' => $lowMerchantId,
                        'highMerchantId' => $highMerchantId,
                    ],
                    [
                        'version' => ParameterType::STRING,
                        'lowMerchantId' => ParameterType::INTEGER,
                        'highMerchantId' => ParameterType::INTEGER,
                    ],
                ),
                5_000
            );
            $this->paramRepository->setParam(ParameterRepository::MERCHANT_REPORT_VERSION, $merchantReportTargetVersion);
            $this->paramRepository->setParam(ParameterRepository::MERCHANT_UPPER_DATE, $nowDate->format('Y-m-d H:i:s'));
            $this->logger->info("MerchantReport version updated to {$merchantReportTargetVersion}.");
            $this->smartStatement(
                'Removing old Merchant Report rows',
                fn () => $this->connection->executeStatement(
                    'delete from MerchantReport where Version < ?',
                    [$merchantReportTargetVersion]
                )
            );

            foreach ($this->tempTablesDefinitions as $tableDefinition) {
                $this->smartStatement(
                    "Removing table {$tableDefinition->name}...",
                    fn () => $this->connection->executeStatement("drop table `{$tableDefinition->name}`")
                );
            }

            $this->smartStatement(
                "Removing table MerchantPeriodlyStatTemp...",
                fn () => $this->connection->executeStatement("drop table MerchantPeriodlyStatTemp")
            );

            $this->smartStatement(
                "Removing table MerchantPatternPeriodlyStatTemp...",
                fn () => $this->connection->executeStatement("drop table MerchantPatternPeriodlyStatTemp")
            );

            $this->logger->info("done");
        }

        protected function smartStatement(string $name, callable $block, int $maxAttempts = self::MAX_STATEMENT_ATTEMPTS)
        {
            $this->logger->info($name);
            $start = $this->clock->current();
            $sleepTime = milliseconds(100);

            foreach (\iter\range(1, $maxAttempts) as $attempt) {
                try {
                    $affectedCount = $block();
                    $lastException = null;

                    break;
                } catch (DeadlockException|LockWaitTimeoutException $lastException) {
                    $sleepTime = $sleepTime->times(2 ** ($attempt - 1));
                    $this->logger->info(\get_class($lastException) . " on {$attempt} attempt, " . (($attempt < $maxAttempts) ? "retrying in {$sleepTime}..." : 'giving up!!!'));

                    if ($attempt < $maxAttempts) {
                        $this->clock->sleep($sleepTime);
                    }
                }
            }

            if (isset($lastException)) {
                throw $lastException;
            }

            $duration = $this->clock->current()->sub($start);
            $this->logger->info(\sprintf('Finished,%s took %0.2f min(s)%s',
                $affectedCount !== null ? " affected {$affectedCount} row(s)," : '',
                $duration->getAsMinutesFractionFloat(),
                $attempt > 1 ? ", {$attempt} attempts" : ""
            ));
        }

        protected function smartChunkedStatement(
            string $name,
            callable $block,
            int $batchSize = self::UPDATE_BATCH_SIZE,
            int $maxAttempts = self::MAX_STATEMENT_ATTEMPTS
        ) {
            $this->smartStatement(
                $name,
                function () use ($batchSize, $block) {
                    $totalResult = null;

                    foreach ($this->generateBatchRange($batchSize) as [$startIdx, $endIdx]) {
                        $start = $this->clock->monothonic();
                        $result = $block($startIdx, $endIdx);

                        if ($result !== null) {
                            $totalResult = ($totalResult ?? 0) + $result;
                        }

                        $time = $this->clock->monothonic()->sub($start)->scaleToMillis();
                        $this->logger->info("batch range [{$startIdx}, {$endIdx}), took {$time}, affected {$result}");
                    }

                    return $totalResult;
                },
                $maxAttempts
            );
        }

        private function buildMerchantBuckets(): void
        {
            $stmtIds = $this->replicaUnbufferedConnection->executeQuery('select MerchantID from Merchant');
            $buckets = [];

            foreach (stmtColumn($stmtIds) as $id) {
                @$buckets[(int) (\ceil($id / self::BATCH_RESOLUTION_SIZE) * self::BATCH_RESOLUTION_SIZE)]++;
            }

            \ksort($buckets);
            $this->merchantBuckets = $buckets;
        }

        private function generateBatchRange(int $batchSize): iterable
        {
            $rangeGen = function () use ($batchSize) {
                $sum = 0;

                yield 0;

                foreach ($this->merchantBuckets as $startId => $count) {
                    $sum += $count;

                    if ($sum >= $batchSize) {
                        yield $startId;

                        $sum = 0;
                    }
                }

                yield $startId + 1;
            };

            return
                it($rangeGen())
                ->sliding(2);
        }

        private function createTempTables()
        {
            $this->logger->info('creating temp tables');

            foreach ($this->tempTablesDefinitions as $tableDefinition) {
                $this->connection->executeStatement("drop table if exists `{$tableDefinition->name}`");
                $this->connection->executeStatement("create table `{$tableDefinition->name}` {$tableDefinition->createTable}");
            }
        }

        private function flushStats(int $maxUpdatePacketBytes)
        {
            $startTime = $this->clock->current();
            $this->logger->info('flushing stats (memory: ' . \number_format((int) \round(\memory_get_usage(true) / (1024 * 1024))) . ' MB)...');
            $hashKeySplitter = static fn ($merchantKey) => \explode('#', $merchantKey);
            $scalarKeySqlMapper = static fn ($count, $partsKey) => "({$partsKey},{$count})";
            $arrayKeySqlMapper = static fn ($count, array $partsKey) => '(' . \implode(',', $partsKey) . ",{$count})";

            $update = function (
                array &$map,
                string $table,
                callable $sqlValuesMapper,
                ?callable $keySplitter = null,
                bool $isTemporal = true
            ) use ($maxUpdatePacketBytes): int {
                if (!$map) {
                    return 0;
                }

                $sqlInsertHeader = "INSERT INTO {$table} VALUES ";
                $iter = it($map);

                if ($keySplitter) {
                    $iter = $iter->mapKeys($keySplitter);
                }

                if ($isTemporal) {
                    $iter = $iter->flatMapIndexed(function (array $count, $key) {
                        $newKeyCommon = (array) $key;

                        // temporal data
                        foreach ($count as $periodOffset => $periodCount) {
                            $newKey = $newKeyCommon;
                            $newKey[] = $periodOffset;

                            yield $newKey => $periodCount;
                        }
                    });
                }

                $this->batchInsert(
                    $iter->mapIndexed($sqlValuesMapper),
                    $sqlInsertHeader,
                    $maxUpdatePacketBytes
                );

                $counter = \count($map);
                $map = [];

                return $counter;
            };

            $insertedTransactionsCount = $update(
                $this->transactionsCountMap,
                'MerchantTransactionsStatsTemp(MerchantID, PeriodsOffset, Transactions)',
                $arrayKeySqlMapper,
            );

            $insertedTransactionsLast3MonthsCount = $update(
                $this->transactionsLast3MonthsCountMap,
                'MerchantTransactionsLast3MonthsStatsTemp(MerchantID, Transactions)',
                $scalarKeySqlMapper,
                null,
                false
            );

            $insertedPopularCategoryCount = $update(
                $this->popularCategoryCountMap,
                'MerchantPopularShoppingCategoryStatsTemp(MerchantID, ShoppingCategoryID, PeriodsOffset, Transactions)',
                $arrayKeySqlMapper,
                $hashKeySplitter,
            );

            $insertedStatByCardCount = $update(
                $this->statByCardCountMap,
                'MerchantCacheByCardStatsTemp(MerchantID, CardID, PeriodsOffset, CardCount)',
                $arrayKeySqlMapper,
                $hashKeySplitter
            );

            $insertedStatByMultiplierCount = $update(
                $this->statByMultiplierCountMap,
                'MerchantCacheByMultiplierStatsTemp(MerchantID, CardID, PeriodsOffset, CardCount)',
                static fn ($count, array $partsKey) => "({$partsKey[0]},'{$partsKey[1]}',{$partsKey[2]},{$count})",
                $hashKeySplitter
            );

            $insertedMerchantReportTransactionsCount = $update(
                $this->merchantReportTransactionsCount,
                'MerchantReportTransactionsStatsTemp(MerchantID, CreditCardID, ShoppingCategoryID, PeriodsOffset, Transactions)',
                $arrayKeySqlMapper,
                $hashKeySplitter
            );

            $insertedMerchantReportExpectedTransactionsCount = $update(
                $this->merchantReportExpectedMultiplierTransactionsCount,
                'MerchantReportExpectedTransactionsStatsTemp(MerchantID, CreditCardID, ShoppingCategoryID, PeriodsOffset, Transactions)',
                $arrayKeySqlMapper,
                $hashKeySplitter
            );

            $this->logger->info('memory before GC: ' . \number_format((int) \round(\memory_get_usage(true) / (1024 * 1024))) . ' MB');
            \gc_collect_cycles();
            $this->logger->info(
                'flushed. '
                . "transactions: {$insertedTransactionsCount}, "
                . "transactions last 3 months: {$insertedTransactionsLast3MonthsCount}, "
                . "popular categories: {$insertedPopularCategoryCount}, "
                . "by card: {$insertedStatByCardCount}, "
                . "by multiplier: {$insertedStatByMultiplierCount}, "
                . "merchant report count: {$insertedMerchantReportTransactionsCount}, "
                . "merchant report expected count: {$insertedMerchantReportExpectedTransactionsCount}, "
                . 'time: ' . \number_format($this->clock->current()->sub($startTime)->getAsMinutesFractionFloat(), 2) . ' min(s)'
                . 'memory: ' . \number_format((int) \round(\memory_get_usage(true) / (1024 * 1024))) . ' MB'
            );
        }

        private function calculateTempStats(int $maxMemoryAllowedMb, int $maxUpdatePacketMb, array $merchantIds, ?\DateTimeInterface $startDate, \DateTimeImmutable $now): int
        {
            $maxUpdatePacketBytes = $maxUpdatePacketMb * 1024 * 1024;
            $this->logger->info("max memory allowed: {$maxMemoryAllowedMb} MB");
            $this->logger->info("analyzing merchant stats, loading subaccounts");
            $subToCardMap = $this->replicaUnbufferedConnection->fetchAllKeyValue(
                "select SubAccountID, CreditCardID from SubAccount where CreditCardID is not null"
            );
            $cardCashbackMap = it(
                $this->replicaUnbufferedConnection->fetchAllKeyValue(
                    "select CreditCardID, IsCashBackOnly from CreditCard where IsCashBackOnly = 1"
                ))
                ->map(fn ($flag) => (bool) $flag)
                ->toArrayWithKeys();
            $scMap = $this->replicaUnbufferedConnection->fetchAllKeyValue('
                select 
                    sc.ShoppingCategoryID, 
                    coalesce(sc.ShoppingCategoryGroupID, 0) as ShoppingCategoryGroupID
                from ShoppingCategory sc
            ');
            $ccscgMap =
                it(
                    $this->replicaUnbufferedConnection->executeQuery('
                    select 
                        coalesce(ShoppingCategoryGroupID, 0) as ShoppingCategoryGroupID,
                        CreditCardID,
                        StartDate,
                        EndDate,
                        Multiplier
                    from CreditCardShoppingCategoryGroup
                '))
                ->reindex(fn (array $row) => $row['ShoppingCategoryGroupID'] . '#' . $row['CreditCardID'])
                ->toArrayWithKeys();
            $this->createTempTables();

            $maxMerchantId = (int) $this->replicaUnbufferedConnection->fetchOne('
                select max(MerchantID) from Merchant
            ');
            $this->logger->info('max MerchantID: ' . $maxMerchantId);
            $this->logger->info("opening main query");

            $this->replicaUnbufferedConnection->setTransactionIsolation(TransactionIsolationLevel::READ_UNCOMMITTED);
            $q = $this->replicaUnbufferedConnection->executeQuery("
                select 
                    h.MerchantID, 
                    h.SubAccountID,
                    h.Multiplier,
                    h.ShoppingCategoryID,
                    h.PostingDate,
                    h.Amount,
                    h.Miles
                from AccountHistory h
                where " .
                    ($startDate ? 'h.PostingDate >= ? AND ' : '')
                    . (
                        ($merchantIds) ?
                            ('h.MerchantID in (?)') :
                            "h.MerchantID is not null"
                    ),
                \array_merge(
                    $startDate ? [$startDate->format('Y-m-d H:i:s')] : [],
                    $merchantIds ? [$merchantIds] : []
                ),
                \array_merge(
                    $startDate ? [ParameterType::STRING] : [],
                    $merchantIds ? [Connection::PARAM_INT_ARRAY] : []
                )
            );

            $this->logger->info("reading AccountHistory");
            $lastStopIteration = 0;
            $lastStopTime = $startTime = $this->clock->current();
            $ignoredCategoriesMap = \array_flip(ShoppingCategory::IGNORED_CATEGORIES);
            $nowString = $now->format('Y-m-d H:i:s');
            $last3MonthsString = $now->modify('-3 months')->format('Y-m-d H:i:s');
            \gc_collect_cycles();
            \gc_disable();

            try {
                foreach (
                    stmt($q)
                        ->onNthMillisAndLast(
                            30_000,
                            $this->makePeriodicLogger(
                                'AccountHistory: ',
                                $startTime,
                                $lastStopIteration,
                                $lastStopTime
                            )
                        ) as $i => [
                            $merchantId,
                            $subAccountID,
                            $multiplier,
                            $shoppingCategoryId,
                            $postingDate,
                            $amount,
                            $miles,
                        ]
                ) {
                    if ($i % 100_000 === 0) {
                        $memory = \memory_get_usage(true) / (1024 * 1024);

                        if ($memory > $maxMemoryAllowedMb) {
                            $this->logger->info(\sprintf('memory is full (%0.1f), flushing...', $memory));

                            $this->flushStats($maxUpdatePacketBytes);
                        }
                    }

                    $days = $now->diff(new \DateTime($postingDate))->days;

                    if ($days < 0) {
                        continue;
                    }

                    $periodsOffset = $this->searchPeriod($days);

                    if ($periodsOffset < 0) {
                        continue;
                    }

                    $periodsOffset = (int) \ceil($periodsOffset);

                    self::updateTemporalCount($this->transactionsCountMap, $merchantId, $periodsOffset);

                    if (
                        ($postingDate >= $last3MonthsString)
                        && ($postingDate <= $nowString)
                    ) {
                        @++$this->transactionsLast3MonthsCountMap[$merchantId];
                    }

                    if (
                        null !== $shoppingCategoryId
                        && !\array_key_exists($shoppingCategoryId, $ignoredCategoriesMap)
                    ) {
                        self::updateTemporalCount(
                            $this->popularCategoryCountMap,
                            $merchantId . '#' . $shoppingCategoryId,
                            $periodsOffset
                        );
                    }

                    if (
                        null !== $subAccountID
                        && (null !== ($cardId = $subToCardMap[$subAccountID] ?? null))
                    ) {
                        self::updateTemporalCount(
                            $this->statByCardCountMap,
                            $merchantId . '#' . $cardId,
                            $periodsOffset
                        );

                        if (null !== $multiplier) {
                            if (($multiplier < 1) && ($cardCashbackMap[$cardId] ?? false)) {
                                $multiplier = \round(\round($miles * 100) / $amount, 1);
                                $multiplier = \round(\round($multiplier * 2) / 2, 1); // discard rounding errors
                            }

                            self::updateTemporalCount(
                                $this->statByMultiplierCountMap,
                                $merchantId . '#' . Merchant::getCardAndMultiplierStatKey(
                                    $cardId,
                                    $multiplier
                                ),
                                $periodsOffset
                            );
                        }

                        if (
                            ($amount > 0)
                            && ($miles > 0)
                            && ($postingDate >= $last3MonthsString)
                            && ($postingDate <= $nowString)
                        ) {
                            $merchantReportStatKey = $merchantId
                                . "#{$cardId}"
                                . '#' . ($shoppingCategoryId ?? 0);
                            self::updateTemporalCount(
                                $this->merchantReportTransactionsCount,
                                $merchantReportStatKey,
                                $periodsOffset
                            );
                            $shoppingCategoryGroupId = $scMap[$shoppingCategoryId] ?? 0;
                            $ccscgKey = "{$shoppingCategoryGroupId}#{$cardId}";

                            if ($ccsgData = ($ccscgMap[$ccscgKey] ?? null)) {
                                [
                                    'StartDate' => $ccscgStartDate,
                                    'EndDate' => $ccscgEndDate,
                                    'Multiplier' => $ccscgMultiplier,
                                ] = $ccsgData;

                                if (
                                    (
                                        (null === $ccscgStartDate)
                                        || ($ccscgStartDate <= $postingDate)
                                    )
                                    && (
                                        (null === $ccscgEndDate)
                                        || ($ccscgEndDate > $postingDate)
                                    )
                                    && (\abs($ccscgMultiplier - $multiplier) < 0.5)
                                ) {
                                    self::updateTemporalCount(
                                        $this->merchantReportExpectedMultiplierTransactionsCount,
                                        $merchantReportStatKey,
                                        $periodsOffset
                                    );
                                }
                            }
                        }
                    }
                }

                $this->flushStats($maxUpdatePacketBytes);
                $this->createTempIndexes();
            } finally {
                \gc_enable();
            }

            return $maxMerchantId;
        }

        private function searchPeriod(int $days): int
        {
            $low = 0;
            $maxIdx = $this->PERIODS_COUNT - 1;
            $high = $maxIdx;

            while ($low <= $high) {
                $mid = (int) (($low + $high) / 2);
                $period = $this->PERIODS[$mid];
                $periodStart = $period[0];

                if ($days > $periodStart) {
                    $periodEnd = $period[1];

                    if ($days < $periodEnd) {
                        return $mid;
                    } elseif ($days > $periodEnd) {
                        $low = $mid + 1;

                        if ($low > $maxIdx) {
                            return $maxIdx;
                        }
                    } else {
                        return \min($mid + 1, $maxIdx);
                    }
                } elseif ($days < $periodStart) {
                    $high = $mid - 1;
                } else {
                    return $mid;
                }
            }

            return -1;
        }

        private static function updateTemporalCount(array &$map, $key, int $periodsOffset): void
        {
            if (\array_key_exists($key, $map)) {
                if (
                    (null !== ($prevCount = ($map[$key][$periodsOffset] ?? null)))
                    && ($prevCount < self::MAX_TEMPORAL_TEMPLATE_COUNT)
                    && (\count($map[$key]) === 1)
                ) {
                    $map[$key] = self::$TEMPORAL_TEMPLATES[$periodsOffset][$prevCount];
                } else {
                    @$map[$key][$periodsOffset]++;
                }
            } else {
                $map[$key] = self::$TEMPORAL_TEMPLATES[$periodsOffset][0];
            }
        }

        private function createTempIndexes()
        {
            foreach ($this->tempTablesDefinitions as $tableDefinition) {
                $this->smartStatement(
                    "creating {$tableDefinition->name} index",
                    fn () => $this->connection->executeStatement("
                        alter table `{$tableDefinition->name}` {$tableDefinition->addIndex}
                    ")
                );
            }
        }

        private function batchInsert(IteratorFluent $iter, string $sqlInsertHeader, int $maxUpdatePacketBytes): void
        {
            /** @var IteratorFluent $transactionsChunkIter */
            foreach (
                $iter
                ->reductions(
                    fn (array $lastReduction, string $row) => [
                        $row,
                        $lastReduction[self::REDUCTION_TOTAL_LENGTH_INDEX]
                            + \strlen($row)
                            + 1, // for comma between VALUES
                    ],
                    [
                        null,
                        \strlen($sqlInsertHeader) // for insert into header
                            - 1, // no comma after last VALUES
                    ]
                )
                ->groupAdjacentByLazy(static fn (array $reduction1, array $reduction2) =>
                    ((int) ($reduction1[self::REDUCTION_TOTAL_LENGTH_INDEX] / $maxUpdatePacketBytes))
                    <=> ((int) ($reduction2[self::REDUCTION_TOTAL_LENGTH_INDEX] / $maxUpdatePacketBytes))
                ) as $transactionsChunkIter
            ) {
                $this->connection->executeStatement(
                    $sqlInsertHeader
                    . $transactionsChunkIter
                        ->map(static fn (array $reduction) => $reduction[self::REDUCTION_DATA_INDEX])
                        ->joinToString(',')
                );
            }
        }

        private function makePeriodicLogger(string $logPrefix, Duration $startTime, int &$lastStopIteration, Duration &$lastStopTime)
        {
            return function ($_1, int $iteration, $_2, $_3, bool $isLast) use (
                &$lastStopIteration,
                &$lastStopTime,
                $startTime,
                $logPrefix
            ) {
                $stopTime = $this->clock->current();
                $this->logger->info(
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

        private function calculateMerchantConfidencePeriodInterval(int $confidenceTransactionsAmount): void
        {
            $this->connection->executeQuery('
                DROP TABLE IF EXISTS MerchantPeriodlyStatTemp
            ');
            $this->connection->executeQuery('
                CREATE TABLE MerchantPeriodlyStatTemp (
                    MerchantID int not null,
                    MaxPeriodsOffset int not null,
                    StartDate datetime not null,
                    Transactions int not null
                )
            ');

            $lastPeriodStartDate = $this->PERIODS[\count($this->PERIODS) - 1][2]->format('Y-m-d H:i:s');
            $this->smartStatement(
                'Calculate confidence period for Merchants',
                fn () => $this->connection->executeStatement('
                    insert into MerchantPeriodlyStatTemp(MerchantID, MaxPeriodsOffset, StartDate, Transactions)
                    select 
                        statAgg.MerchantID,
                        statAgg.MaxPeriodsOffset,
                        case ' .
                            it($this->PERIODS)
                            ->take(\count($this->PERIODS) - 1)
                            ->mapIndexed(fn (array $period, int $idx) =>
                                "when statAgg.MaxPeriodsOffset = {$idx} then '" . $period[2]->format('Y-m-d H:i:s') . "'"
                            )
                            ->joinToString("\n")
                        . '
                            else \'' . $lastPeriodStartDate . '\'
                         end as StartDate,
                         statAgg.Transactions
                    from (
                        select
                            statOuter.MerchantID,
                            min(statOuter.PeriodLimit) as MaxPeriodsOffset,
                            min(statOuter.Transactions) as Transactions
                        from (
                            select
                                statInner.MerchantID,
                                FIRST_VALUE(PeriodsOffset) over (partition by MerchantID order by if(statInner.RunningTotalTransactions > :confidenceAmount, 0, 1), ABS(statInner.RunningTotalTransactions - :confidenceAmount)) as PeriodLimit,
                                FIRST_VALUE(RunningTotalTransactions) over (partition by MerchantID order by if(statInner.RunningTotalTransactions > :confidenceAmount, 0, 1), ABS(statInner.RunningTotalTransactions - :confidenceAmount)) as Transactions
                            from (
                                select
                                    MerchantID,
                                    PeriodsOffset,
                                    sum(sum(Transactions)) over (partition by MerchantID order by PeriodsOffset) as RunningTotalTransactions
                                from MerchantTransactionsStatsTemp
                                group by MerchantID, PeriodsOffset
                            ) statInner
                        ) statOuter
                        group by MerchantID
                    ) statAgg',
                    [
                        'confidenceAmount' => $confidenceTransactionsAmount,
                    ]
                )
            );

            $this->smartStatement(
                "Adding index to MerchantPeriodlyStatTemp",
                fn () => $this->connection->executeStatement('
                    alter table MerchantPeriodlyStatTemp add index idxMerchantIdPeriod(MerchantID, MaxPeriodsOffset)
                ')
            );
        }

        private function calculateMerchantPatternConfidencePeriodInterval(int $confidenceTransactionsAmount): void
        {
            $this->connection->executeQuery('
                DROP TABLE IF EXISTS MerchantPatternPeriodlyStatTemp
            ');
            $this->connection->executeQuery('
                CREATE TABLE MerchantPatternPeriodlyStatTemp (
                    MerchantPatternID int not null,
                    MaxPeriodsOffset int not null,
                    StartDate datetime not null,
                    Transactions int not null
                )
            ');

            $lastPeriodStartDate = $this->PERIODS[\count($this->PERIODS) - 1][2]->format('Y-m-d H:i:s');
            $this->smartStatement(
                'Calculate confidence period for MerchantPatterns',
                fn () => $this->connection->executeStatement('
                    insert into MerchantPatternPeriodlyStatTemp(MerchantPatternID, MaxPeriodsOffset, StartDate, Transactions)
                    select 
                        statAgg.MerchantPatternID,
                        statAgg.MaxPeriodsOffset,
                        case ' .
                            it($this->PERIODS)
                            ->take(\count($this->PERIODS) - 1)
                            ->mapIndexed(fn (array $period, int $idx) =>
                                "when statAgg.MaxPeriodsOffset = {$idx} then '" . $period[2]->format('Y-m-d H:i:s') . "'"
                            )
                            ->joinToString("\n")
                        . '
                            else \'' . $lastPeriodStartDate . '\'
                         end as StartDate,
                         statAgg.Transactions
                    from (
                        select
                            statOuter.MerchantPatternID,
                            min(statOuter.PeriodLimit) as MaxPeriodsOffset,
                            min(statOuter.Transactions) as Transactions
                        from (
                            select
                                statInner.MerchantPatternID,
                                FIRST_VALUE(PeriodsOffset) over (partition by MerchantPatternID order by if(statInner.RunningTotalTransactions > :confidenceAmount, 0, 1), ABS(statInner.RunningTotalTransactions - :confidenceAmount)) as PeriodLimit,
                                FIRST_VALUE(RunningTotalTransactions) over (partition by MerchantPatternID order by if(statInner.RunningTotalTransactions > :confidenceAmount, 0, 1), ABS(statInner.RunningTotalTransactions - :confidenceAmount)) as Transactions
                            from (
                                select
                                    mp.MerchantPatternID,
                                    PeriodsOffset,
                                    sum(sum(mtst.Transactions)) over (partition by mp.MerchantPatternID order by PeriodsOffset) as RunningTotalTransactions
                                from MerchantPattern mp
                                join Merchant m on mp.MerchantPatternID = m.MerchantPatternID
                                join MerchantTransactionsStatsTemp mtst on mtst.MerchantID = m.MerchantID
                                group by mp.MerchantPatternID, PeriodsOffset
                            ) statInner
                        ) statOuter
                        group by MerchantPatternID
                    ) statAgg',
                    [
                        'confidenceAmount' => $confidenceTransactionsAmount,
                    ]
                )
            );

            $this->smartStatement(
                "Adding index to MerchantPatternPeriodlyStatTemp",
                fn () => $this->connection->executeStatement('
                    alter table MerchantPatternPeriodlyStatTemp add index idxMerchantPatternIdPeriod(MerchantPatternID, MaxPeriodsOffset)
                ')
            );
        }

        private function deleteMerchantsWithoutTransactions(): void
        {
            $this->logger->info("deleting merchants without transactions...");
            $q = $this->replicaUnbufferedConnection->executeQuery("select MerchantID from Merchant where Transactions = 0");
            stmtColumn($q)
                ->onNthMillis(10000, function (int $millisFromStart, int $iteration, $currentValue, $currentKey) {
                    $this->logger->info("deleted merchants without transactions...$iteration");
                })
                ->chunk(100)
                ->apply(function (array $ids) {
                    $this->connection->executeStatement("delete from Merchant where MerchantID in (?)", [$ids], [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY]);
                    $this->sphinxConnection->executeStatement("delete from Merchant where id in (?)", [$ids], [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY]);
                });
        }
    }
}

namespace AwardWallet\MainBundle\Service\CreditCards\Commands\AnalyzeMerchantStatsCommand\Data {
    class Table
    {
        public string $name;
        public string $createTable;
        public string $addIndex;

        public function __construct(
            string $name,
            string $createTable,
            string $addIndex
        ) {
            $this->name = $name;
            $this->createTable = $createTable;
            $this->addIndex = $addIndex;
        }
    }
}
