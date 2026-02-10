<?php

namespace AwardWallet\MainBundle\Service\AccountHistory;

use AwardWallet\MainBundle\Entity\AccountHistory;
use AwardWallet\MainBundle\Entity\CreditCard;
use AwardWallet\MainBundle\Entity\ShoppingCategory;
use AwardWallet\MainBundle\Service\MileValue\MileValueCalculator;
use AwardWallet\MainBundle\Service\MileValue\MileValueService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class TransactionAnalyzerService
{
    public const COOKIE_OFFER_CARDS_KEY = 'analyzerOfferCards2';

    private const ACCOUNT_HISTORY_BATCH_SIZE = 500;

    private BankTransactionsAnalyser $analyser;
    private EntityManagerInterface $em;
    private MileValueService $mileValueService;
    private AnalyserContextFactory $contextFactory;
    private AuthorizationCheckerInterface $authorizationChecker;
    private HistoryRowValueCalculator $historyRowValueCalculator;
    private MerchantDeepLoader $merchantDeepLoader;
    private ?array $transactionPatterns;

    public function __construct(
        BankTransactionsAnalyser $analyser,
        EntityManagerInterface $em,
        MileValueService $mileValueService,
        AnalyserContextFactory $contextFactory,
        AuthorizationCheckerInterface $authorizationChecker,
        HistoryRowValueCalculator $historyRowValueCalculator,
        MerchantDeepLoader $merchantDeepLoader
    ) {
        $this->em = $em;
        $this->analyser = $analyser;
        $this->mileValueService = $mileValueService;
        $this->contextFactory = $contextFactory;
        $this->authorizationChecker = $authorizationChecker;
        $this->historyRowValueCalculator = $historyRowValueCalculator;
        $this->merchantDeepLoader = $merchantDeepLoader;
    }

    public function getTransactions(TransactionQuery $query): array
    {
        $transactions = [];
        $iter = $this->doGetTransactionsIter($query);

        foreach ($iter as $transaction) {
            $transactions[] = $transaction;
        }

        [$nextPageToken, $isLastPageLoaded] = $iter->getReturn();

        return [$transactions, $nextPageToken, $isLastPageLoaded];
    }

    public function getTotals(TransactionQuery $query)
    {
        $iter = $this->doGetTransactionsIter($query->setLimit(null));
        [$amount, $miles, $potentialMiles, $counter, $cashEquivalent] = [0, 0, 0, 0, 0];
        $categories = [];

        /** @var Transaction $row */
        foreach ($iter as $row) {
            if ($row->isRefill) {
                continue;
            }

            $counter++;
            $amount += true === $row->isSpend ? abs($row->amount) : $row->amount;
            $miles += $row->miles;
            $potentialMiles += (float) $row->potentialMiles;
            $cashEquivalent += $row->cashEquivalent;

            if (!empty($row->category)) {
                $categories[$row->category] = null;
            }
        }

        $categories = array_keys($categories);
        sort($categories);

        return new TransactionTotals(
            $amount,
            $miles,
            $potentialMiles,
            $counter,
            null,
            $categories,
            0,
            0,
            $cashEquivalent
        );
    }

    private function doGetTransactionsIter(TransactionQuery $query): \Generator
    {
        /** @var QueryBuilder $qb */
        $qb = $this->em->createQueryBuilder();
        $qb
            ->select('h, sa, cc, curr')
            ->from(AccountHistory::class, 'h')
            ->leftJoin('h.subaccount', 'sa')
            ->leftJoin('sa.creditcard', 'cc')
            ->leftJoin('h.currency', 'curr')
        ;

        $qb->where('h.subaccount in (:ids)')
            // ->andWhere('h.miles is not null')
        ;

        if ($query->getStartDate()) {
            $qb->andWhere("h.postingdate BETWEEN '{$query->getStartDate()->format('Y-m-d 00:00')}' AND '{$query->getEndDate()->format('Y-m-d 23:59')}' ");
        }

        if ($query->getAmountCondition()) {
            $qb->andWhere('h.amount ' . $query->getAmountCondition());
        }

        if ($query->getPointsMultiplier()) {
            $qb->andWhere('h.multiplier in (:multipliers)')->setParameter('multipliers', $query->getPointsMultiplier());
        }

        $isStarted = true;
        $nextPageToken = $query->getNextPageToken();

        if ($nextPageToken !== null) {
            $qb->andWhere("h.postingdate <= '{$nextPageToken->getPostingDate()->format('Y-m-d 23:59:59')}'");
            $isStarted = false;
        }

        if ($query->getDescriptionFilter()) {
            $qb->andWhere("h.description like :desc")
                ->setParameter('desc', '%' . $query->getDescriptionFilter() . '%');
        }
        $rows = $qb->orderBy('h.postingdate', 'DESC')
//            ->setMaxResults($query->getLimit())
            ->setParameter('ids', $query->getSubAccountIds())
            ->getQuery();

        $cacheContext = $this->contextFactory->makeCacheContext();
        $counter = 0;
        $isLastPageLoaded = true;
        $isStaff = $this->authorizationChecker->isGranted('ROLE_STAFF');
        $isImpersonated = $this->authorizationChecker->isGranted('ROLE_IMPERSONATED');
        $this->transactionPatterns = $this->getTransactionPatterns();

        /** @var AccountHistory $row */
        foreach ($this->iterateHistory($rows) as $row) {
            if (!$isStarted) {
                $isStarted = $row->getUuid() === $nextPageToken->getUuid();

                continue;
            }

            $creditCard = $row->getSubaccount()->getCreditcard();
            $historyRowValue = $this->historyRowValueCalculator->calculate(
                $creditCard,
                $row->getMiles() ?? 0,
                $row->getAmount() ?? 0,
                $row->getAccount()->getProviderid()->getId(),
                $cacheContext
            );

            $merchantCategory = $row->getMerchant() ? $row->getMerchant()->chooseShoppingCategory() : null;
            $category = $merchantCategory;

            if (!$merchantCategory || !$merchantCategory->getGroup()) {
                // Do not show auto-selected category for merchants without linked Shopping Category Group,
                // as such merchants aggregates misleading\irrelevant info from parsers, LPs
                $category = $row->getShoppingcategory();
            }

            if ($category && in_array($category->getId(), ShoppingCategory::IGNORED_CATEGORIES)) {
                $category = null;
            }

            if ($category && $category->getGroup()) {
                $category = $category->getGroup();
            }

            if (!empty($query->getCategories()) && !in_array($category, $query->getCategories())) {
                continue;
            }

            $transaction = new Transaction(
                $row->getUuid(),
                $row->getPostingdate(),
                $row->getAmount(),
                $row->getMiles(),
                $historyRowValue->getPointValue(),
                $row->getDescription(),
                $row->getSubaccount()->getCreditCardFormattedDisplayName(),
                $category ? html_entity_decode($category->getName()) : '',
                html_entity_decode($row->getCategory() ?? ''),
                $row->getCurrency() ? $row->getCurrency()->getCode() : null,
                $row->getSubaccount()->getId(),
                $historyRowValue->getMultiplier(),
                $historyRowValue->getMinValue(),
                $historyRowValue->getMaxValue(),
                $creditCard->getPointName()
            );

            $this->processTransactionPatterns($row->getAccount()->getProviderid()->getId(), $transaction);

            if (!$isStaff && !$isImpersonated) {
                unset($transaction->rawCategory);
            }

            if ($query->withEarningPotential() && $row->getMerchant()) {
                $potential = $this->analyser->detectPotential(
                    $row->getAccount()->getUser(),
                    [
                        'amount' => $transaction->amount,
                        'miles' => $transaction->miles,
                        'multiplier' => $transaction->multiplier,
                        'milesValue' => $transaction->pointsValue,
                        'merchantId' => $row->getMerchant()->getId(),
                        'merchant' => $row->getMerchant(),
                    ],
                    $row->getPostingdate()->format('Y-m-d'),
                    $query->getOfferCards(),
                    $cacheContext
                );
                $transaction->potential = $potential['potential'] ?? null;

                if (!empty($query->getEarningPotentialMultiplier())) {
                    $diff = round($transaction->potential - $transaction->multiplier, 1);

                    if (!in_array($diff, $query->getEarningPotentialMultiplier())) {
                        continue;
                    }
                }

                $transaction->potentialMiles = $potential['potentialMiles'] ?? null;
                $transaction->potentialPointsValue = round($potential['potentialValue'] ?? 0, 2);
                $transaction->potentialColor = BankTransactionsAnalyser::potentialValueDiffColor((float) $transaction->pointsValue, $transaction->potentialPointsValue);

                $mileValueCost = $historyRowValue->getMileValueCost();

                if ($transaction->potentialMiles) {
                    $transaction->potentialMinValue = $mileValueCost->getMinValue()
                        ? MileValueCalculator::calculateEarning($mileValueCost->getMinValue(), $transaction->potentialMiles)
                        : 0;
                    $transaction->potentialMaxValue = $mileValueCost->getMaxValue()
                        ? MileValueCalculator::calculateEarning($mileValueCost->getMaxValue(), $transaction->potentialMiles)
                        : 0;

                    $transaction->isProfit = $transaction->miles > 0 && $transaction->amount > 0 && $transaction->multiplier > 0
                        && (
                            $transaction->pointsValue >= $transaction->potentialPointsValue
                            || ($transaction->multiplier === $transaction->potential && abs($transaction->potentialPointsValue - $transaction->pointsValue) <= 0.05)
                        );
                }

                if (null !== $mileValueCost->getPrimaryValue()) {
                    $milesBalance = abs($transaction->miles ?? 0);

                    if (CreditCard::CASHBACK_TYPE_POINT === $mileValueCost->getCashBackType()) {
                        $milesBalance *= 100;
                    }
                    $cashEquivalent = $this->mileValueService->calculateCashEquivalent($mileValueCost->getPrimaryValue(), $milesBalance, null);
                    $transaction->cashEquivalent = $cashEquivalent['raw'];
                    $transaction->formatted['cashEquivalent'] = $cashEquivalent['formatted'];

                    $potentialCashEq = $this->mileValueService->calculateCashEquivalent($mileValueCost->getPrimaryValue(), $transaction->potentialMiles, null);
                    $transaction->diffCashEq = BankTransactionsAnalyser::potentialDiffColorByCashEq($transaction->cashEquivalent, $potentialCashEq['raw']);

                    if ($transaction->isProfit && BankTransactionsAnalyser::POTENTIAL_LEVEL_LOW !== $transaction->diffCashEq) {
                        $transaction->isProfit = false;
                    } elseif (BankTransactionsAnalyser::POTENTIAL_LEVEL_LOW === $transaction->diffCashEq && null !== $transaction->potential) {
                        $transaction->isProfit = true;
                    }
                }
            }

            if (empty($transaction->category) && null !== $merchantCategory) {
                $transaction->category = $merchantCategory->getName();
            }

            yield $transaction;
            $counter++;

            if ($query->getLimit() && $counter === $query->getLimit()) {
                $isLastPageLoaded = false;

                break;
            }
        }

        $nextPageToken = null;

        if (isset($row)) {
            $nextPageToken = (string) new NextPageToken($row->getPostingdate(), $row->getUuid());
        }

        return [$nextPageToken, $isLastPageLoaded];
    }

    private function iterateHistory(Query $query): iterable
    {
        foreach (
            it($query->iterate())
            ->column(0)
            ->chunk(self::ACCOUNT_HISTORY_BATCH_SIZE) as $historyRows
        ) {
            $merchantsIds =
                it($historyRows)
                ->map(function (AccountHistory $accountHistory) { return $accountHistory->getMerchant() ? $accountHistory->getMerchant()->getId() : null; })
                ->filter(fn ($merchantId) => $merchantId !== null)
                ->collect()
                ->unique()
                ->toArray();

            $this->merchantDeepLoader->load($merchantsIds);

            yield from $historyRows;

            //            $this->em->clear(); // causes non persisted entity found exception, when creating coupon in bundles/AwardWallet/WidgetBundle/Widget/InviteWidget.php#L64-L64
        }
    }

    private function processTransactionPatterns(int $providerId, Transaction $transaction): void
    {
        if (!array_key_exists($providerId, $this->transactionPatterns)
            || $transaction->amount > 0
        ) {
            return;
        }

        foreach ($this->transactionPatterns[$providerId] as $pattern) {
            if (($pattern['isRegexp'] && preg_match('/' . $pattern['pattern'] . '/mis', $transaction->description))
                || false !== stripos($transaction->description, $pattern['pattern'])
            ) {
                if ($pattern['isPositive']) {
                    $transaction->isSpend = true;
                } else {
                    $transaction->isRefill = true;
                }
            }
        }
    }

    private function getTransactionPatterns(): array
    {
        $providers = $this->em->getConnection()->fetchAllKeyValue('
            SELECT ProviderID, TransactionPatterns
            FROM Provider
            WHERE TransactionPatterns IS NOT NULL
        ');

        $list = [];

        foreach ($providers as $providerId => $items) {
            $list[(int) $providerId] = array_map('trim', explode("\n", trim($items)));
        }

        $patterns = [];

        foreach ($list as $providerId => $items) {
            $patterns[$providerId] = [];

            foreach ($items as $pattern) {
                $isPositive = 0 === strpos($pattern, '+');

                if ($isPositive) {
                    $pattern = substr($pattern, 1);
                }

                $isRegexp = 0 === strpos($pattern, '#');

                if ($isRegexp) {
                    $pattern = substr($pattern, 1);
                }

                $patterns[$providerId][] = [
                    'pattern' => $pattern,
                    'isRegexp' => $isRegexp,
                    'isPositive' => $isPositive,
                ];
            }
        }

        return $patterns;
    }
}
