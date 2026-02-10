<?php

namespace AwardWallet\MainBundle\Service\AccountHistory;

use AwardWallet\MainBundle\Entity\AccountHistory;
use AwardWallet\MainBundle\Entity\CreditCard;
use AwardWallet\MainBundle\Entity\Merchant;
use AwardWallet\MainBundle\Entity\Repositories\ParameterRepository;
use AwardWallet\MainBundle\Entity\ShoppingCategory;
use AwardWallet\MainBundle\Entity\ShoppingCategoryGroup;
use AwardWallet\MainBundle\Entity\UserCreditCard;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\NumberHandler;
use AwardWallet\MainBundle\Service\AccountHistory\MerchantRecentTransactionsStatLoader\MerchantRecentTransactionsStatLoader;
use AwardWallet\MainBundle\Service\AccountHistory\MerchantRecentTransactionsStatLoader\Stat;
use AwardWallet\MainBundle\Service\MileValue\MileValueService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class SpentAnalysisService
{
    public const DEFAULT_MERCHANT_DATA_LIMIT = 10;
    public const MIN_MERCHANT_TRANSACTIONS = 5;
    public const MIN_MULTIPLIER_TRANSACTIONS = 1;

    public const CARDS_GROUP_LIST = 'list';
    public const CARDS_GROUP_EXCLUDED = 'excluded';
    public const CARDS_GROUP_FROM_RECENT_TRANSACTIONS = 'fromRecentTransactions';
    public const CARDS_GROUP_FULL = [
        self::CARDS_GROUP_LIST,
        self::CARDS_GROUP_EXCLUDED,
        self::CARDS_GROUP_FROM_RECENT_TRANSACTIONS,
    ];

    private LoggerInterface $logger;
    private BankTransactionsAnalyser $analyser;
    private TranslatorInterface $translator;
    private EntityManagerInterface $em;
    private ParameterRepository $parameterRepository;
    private AwTokenStorageInterface $tokenStorage;
    private AnalyserContextFactory $contextFactory;
    private bool $isDebug;
    private MileValueService $mileValueService;
    private Connection $connection;
    private MerchantRecentTransactionsStatLoader $merchantRecentTransactionsStatLoader;
    private HistoryRowValueCalculator $historyRowValueCalculator;
    private LocalizeService $localizeService;
    private ?Context $cacheContext = null;

    public function __construct(
        LoggerInterface $logger,
        BankTransactionsAnalyser $analyser,
        TranslatorInterface $translator,
        EntityManagerInterface $em,
        ParameterRepository $parameterRepository,
        AwTokenStorageInterface $tokenStorage,
        AnalyserContextFactory $contextFactory,
        bool $debug,
        MileValueService $mileValueService,
        Connection $connection,
        MerchantRecentTransactionsStatLoader $merchantRecentTransactionsStatLoader,
        HistoryRowValueCalculator $historyRowValueCalculator,
        LocalizeService $localizeService
    ) {
        $this->logger = $logger;
        $this->analyser = $analyser;
        $this->translator = $translator;
        $this->em = $em;
        $this->parameterRepository = $parameterRepository;
        $this->tokenStorage = $tokenStorage;
        $this->isDebug = $debug;
        $this->contextFactory = $contextFactory;
        $this->mileValueService = $mileValueService;
        $this->connection = $connection;
        $this->merchantRecentTransactionsStatLoader = $merchantRecentTransactionsStatLoader;
        $this->historyRowValueCalculator = $historyRowValueCalculator;
        $this->localizeService = $localizeService;
    }

    public function transactionsExists(array $ids): array
    {
        $result = [];
        $availableRanges = [
            BankTransactionsDateUtils::THIS_MONTH,
            BankTransactionsDateUtils::THIS_QUARTER,
            BankTransactionsDateUtils::LAST_MONTH,
            BankTransactionsDateUtils::LAST_QUARTER,
            BankTransactionsDateUtils::LAST_YEAR,
            BankTransactionsDateUtils::THIS_YEAR,
        ];

        $existed = $this->analyser->checkTransactionsExistsNew($ids);

        foreach ($availableRanges as $range) {
            $limits = BankTransactionsDateUtils::findRangeLimits($range);
            $start = new \DateTime($limits['start']);
            $cardsInfo = [];

            foreach ($ids as $id) {
                $noTransactions = true;

                if (isset($existed[$id])) {
                    $current = new \DateTime($existed[$id]);
                    $noTransactions = $start > $current;
                }

                $cardsInfo[] = [
                    'subAccountId' => $id,
                    'noTransactions' => $noTransactions,
                ];
            }

            $result[] = [
                'dateRange' => $range,
                'cardsInfo' => $cardsInfo,
            ];
        }

        return $result;
    }

    public function isUserTransactionsExists(Usr $user): bool
    {
        return (bool) $this->em->getConnection()->fetchOne('
            SELECT EXISTS (
                SELECT 1
                FROM AccountHistory ah
                JOIN Account a ON a.AccountID = ah.AccountID
                JOIN SubAccount sa ON sa.AccountID = a.AccountID
                JOIN CreditCard cc ON cc.CreditCardID = sa.CreditCardID
                WHERE a.UserID = ?
            );
        ',
            [$user->getId()],
            [\PDO::PARAM_INT]
        );
    }

    public function merchantsData(
        array $subAccIds,
        int $range,
        array $filterIds,
        ?int $dataLimit = self::DEFAULT_MERCHANT_DATA_LIMIT,
        ?Usr $user = null
    ): array {
        $dateLimits = BankTransactionsDateUtils::findRangeLimits($range);
        $context = $this->getCacheContext();

        $rows = $this->analyser->merchantAnalytics(
            $user ?? $this->tokenStorage->getUser(),
            $subAccIds,
            $dateLimits['start'],
            $dateLimits['end'],
            $filterIds,
            $context
        );
        $realLimits = $this->analyser->transactionsActualDates($subAccIds, $dateLimits['start'], $dateLimits['end']);

        $counter = 0;
        $result = [];
        $other = $total = [
            'merchantId' => 0,
            'merchantName' => '',
            'amount' => 0,
            'transactions' => 0,
            'miles' => 0,
            'potentialMiles' => 0,
            'milesValue' => 0,
            'potentialMilesValue' => 0,
        ];

        foreach ($rows as $row) {
            ++$counter;

            if ($counter < $dataLimit) {
                $row['earningPotentialColor'] = BankTransactionsAnalyser::potentialValueDiffColor($row['milesValue'], $row['potentialMilesValue']);
                $result[] = $row;
            } else {
                $other['amount'] += $row['amount'];
                $other['transactions'] += $row['transactions'];
                $other['miles'] += $row['miles'];
                $other['potentialMiles'] += $row['potentialMiles'];
                $other['milesValue'] += $row['milesValue'];
                $other['potentialMilesValue'] += $row['potentialMilesValue'];
            }

            $total['amount'] += $row['amount'];
            $total['transactions'] += $row['transactions'];
            $total['miles'] += $row['miles'];
            $total['potentialMiles'] += $row['potentialMiles'];
            $total['milesValue'] += $row['milesValue'];
            $total['potentialMilesValue'] += $row['potentialMilesValue'];
        }

        if ($counter > $dataLimit) {
            $other['multiplier'] = round($other['miles'] / $other['amount'], 1);
            $other['potential'] = round($other['potentialMiles'] / $other['amount'], 1);
            $other['merchantName'] = $this->translator->trans(/** @Desc("Other") */ "spent-analysis.other-merchants-name");
            $other['earningPotentialColor'] = BankTransactionsAnalyser::potentialValueDiffColor($other['milesValue'], $other['potentialMilesValue']);

            $result[] = $other;
        }

        if ($counter > 0) {
            $total['multiplier'] = round($total['miles'] / $total['amount'], 1);
            $total['potential'] = round($total['potentialMiles'] / $total['amount'], 1);
            $total['merchantName'] = $this->translator->trans(/** @Desc("Total") */ "account.details.totals.total");
            $total['earningPotentialColor'] = BankTransactionsAnalyser::potentialValueDiffColor($total['milesValue'], $total['potentialMilesValue']);

            $result[] = $total;
        }

        return [
            'data' => $result,
            'realStartDate' => $realLimits['start'],
            'realEndDate' => $realLimits['end'],
        ];
    }

    public function merchantTransactions(array $subAccIds, int $range, $merchantId, array $filterIds)
    {
        $dateLimits = BankTransactionsDateUtils::findRangeLimits($range);
        $context = $this->getCacheContext();

        return $this->analyser->merchantTransactions(
            $this->tokenStorage->getUser(),
            $subAccIds,
            $dateLimits['start'],
            $dateLimits['end'],
            $merchantId,
            $filterIds,
            $context
        );
    }

    public function getTotals(array $rows): array
    {
        $totals = [
            'transactions' => count($rows),
            'amount' => 0,
            'miles' => 0,
            'average' => 0,
            'multiplier' => 0,
            'milesValue' => 0,
            'potentialMilesValue' => 0,
        ];

        foreach ($rows as $row) {
            $totals['amount'] += $row['amount'];
            $totals['miles'] += $row['miles'];

            $totals['milesValue'] += $row['milesValue'];
            $totals['potentialMilesValue'] += $row['potentialMilesValue'];
        }
        $totals['potentialMilesValue'] = round($totals['potentialMilesValue'], 2);

        $totals['average'] = $totals['transactions'] > 0 ? round($totals['amount'] / $totals['transactions'], 2) : 0;
        $totals['diffCashEq'] = BankTransactionsAnalyser::potentialDiffColorByCashEq($totals['milesValue'], $totals['potentialMilesValue']);
        $totals['isProfit'] = $totals['milesValue'] >= $totals['potentialMilesValue'];

        $totals['multiplier'] = $totals['miles'] > 0 ? MultiplierService::calculate($totals['amount'], $totals['miles'], 0) : 0;

        return $totals;
    }

    public function validateRange(int $range): bool
    {
        $availableRanges = [
            BankTransactionsDateUtils::THIS_MONTH,
            BankTransactionsDateUtils::THIS_QUARTER,
            BankTransactionsDateUtils::THIS_YEAR,
            BankTransactionsDateUtils::LAST_MONTH,
            BankTransactionsDateUtils::LAST_QUARTER,
            BankTransactionsDateUtils::LAST_YEAR,
        ];

        if (in_array($range, $availableRanges)) {
            return true;
        }

        return false;
    }

    public function buildOffer(OfferQuery $query)
    {
        /** @var ShoppingCategoryGroup $offerCategory */
        [$cards, $grouped, $offerCategory] = $this->buildCardsListToOffer(
            $query->getMerchant(),
            $query->getSource(),
            $query->getUser(),
            $query->getOfferCards(),
            $query->getDate()
        );

        if (!isset($cards[0])) {
            return null;
        }

        $data = [
            'miles' => $query->getMiles(),
            'pointValue' => $query->getPointValue(),
            'amount' => $query->getAmount(),
            'merchantId' => $query->getMerchant()->getId(),
            'multiplier' => $query->getMultiplier(),
        ];

        // choosing bottom link href
        $addSourceLink = sprintf("?%s=%s", OfferQuery::SOURCE_PARAM_NAME, $query->getSource());
        $merchantPattern = $query->getMerchant()->getMerchantPattern();
        $blogUrl = $merchantPattern ? $merchantPattern->getClickurl() : null;
        $isMerchantBlogLink = true;

        if (empty($blogUrl)) {
            $blogUrl = $offerCategory instanceof ShoppingCategoryGroup ? $offerCategory->getClickURL() . $addSourceLink : $cards[0]->link;
            $isMerchantBlogLink = false;
        } else {
            $blogUrl .= $addSourceLink;
        }

        $category = $offerCategory instanceof ShoppingCategoryGroup ? $offerCategory->getName() : "";
        $result = array_merge([
            'cards' => $cards,
            'grouped' => $grouped,
            'potentialData' => [
                'isEveryPurchaseCategory' => empty($offerCategory) ? true : false,
                'blogUrl' => $blogUrl,
                'potential' => $cards[0]->multiplier,
            ],
            'category' => $category,
            'merchant' => $query->getDescription(),
            'bottomLinkTitle' => $isMerchantBlogLink ? str_replace('#', '',
                $query->getMerchant()->getName()) : $category,
        ], $data);

        return $result;
    }

    public function buildCardsListToOffer(
        Merchant $merchant,
        $source,
        ?Usr $user = null,
        array $filterIds = [],
        ?\DateTime $date = null,
        array $groupsList = self::CARDS_GROUP_FULL,
        ?Context $context = null,
        bool $findPopularCategory = true
    ) {
        $merchantId = $merchant->getId();
        $groupsMap = \array_flip($groupsList);

        $existedUserCardsMap = [];

        if ($user instanceof Usr) {
            $existedEntities = $this->em->getRepository(UserCreditCard::class)->findBy(['user' => $user, 'isClosed' => false]);
            $existedUserCardsMap =
                it($existedEntities)
                    ->map(fn (UserCreditCard $item) => [$item->getCreditCard()->getId(), true])
                    ->fromPairs()
                    ->toArrayWithKeys();
        }

        /** @var OfferCreditCardItem[] $cards */
        [$cards, $offerCategory] = $this->analyser->findPotentialCards(
            $merchant,
            $user,
            $filterIds,
            $date,
            $context,
            $existedUserCardsMap
        );

        $currentVersion = $context
            ? $context->getCurrentMerchantReportVersion()->getValue()
            : (int) $this->parameterRepository->getParam(ParameterRepository::MERCHANT_REPORT_VERSION);

        $expected = ($context && ($reportExpectedMultiplier = $context->getMerchantReportExpectedMultiplier()))
            ? ($reportExpectedMultiplier->getValue()[$merchant->getId()] ?? [])
            : $this->em
                ->getConnection()
                ->executeQuery("
                        SELECT CreditCardID, CONCAT(sum(Transactions), '_', sum(ExpectedMultiplierTransactions)) as Stat
                        FROM MerchantReport r
                        WHERE Version = ?
                        AND MerchantID = ?
                        GROUP BY CreditCardID",
                    [$currentVersion, $merchant->getId()],
                    [\PDO::PARAM_INT, \PDO::PARAM_INT]
                )
                ->fetchAllKeyValue();

        $grouped = [
            self::CARDS_GROUP_LIST => [],
            self::CARDS_GROUP_EXCLUDED => [],
            self::CARDS_GROUP_FROM_RECENT_TRANSACTIONS => [],
        ];

        $inOfferCarsIdMap = [];
        $cardAnalyzerCache = [];
        $merchantUpdateJson = [];

        /** @var OfferCreditCardItem $card */
        foreach ($cards as $card) {
            $card->isUserHas = \array_key_exists($card->cardId, $existedUserCardsMap);

            if ($findPopularCategory) {
                $card->shoppingCategory = $this->theMostPopularCategory($card->cardId, $merchant->getId(), $currentVersion);
            }

            $inOfferCarsIdMap[$card->cardId] = true;

            $card->link = $card->link ? $card->link . sprintf("?%s=%s", OfferQuery::SOURCE_PARAM_NAME, $source) : null;

            if (true === $card->earningAllTransactions) {
                $card->isConfirmed = true;
                $grouped[self::CARDS_GROUP_LIST][] = $card;

                continue;
            }

            [$totalTransactions, $expectedMultiplierTransactions] = $this->loadTransactionsFromCache($merchant, $card->cardId, $card->multiplier, $merchantUpdateJson);

            if ($totalTransactions === null) {
                [$totalTransactions, $expectedMultiplierTransactions] = $this->analyzeCardTransactions($card->cardId, $merchant->getId(), $card->multiplier, $expected, $cardAnalyzerCache, $merchantUpdateJson);
            }

            if ((int) $totalTransactions < self::MIN_MULTIPLIER_TRANSACTIONS
                || (null === $expectedMultiplierTransactions && (int) $totalTransactions > self::MIN_MULTIPLIER_TRANSACTIONS) // нет транзакций = нет подтверждения, что карта не зарабатывает (чтобы не попала в excluded)
            ) {
                $card->isUnconfirmed = true;
                $grouped[self::CARDS_GROUP_LIST][] = $card;
            } elseif ((int) $expectedMultiplierTransactions >= self::MIN_MULTIPLIER_TRANSACTIONS) {
                $card->isConfirmed = true;
                $grouped[self::CARDS_GROUP_LIST][] = $card;
            } else {
                $grouped[self::CARDS_GROUP_EXCLUDED][] = $card;
            }
        }

        if ($merchantUpdateJson) {
            $this->connection->executeStatement("
                update Merchant m
                set m.Stat = json_merge_patch(
                    ifnull(m.Stat, json_object()),
                    :json
                )
                where 
                    m.MerchantID = :merchantId 
                    and (m.Stat is null or json_contains_path(m.Stat, 'one', '$.p'))",
                [
                    'merchantId' => $merchant->getId(),
                    'json' => \json_encode($merchantUpdateJson),
                ],
            );
        }

        if (!$this->isDebug && isset($groupsMap[self::CARDS_GROUP_FROM_RECENT_TRANSACTIONS])) {
            $startDate = new \DateTime('first day of -3 month');
            $startDate->setTime(0, 0, 0);
            $recentTransactions = $this->merchantRecentTransactionsStatLoader->load(
                [$merchantId],
                self::MIN_MERCHANT_TRANSACTIONS,
                fn (Stat $stat) => \array_key_exists($stat->creditCardId, $inOfferCarsIdMap),
                fn (CreditCard $card) => \array_key_exists($card->getId(), $existedUserCardsMap),
                $startDate,
                $filterIds
            )[$merchant->getId()] ?? [];

            if ($findPopularCategory) {
                foreach ($recentTransactions as $recentTransaction) {
                    $recentTransaction->shoppingCategory = $this->theMostPopularCategory(
                        $recentTransaction->cardId,
                        $merchantId,
                        $currentVersion
                    );
                }
            }

            $grouped[self::CARDS_GROUP_FROM_RECENT_TRANSACTIONS] = $recentTransactions;
        }

        $this->logger->info("buildCardsListToOffer, merchant: {$merchant->getId()}, date: " . ($date ? $date->format("Y-m-d") : "null")
            . ", cards: " . count($cards)
            . ", grouped.list(confirmed/unconfirmed): " . count($grouped[self::CARDS_GROUP_LIST])
            . ", grouped.excluded: " . count($grouped[self::CARDS_GROUP_EXCLUDED])
            . ", grouped.fromRecentTransactions: " . count($grouped[self::CARDS_GROUP_FROM_RECENT_TRANSACTIONS]),
            ["fromRecentTransactions" => count($grouped[self::CARDS_GROUP_FROM_RECENT_TRANSACTIONS])]
        );

        return [
            $cards,
            $grouped,
            $offerCategory,
        ];
    }

    public function fetchTransactionOfferWithBestCard($offerData): array
    {
        /** @var AccountHistory $historyRow */
        $historyRow = $offerData['historyRow'];
        $historyRow->setMiles(round($historyRow->getMiles()));
        $cacheContext = $this->getCacheContext();

        $bestCard = $offerData['cards'][0];
        $bestCardHave = null;
        $bestAwCard = null;
        $currentCard = null;

        $transactionCard = $historyRow->getSubaccount()->getCreditcard();

        /** @var OfferCreditCardItem $card */
        foreach ($offerData['cards'] as $card) {
            if ($card->isUserHas && null === $bestCardHave) {
                $bestCardHave = $card;
                $bestCardHave->isBestHave = true;
            }

            if ($card->cardId === $transactionCard->getId()) {
                $currentCard = $card;
            }
        }

        $bestCard->isBestValue = true;

        if (!empty($bestCardHave) && $bestCardHave->cardId === $bestCard->cardId) {
            $bestCard->isBestHave = true;
            $bestCardHave = null;
        }

        $pointNames = [];

        foreach ([$bestCard, $bestCardHave, $bestAwCard, $currentCard] as $index => &$card) {
            if (null === $card) {
                continue;
            }

            $historyRowValue = $this->historyRowValueCalculator->calculate(
                $card->creditCard,
                $historyRow->getMiles() ?? 0.0,
                $historyRow->getAmount() ?? 0.0,
                $historyRow->getAccount()->getProviderid()->getId(),
                $cacheContext
            );

            $transaction = new Transaction(
                $historyRow->getUuid(),
                $historyRow->getPostingdate(),
                $historyRow->getAmount(),
                $historyRow->getMiles(),
                $historyRowValue->getPointValue(),
                $historyRow->getDescription(),
                $historyRow->getSubaccount()->getCreditCardFormattedDisplayName(),
                '',// $category ? html_entity_decode($category->getName()) : '',
                html_entity_decode($historyRow->getCategory() ?? ''),
                $historyRow->getCurrency() ? $historyRow->getCurrency()->getCode() : null,
                $historyRow->getSubaccount()->getId(),
                $historyRowValue->getMultiplier(),
                $historyRowValue->getMinValue(),
                $historyRowValue->getMaxValue(),
                $card->creditCard->getPointName()
            );
            $pointNames[] = $card->creditCard->getPointName();

            $card->potential = $this->analyser->detectPotential(
                $this->tokenStorage->getUser(),
                [
                    'amount' => $historyRow->getAmount(),
                    'miles' => $historyRow->getMiles(),
                    'multiplier' => $transaction->multiplier,
                    'milesValue' => $transaction->pointsValue,
                    'merchantId' => $historyRow->getMerchant()->getId(),
                    'merchant' => $historyRow->getMerchant(),
                ],
                $historyRow->getPostingdate()->format('Y-m-d H:i:s'),
                [$card->creditCard->getId()],
                $cacheContext
            );
            $transaction->potential = $card->potential['potential'] ?? $transaction->potential;

            $potentialMiles = NumberHandler::numberPrecision($card->potential['potentialMiles'] ?? 0, 2);
            empty($potentialMiles) ? $potentialMiles = $transaction->miles : null;

            $potentialValue = NumberHandler::numberPrecision($card->potential['potentialMiles'] ?? 0, 2);
            $potentialPointsValue = empty($potentialValue) ? $card->potential['potentialMiles'] : $potentialValue;

            $card->potentialColor = BankTransactionsAnalyser::potentialValueDiffColor(
                (float) $transaction->pointsValue,
                $potentialPointsValue
            );
            $primaryMileValue = $historyRowValue->getMileValueCost()->getPrimaryValue();

            if ($potentialMiles) {
                $transaction->potentialMiles = $card->potential['potentialMiles'] ?? null;
                $transaction->potentialPointsValue = round($card->potential['potentialValue'] ?? 0, 2);

                $transaction->isProfit = $transaction->miles > 0 && $transaction->amount > 0 && $transaction->multiplier > 0
                    && (
                        $transaction->pointsValue >= $transaction->potentialPointsValue
                        || ($transaction->multiplier === $transaction->potential && abs($transaction->potentialPointsValue - $transaction->pointsValue) <= 0.05)
                    );
            }

            if (null !== $primaryMileValue) {
                $card->cashEquivalent = $this->mileValueService->calculateCashEquivalent(
                    $primaryMileValue,
                    $potentialMiles,
                    null
                );

                $transaction->cashEquivalent = $card->cashEquivalent['raw'] ?? 0;
                $potentialCashEq = $this->mileValueService->calculateCashEquivalent($primaryMileValue, $potentialPointsValue, null);
                $transaction->diffCashEq = BankTransactionsAnalyser::potentialDiffColorByCashEq($transaction->cashEquivalent, $potentialCashEq['raw']);

                if ($transaction->isProfit && BankTransactionsAnalyser::POTENTIAL_LEVEL_LOW !== $transaction->diffCashEq) {
                    $transaction->isProfit = false;
                } elseif (BankTransactionsAnalyser::POTENTIAL_LEVEL_LOW === $transaction->diffCashEq && null !== $transaction->potential) {
                    $transaction->isProfit = true;
                }
            }

            $card->potentialPontValue = ($potentialPointsValue > 0 ? '+' : '') . $this->localizeService->formatNumber($potentialPointsValue);
        }

        if (null !== $currentCard
            && isset($currentCard->cashEquivalent, $bestCard->cashEquivalent)
            && $currentCard->cashEquivalent['raw'] >= $bestCard->cashEquivalent['raw']
        ) {
            $bestCard = $currentCard;
            $bestCard->isBestValue =
            $bestCard->isBestHave = true;

            $bestCardHave = null;
        }

        unset($bestCard->creditCard, $bestCardHave->creditCard, $bestAwCard->creditCard);

        return [
            'bestCards' => [
                'value' => $bestCard,
                'have' => $bestCardHave,
                'aw' => $bestAwCard,
            ],
            'transaction' => [
                'miles' => $transaction->miles,
                'milesValue' => ($transaction->miles > 0 ? '+' : '') . $this->localizeService->formatNumber($transaction->miles),
                'multiplier' => $transaction->multiplier,
                'pointName' => $transaction->pointName,
                'pointNames' => array_unique($pointNames),
                'cashEquivalent' => $transaction->cashEquivalent,
                'cashEquivalentFormatted' => $this->localizeService->formatCurrency($transaction->cashEquivalent, 'USD'),
                'description' => $historyRow->getDescription(),
            ],
        ];
    }

    public function getCacheContext(): Context
    {
        if (null === $this->cacheContext) {
            $this->cacheContext = $this->contextFactory->makeCacheContext();
        }

        return $this->cacheContext;
    }

    private function theMostPopularCategory(int $creditCardId, int $merchantId, int $version)
    {
        $sql = '
            SELECT c.Name, r.Transactions FROM MerchantReport r
            JOIN ShoppingCategory c ON r.ShoppingCategoryID = c.ShoppingCategoryID
            WHERE r.Version = ?
            AND r.MerchantID = ?
            AND r.CreditCardID = ?
            AND r.ShoppingCategoryID NOT IN (?)
            AND r.Transactions >= 5
            ORDER BY r.Transactions DESC
        ';

        return $this->em
                    ->getConnection()
                    ->executeQuery(
                        $sql,
                        [$version, $merchantId, $creditCardId, array_merge(ShoppingCategory::IGNORED_CATEGORIES, [0])],
                        [\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, Connection::PARAM_INT_ARRAY]
                    )
                    ->fetchColumn();
    }

    private function scanCardTransactions(int $cardId, int $merchantId, float $multiplier, array &$merchantUpdateJson): array
    {
        $startTime = microtime(true);
        $expectedCountByMultiplier = count($this->connection->executeQuery("select 
            1 
        from 
            AccountHistory ah use index(MerchantID)
            join SubAccount sa on sa.SubAccountID = ah.SubAccountID 
        where 
            ah.MerchantID = :merchantId and sa.CreditCardID = :creditCardId and ah.Multiplier = :multiplier limit " . self::MIN_MULTIPLIER_TRANSACTIONS,
            [
                "creditCardId" => $cardId,
                "merchantId" => $merchantId,
                "multiplier" => $multiplier,
            ]
        )->fetchFirstColumn());
        $this->logger->info("scanned merchant transactions", ["merchantId" => $merchantId, "cardId" => $cardId, "multiplier" => $multiplier, "time" => microtime(true) - $startTime]);

        if ($expectedCountByMultiplier >= self::MIN_MULTIPLIER_TRANSACTIONS) {
            $merchantUpdateJson[Merchant::STAT_BY_CARD_AND_MULTIPLIER][Merchant::getCardAndMultiplierStatKey($cardId, $multiplier)] = $expectedCountByMultiplier;
            $merchantUpdateJson[Merchant::STAT_BY_CARD][$cardId] = $expectedCountByMultiplier;
            $merchantUpdateJson['p'] = true;

            // optimization, to not return total transaction, because it is not relevant in this case
            return [$expectedCountByMultiplier, $expectedCountByMultiplier];
        }

        $startTime = microtime(true);
        $totalCountByCard = count($this->connection->executeQuery("select 
            1 
        from 
            AccountHistory ah use index(MerchantID)
            join SubAccount sa on sa.SubAccountID = ah.SubAccountID 
        where 
            ah.MerchantID = :merchantId and sa.CreditCardID = :creditCardId limit " . self::MIN_MULTIPLIER_TRANSACTIONS,
            [
                "creditCardId" => $cardId,
                "merchantId" => $merchantId,
            ]
        )->fetchFirstColumn());
        $this->logger->info("scanned no multiplier transactions", ["merchantId" => $merchantId, "cardId" => $cardId, "time" => microtime(true) - $startTime]);
        $merchantUpdateJson['p'] = true;
        $merchantUpdateJson[Merchant::STAT_BY_CARD_AND_MULTIPLIER][Merchant::getCardAndMultiplierStatKey($cardId, $multiplier)] = $expectedCountByMultiplier;
        $merchantUpdateJson[Merchant::STAT_BY_CARD][$cardId] = $totalCountByCard;

        return [$totalCountByCard, $expectedCountByMultiplier];
    }

    private function analyzeCardTransactions(int $cardId, int $merchantId, float $multiplier, array $expected, array &$cardAnalyzerCache, array &$merchantUpdateJson): array
    {
        [$totalTransactions, $expectedMultiplierTransactions] = explode('_', $expected[$cardId] ?? '0_0');

        if ($expectedMultiplierTransactions >= self::MIN_MULTIPLIER_TRANSACTIONS) {
            return [$totalTransactions, $expectedMultiplierTransactions];
        }

        $cacheKey = "{$cardId}_{$merchantId}_" . round($multiplier, 1);
        $cached = $cardAnalyzerCache[$cacheKey] ?? null;

        if ($cached !== null) {
            return $cached;
        }

        $result = $this->scanCardTransactions($cardId, $merchantId, $multiplier, $merchantUpdateJson);
        $cached[$cacheKey] = $result;

        return $result;
    }

    /**
     * @return int[] [transactionsWithThisCard, transactionsWithThisCardAndMultiplier]
     */
    private function loadTransactionsFromCache(Merchant $merchant, int $cardId, float $multiplier, array &$merchantUpdateJson): array
    {
        $merchantStat = $merchant->getStat();
        $merchantPatternStat = ($merchantPattern = $merchant->getMerchantPattern()) ?
            $merchantPattern->getStat() :
            null;

        if (($merchantStat ?? $merchantPatternStat) === null) {
            return [null, null];
        }

        $isPartialCache = isset($merchantStat['p']);
        $cardMultiplierKey = Merchant::getCardAndMultiplierStatKey($cardId, $multiplier);

        if ($merchantPatternStat) {
            return [
                $merchantPatternStat[Merchant::STAT_BY_CARD][$cardId] ?? 0,
                $merchantPatternStat[Merchant::STAT_BY_CARD_AND_MULTIPLIER][$cardMultiplierKey] ?? null,
            ];
        }

        return [
            $merchantUpdateJson[Merchant::STAT_BY_CARD][$cardId]
                ?? $merchantStat[Merchant::STAT_BY_CARD][$cardId]
                ?? ($isPartialCache ? null : 0),
            $merchantUpdateJson[Merchant::STAT_BY_CARD_AND_MULTIPLIER][$cardMultiplierKey]
                ?? $merchantStat[Merchant::STAT_BY_CARD_AND_MULTIPLIER][$cardMultiplierKey]
                ?? ($isPartialCache ? null : 0),
        ];
    }
}
