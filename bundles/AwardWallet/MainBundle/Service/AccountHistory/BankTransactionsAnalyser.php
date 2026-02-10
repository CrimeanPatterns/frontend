<?php

namespace AwardWallet\MainBundle\Service\AccountHistory;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Accountproperty;
use AwardWallet\MainBundle\Entity\CreditCard;
use AwardWallet\MainBundle\Entity\CreditCardMerchantGroup;
use AwardWallet\MainBundle\Entity\CreditCardShoppingCategoryGroup;
use AwardWallet\MainBundle\Entity\Merchant;
use AwardWallet\MainBundle\Entity\MerchantGroup;
use AwardWallet\MainBundle\Entity\MerchantPatternGroup;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\ShoppingCategory;
use AwardWallet\MainBundle\Entity\ShoppingCategoryGroup;
use AwardWallet\MainBundle\Entity\Subaccount;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\UserCreditCard;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorage;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Manager\AccountListManager;
use AwardWallet\MainBundle\Service\CreditCards\CreditCardMatcher;
use AwardWallet\MainBundle\Service\MileValue\MileValueCalculator;
use AwardWallet\MainBundle\Service\MileValue\MileValueCards;
use AwardWallet\MainBundle\Service\MileValue\MileValueService;
use AwardWallet\MainBundle\Service\UserAvatar;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class BankTransactionsAnalyser
{
    public const CHASE_FREEDOM_CARD_ID = 3;
    public const POTENTIAL_LEVEL_LOW = 'low';
    public const POTENTIAL_LEVEL_SMALL = 'small';
    public const POTENTIAL_LEVEL_MEDIUM = 'medium';
    public const POTENTIAL_LEVEL_HIGH = 'high';
    private const SPEND_ANALISYS_TAB_SHOW_KEY = 'credit_cards.spend_analysis_tab_enabled_%s';

    private LoggerInterface $logger;
    private EntityManagerInterface $em;
    private Connection $replica;
    private RouterInterface $router;
    private TranslatorInterface $translator;
    private \Memcached $memcached;
    private AwTokenStorage $tokenStorage;
    private AccountListManager $accountListManager;
    private OptionsFactory $optionsFactory;
    private MileValueService $mileValueService;
    private \Doctrine\Persistence\ObjectRepository $merchantRep;
    private \Doctrine\Persistence\ObjectRepository $creditCardRep;
    private HistoryRowValueCalculator $historyRowValueCalculator;
    private AuthorizationCheckerInterface $authorizationChecker;
    private CreditCardMatcher $cardMatcher;
    private MileValueCards $mileValueCards;
    private UserAvatar $userAvatar;

    private string $channel;
    private string $host;

    private array $initial = [];

    public function __construct(
        Logger $logger,
        EntityManagerInterface $em,
        Connection $replicaUnbufferedConnection,
        AccountListManager $accountListManager,
        OptionsFactory $optionsFactory,
        TranslatorInterface $translator,
        RouterInterface $router,
        AwTokenStorage $tokenStorage,
        \Memcached $memcached,
        MileValueService $mileValueService,
        HistoryRowValueCalculator $historyRowValueCalculator,
        AuthorizationCheckerInterface $authorizationChecker,
        CreditCardMatcher $cardMatcher,
        MileValueCards $mileValueCards,
        UserAvatar $userAvatar,
        string $channel,
        string $host
    ) {
        $this->logger = $logger;
        $this->em = $em;
        $this->replica = $replicaUnbufferedConnection;
        $this->translator = $translator;
        $this->router = $router;
        $this->memcached = $memcached;
        $this->tokenStorage = $tokenStorage;
        LocalizeService::defineDateTimeFormat();
        $this->accountListManager = $accountListManager;
        $this->optionsFactory = $optionsFactory;
        $this->mileValueService = $mileValueService;
        $this->merchantRep = $this->em->getRepository(Merchant::class);
        $this->creditCardRep = $this->em->getRepository(CreditCard::class);
        $this->historyRowValueCalculator = $historyRowValueCalculator;
        $this->authorizationChecker = $authorizationChecker;
        $this->cardMatcher = $cardMatcher;
        $this->mileValueCards = $mileValueCards;
        $this->userAvatar = $userAvatar;
        $this->channel = $channel;
        $this->host = $host;
    }

    public function isSpendAnalysisEnabled(): bool
    {
        $user = $this->tokenStorage->getUser();

        if (!$user) {
            return false;
        }

        $show = $this->memcached->get(sprintf(self::SPEND_ANALISYS_TAB_SHOW_KEY, $user->getUserid()));
        $result = $this->memcached->getResultCode();

        if ($result === \Memcached::RES_NOTFOUND) {
            $initial = $this->getSpentAnalysisInitial();
            $show = $this->memcached->get(sprintf(self::SPEND_ANALISYS_TAB_SHOW_KEY, $user->getUserid()));
        }

        return (bool) $show;
    }

    public static function getOfferCardsComparator(array $transfers = []): callable
    {
        /** @var callable $instance */
        static $instance;

        if (!$instance) {
            $instance = static function (OfferCreditCardItem $a, OfferCreditCardItem $b) use ($transfers) {
                $maxValA = (float) ($a->value ?? 0);
                $maxValB = (float) ($b->value ?? 0);

                $a->isTransferable = array_key_exists($a->providerId, $transfers); // && PROVIDER_KIND_CREDITCARD === $a->providerKind;

                if ($a->isTransferable
                    && array_key_exists($b->providerId, $transfers[$a->providerId])
                ) {
                    $ratio = round(
                        $transfers[$a->providerId][$b->providerId]['TargetRate'] / $transfers[$a->providerId][$b->providerId]['SourceRate'],
                        2
                    );

                    if (1.0 === $ratio) {
                        $maxValA = max($maxValA, $maxValB) + 0.01;
                    } else {
                        $maxValA *= $ratio;
                    }
                }

                if ($maxValA === $maxValB) {
                    if ($a->value === $b->value) {
                        if ($b->isUserHas === $a->isUserHas) {
                            return $a->creditCard->isBusiness() <=> $b->creditCard->isBusiness();
                        }

                        return $b->isUserHas <=> $a->isUserHas;
                    }

                    return $b->value <=> $a->value;
                }

                return $maxValB <=> $maxValA;
            };
        }

        return $instance;
    }

    public function getSpentAnalysisInitial(?Usr $user = null, ?bool $scanFullHistory = false, ?Context $context = null)
    {
        if (empty($user)) {
            $user = $this->tokenStorage->getUser();
        }
        $userId = $user->getId();

        /* кэширование поскольку меняется состояние $this->accountList */
        if (empty($this->initial[$userId])) {
            $listOptions = $this->accountListOptions()
                ->set(Options::OPTION_USER, $user ?: $this->tokenStorage->getBusinessUser());
            $initial = $this->buildInitial($listOptions, $scanFullHistory, $context);
            $this->initial[$user->getId()] = $initial;

            $show = false;

            if (isset($this->initial[$userId]["ownersList"]) && !empty($this->initial[$userId]["ownersList"])) {
                foreach ($this->initial[$userId]['ownersList'] as $ownerItem) {
                    if (count($ownerItem['availableCards']) > 0) {
                        $show = true;

                        break;
                    }
                }
            }

            $this->memcached->set(sprintf(self::SPEND_ANALISYS_TAB_SHOW_KEY, $userId), $show, 3600);
        }

        return $this->initial[$userId];
    }

    public function getSpentAnalysisInitialByUserAgent(Useragent $userAgent): array
    {
        $listOptions = $this->accountListOptions()
            ->set(Options::OPTION_USER, $userAgent->getAgentid())
            ->set(Options::OPTION_USERAGENT, $userAgent->getId());

        return $this->buildInitial($listOptions);
    }

    public function getMerchants(array $merchantsId): array
    {
        if (empty($merchantsId)) {
            return [];
        }

        return it($this->em->getRepository(Merchant::class)->findBy([
            'id' => array_unique(array_map('intval', $merchantsId)),
        ]))
            ->reindex(static fn ($row) => $row->getId())
            ->toArrayWithKeys();
    }

    public function merchantAnalytics(
        Usr $user,
        array $subAccIds,
        $startDate,
        $endDate,
        array $filterIds,
        ?Context $context = null
    ): array {
        $rows = $this->queryData($subAccIds, $startDate, $endDate);

        $analytics = [];
        $merchants = $this->getMerchants(array_column($rows, 'merchantId'));

        foreach ($rows as &$row) {
            /** @var CreditCard $creditCard */
            $creditCard = $context->getCreditCardsMap()[$row['cardId']];
            $historyRowValue = $this->historyRowValueCalculator->calculate(
                $creditCard,
                $row['miles'],
                $row['amount'],
                $row['providerId'],
                $context
            );
            $row['multiplier'] = $historyRowValue->getMultiplier();
            $milesValue = $historyRowValue->getPointValue();
            $row['milesValue'] = $milesValue;
            $row['miles'] = $historyRowValue->getMiles();
            $row['minValue'] = $historyRowValue->getMinValue();
            $row['maxValue'] = $historyRowValue->getMaxValue();
            $mileValueCost = $historyRowValue->getMileValueCost();
            $row['merchant'] = $merchants[$row['merchantId']] ?? null;
            $row['pointName'] = $creditCard->getPointName();

            $potential = $context
                ? $this->detectPotential($user, $row, $startDate, $filterIds, $context)
                : ['potentialMiles' => 0, 'potentialMinValue' => 0, 'potentialMaxValue' => 0];
            $row = array_merge($row, $potential);

            if (!isset($analytics[$row['merchantId']])) {
                $analytics[$row['merchantId']] = array_merge($row, [
                    'miles' => 0,
                    'amount' => 0,
                    'transactions' => 0,
                    'milesValue' => 0,
                    'minValue' => 0,
                    'maxValue' => 0,
                    'potentialMiles' => 0,
                    'potentialMilesValue' => 0,
                    'potentialMinValue' => 0,
                    'potentialMaxValue' => 0,
                    'pointNames' => [],
                ]);
            }
            $current = $analytics[$row['merchantId']];

            if (isset($row['potentialCardId'])) {
                // $mileValueItem = $this->mileValueService->getMileValueViaCreditCardId((int) $row['potentialCardId'], $context);
                $mileValueItem = $this->mileValueCards->getCardMileValueCost((int) $row['potentialCardId'], $context);
                $potentialMilesValue = MileValueCalculator::calculateEarning($mileValueItem->getPrimaryValue(), $row['potentialMiles']);

                $potentialMinValue = $mileValueItem->getMinValue() ? MileValueCalculator::calculateEarning($mileValueItem->getMinValue(), $row['potentialMiles']) : 0;
                $potentialMaxValue = $mileValueItem->getMaxValue() ? MileValueCalculator::calculateEarning($mileValueItem->getMaxValue(), $row['potentialMiles']) : 0;
            } else {
                $potentialMilesValue = $milesValue;
                $potentialMinValue = $row['minValue'] ? MileValueCalculator::calculateEarning($mileValueCost->getMinValue(), $potentialMilesValue) : 0;
                $potentialMaxValue = $row['maxValue'] ? MileValueCalculator::calculateEarning($mileValueCost->getMaxValue(), $potentialMilesValue) : 0;
            }

            $current = array_merge($current, [
                'potentialMiles' => $current['potentialMiles'] + $row['potentialMiles'],
                'potentialMilesValue' => $current['potentialMilesValue'] + $potentialMilesValue,
                'potentialMinValue' => $current['potentialMinValue'] + $potentialMinValue,
                'potentialMaxValue' => $current['potentialMaxValue'] + $potentialMaxValue,
                'miles' => $current['miles'] + $row['miles'],
                'milesValue' => $current['milesValue'] + $milesValue,
                'minValue' => $current['minValue'] + $row['minValue'],
                'maxValue' => $current['maxValue'] + $row['maxValue'],
                'amount' => $current['amount'] + $row['amount'],
                'transactions' => $current['transactions'] + 1,
                'pointNames' => array_merge($current['pointNames'], [$row['pointName']]),
            ]);

            $analytics[$row['merchantId']] = $current;
        }

        foreach ($analytics as &$row) {
            $row['earningPotentialColor'] = ($row['maxValue'] > 0 && $row['potentialMaxValue'] > 0)
                ? self::potentialValueDiffColor($row['maxValue'], $row['potentialMaxValue'])
                : self::potentialValueDiffColor($row['milesValue'], $row['potentialMilesValue']);
            $row['multiplier'] = $this->calcMultiplierForRow($row['cardId'], $row['amount'], $row['miles'], $row['milesValue'], $context);
            /*
            $row['potential'] = $row['potentialMiles'] > $row['miles'] ? round($row['potentialMiles'] / $row['amount'],
                1) : null;
            */
            $row['potential'] = ($row['potentialMiles'] > 0 && $row['amount'] > 0)
                ? round($row['potentialMiles'] / $row['amount'], 1)
                : null;

            $row['merchantName'] = html_entity_decode($row['merchantName']);
            $row['category'] = html_entity_decode(empty($row['category'])
                ? $row['merchantCategory']
                : $row['category']
            );
            $row['pointNames'] = array_values(array_unique($row['pointNames']));

            $row['miles'] = round($row['miles']);
            $row['milesValue'] = round($row['milesValue'], 2);
            $row['potentialMiles'] = round($row['potentialMiles'], 2);

            $row['diffCashEq'] = self::potentialDiffColorByCashEq($row['milesValue'], $row['potentialMilesValue']);
            $row['isProfit'] = $row['milesValue'] >= $row['potentialMilesValue']
                || ($row['potentialMaxValue'] > 1 && abs($row['potentialMaxValue'] - $row['maxValue']) <= 1);

            unset($row['merchantCategory']);
        }

        usort($analytics, static function ($a, $b) {
            if ($a['amount'] === $b['amount']) {
                return 0;
            }

            return ($a['amount'] < $b['amount']) ? 1 : -1;
        });

        return $analytics;
    }

    public function merchantTransactions(
        Usr $user,
        array $subAccIds,
        $startDate,
        $endDate,
        $merchantId,
        array $filterIds,
        Context $context
    ) {
        $rows = $this->queryData($subAccIds, $startDate, $endDate, $merchantId);

        foreach ($rows as &$row) {
            /** @var CreditCard $creditCard */
            $creditCard = $context->getCreditCardsMap()[$row['cardId']];
            $historyRowValue = $this->historyRowValueCalculator->calculate(
                $creditCard,
                $row['miles'],
                $row['amount'],
                $row['providerId'],
                $context
            );
            $row['multiplier'] = $historyRowValue->getMultiplier();
            $row['milesValue'] = $historyRowValue->getPointValue();
            $row['miles'] = $historyRowValue->getMiles();
            $row['minValue'] = $historyRowValue->getMinValue();
            $row['maxValue'] = $historyRowValue->getMaxValue();
            $mileValueCost = $historyRowValue->getMileValueCost();
            $row['pointName'] = $creditCard->getPointName();

            $potential = $this->detectPotential($user, $row, $startDate, $filterIds, $context);
            $row = array_merge($row, $potential);

            $pattern = Provider::AMEX_ID === $creditCard->getProvider()->getId()
                ? "/\d{5}|\d{4}/"
                : "/\d{4}/";
            preg_match($pattern, $row['displayName'], $cardEnding);
            $row['creditCardName'] = sprintf("%s (...%s)", $row['cardName'], $cardEnding[0] ?? "");
            unset($row['displayName'], $row['cardName']);

            if (isset($potential['potentialCardId'])) {
                // $potentialMilesValueItem = $this->mileValueService->getMileValueViaCreditCardId((int) $potential['potentialCardId'], $context);
                $potentialMilesValueItem = $this->mileValueCards->getCardMileValueCost((int) $potential['potentialCardId'], $context);
                $potentialMilesValue = $potentialMilesValueItem->getPrimaryValue() * $potential['potentialMiles'] / 100;

                $potentialMinValue = $mileValueCost->getMinValue() ? MileValueCalculator::calculateEarning($mileValueCost->getMinValue(), $row['potentialMiles']) : 0;
                $potentialMaxValue = $mileValueCost->getMaxValue() ? MileValueCalculator::calculateEarning($mileValueCost->getMaxValue(), $row['potentialMiles']) : 0;
            } else {
                $potentialMilesValue = $row['milesValue'];
                $potentialMinValue = $row['minValue'] ? MileValueCalculator::calculateEarning($mileValueCost->getMinValue(), $potentialMilesValue) : 0;
                $potentialMaxValue = $row['maxValue'] ? MileValueCalculator::calculateEarning($mileValueCost->getMaxValue(), $potentialMilesValue) : 0;
            }

            $row = array_merge($row, [
                'potentialMilesValue' => $potentialMilesValue,
                'potentialMinMileValue' => $mileValueCost->getMinValue(),
                'potentialMaxMileValue' => $mileValueCost->getMaxValue(),
                'milesValue' => $row['milesValue'],
                'potentialMinValue' => $potentialMinValue,
                'potentialMaxValue' => $potentialMaxValue,
            ]);

            $row['diffCashEq'] = self::potentialDiffColorByCashEq($row['milesValue'] ?? 0, $row['potentialMilesValue']);
            $row['isProfit'] = $row['milesValue'] >= $row['potentialMilesValue']
                || ($row['potentialMaxValue'] > 1 && abs($row['potentialMaxValue'] - $row['maxValue']) <= 1);
        }

        return $rows;
    }

    public function transactionsActualDates(array $subAccIds, $startDate, $endDate)
    {
        $sql = "
            SELECT DATE_FORMAT(MAX(h.PostingDate), '%Y-%m-%d') as end, 
                   DATE_FORMAT(MIN(h.PostingDate), '%Y-%m-%d') as start  
            FROM AccountHistory h 
            WHERE h.SubAccountID IN (?)
            AND h.PostingDate >= ? AND h.PostingDate < ?
            AND h.Miles > 0
            AND h.MerchantID IS NOT NULL
        ";

        return $this->replica->executeQuery(
            $sql,
            [$subAccIds, $startDate, $endDate],
            [Connection::PARAM_INT_ARRAY, \PDO::PARAM_STR, \PDO::PARAM_STR]
        )->fetch();
    }

    public function checkTransactionsExistsNew(array $subAccIds)
    {
        $limits = BankTransactionsDateUtils::findRangeLimits(BankTransactionsDateUtils::LAST_YEAR);

        $sql = "SELECT SubAccountID, MAX(PostingDate) FROM AccountHistory
                WHERE PostingDate >= '{$limits['start']}'
                AND SubAccountID IN (?)
                GROUP BY SubAccountID";

        $rows = $this->replica->executeQuery($sql, [$subAccIds], [Connection::PARAM_INT_ARRAY])->fetchAll(\PDO::FETCH_KEY_PAIR);

        return $rows;
    }

    public function checkTransactionsExists($subAccId, $startDate = null)
    {
        $sql = "
            SELECT UUID FROM AccountHistory
            WHERE SubAccountID = ? 
            ::POSTING_DATE::
            AND MerchantID IS NOT NULL
            LIMIT 1
        ";

        $replace = empty($startDate) ? "" : "AND PostingDate >= '{$startDate}'";
        $sql = str_replace('::POSTING_DATE::', $replace, $sql);

        $row = $this->replica->executeQuery($sql, [$subAccId], [\PDO::PARAM_INT])->fetch();

        if (!$row) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param int[] $subAccIds
     * @throws \Doctrine\DBAL\DBALException
     */
    public function shoppingCategoryGroupAnalytics(Usr $user, array $subAccIds, $startDate, $endDate, ?Context $context = null): array
    {
        $sql = "SELECT sc.ShoppingCategoryGroupID, SUM(h.Amount) AS amount, ROUND(SUM(h.Miles)) AS miles
                FROM AccountHistory h
                JOIN Merchant m ON h.MerchantID = m.MerchantID
                JOIN ShoppingCategory sc ON m.ShoppingCategoryID = sc.ShoppingCategoryID
                WHERE h.SubAccountID IN (?)
                AND h.PostingDate >= ? AND h.PostingDate < ?
                AND h.Miles > 0
                GROUP BY sc.ShoppingCategoryGroupID
                ORDER BY amount DESC
        ";

        $rows = $this->replica->executeQuery(
            $sql,
            [$subAccIds, $startDate, $endDate],
            [Connection::PARAM_INT_ARRAY, \PDO::PARAM_STR, \PDO::PARAM_STR]
        )->fetchAll();

        $categoryGroupRepo = $this->em->getRepository(ShoppingCategoryGroup::class);
        $allTransactionMultipliers = $context ?
            $context->getAllTransactionMultipliers()->getValue() :
            $this->em
            ->createQueryBuilder()
            ->select('ccscg, cc')
            ->from(CreditCardShoppingCategoryGroup::class, 'ccscg')
            ->join('ccscg.creditCard', 'cc')
            ->where('ccscg.shoppingCategoryGroup is null')
            ->getQuery()
            ->execute();

        $result = [];

        foreach ($rows as ['ShoppingCategoryGroupID' => $groupId, 'amount' => $amount, 'miles' => $miles]) {
            if ($groupId) {
                $group = $categoryGroupRepo->find($groupId);
                $multipliers = $group->getMultipliers()->toArray();
            } else {
                continue;
                //                $multipliers = $this->em->getRepository(CreditCardShoppingCategoryGroup::class)->findBy(['shoppingCategoryGroup' => null]);
            }

            $multipliers = array_merge($multipliers, $allTransactionMultipliers);
            usort($multipliers, function (CreditCardShoppingCategoryGroup $a, CreditCardShoppingCategoryGroup $b) {
                if ($a->getMultiplier() === $b->getMultiplier()) {
                    return 0;
                }

                return ($a->getMultiplier() > $b->getMultiplier()) ? -1 : 1;
            });

            $realMultiplier = round((float) $miles / (float) $amount, 1);
            $potential = $realMultiplier;

            /** @var CreditCardShoppingCategoryGroup $multiplier */
            foreach ($multipliers as $multiplier) {
                if ($multiplier->getStartDate() !== null) {
                    if ($multiplier->getStartDate() === $startDate) {
                        $potential = $multiplier->getMultiplier();

                        break;
                    }

                    continue;
                }

                $potential = $multiplier->getMultiplier();

                break;
            }

            $potentialMiles = round($potential * $amount);
            $diff = $potentialMiles - $miles;

            if (abs($miles) < 0.01 || $diff <= 0 || ($diff / $miles) < 0.05) {
                continue;
            }

            $result[] = [
                'amount' => $amount,
                'miles' => (float) $miles,
                'multiplier' => $realMultiplier,
                'category' => $group->getName(),
                'potentialMiles' => $potentialMiles,
                'potential' => $potential,
                'blogUrl' => $group->getClickURL(),
                'earningPotentialColor' => self::potentialDiffColor($potential, $realMultiplier),
            ];
        }

        return $result;
    }

    public function detectPotential(Usr $user, $data, $startDate, ?array $filterIds = null, ?Context $context = null)
    {
        $miles = (float) $data['miles'];

        $result = [
            'potentialMiles' => 0,
            'category' => null,
            'blogUrl' => null,
        ];

        if (($miles < 0 && ((float) ($data['milesValue'] ?? 0)) < 0) || (float) $data['amount'] <= 0 || empty($data['merchantId'])) {
            return $result;
        }

        $merchant = $data['merchant'] ?? $this->merchantRep->find($data['merchantId']);
        unset($data['merchant']);

        if (!$merchant instanceof Merchant) {
            return $result;
        }

        /** @var ShoppingCategoryGroup $offerCategory */
        [$cards, $offerCategory] = $this->findPotentialCards(
            $merchant,
            $user,
            $filterIds,
            new \DateTime($data['postingDate'] ?? $startDate),
            $context
        );

        if ($offerCategory instanceof ShoppingCategoryGroup) {
            $result['category'] = $offerCategory->getName();
        }

        if (\count($cards) < 1) {
            return $result;
        }

        $filteredCards = $this->filterExcludedCards($cards, $merchant);

        if (!empty($filteredCards)) {
            $cards = $filteredCards;
        }

        /** @var OfferCreditCardItem $potentialCard */
        $potentialCard = $cards[0];

        if ($potentialCard->earningAllTransactions && (int) $potentialCard->multiplier === 5) {
            if (!isset($cards[1])) {
                return $result;
            }
            $potentialCard = $cards[1];
        }

        $potentialMiles = \round($potentialCard->creditCard->getBaseAmountForPoints($data['amount']) * $potentialCard->multiplier);
        $result['potentialMiles'] = $potentialMiles;
        $result['potentialCardId'] = $potentialCard->cardId;
        $result['potentialValue'] = $potentialValue = MileValueCalculator::calculateEarning($potentialCard->mileValue, $potentialMiles);

        /* else blocks only for subaccount history page */
        if (isset($data['milesValue'])) {
            if ($potentialValue <= $data['milesValue']) {
                return $result;
            }
        } elseif ((float) $data['multiplier'] >= $potentialCard->multiplier) {
            return $result;
        } elseif (\abs($potentialCard->multiplier - (float) $data['multiplier']) < 0.2) {
            return $result;
        }

        $result = \array_merge($result, [
            'potentialMiles' => $potentialMiles,
            'potential' => $potentialCard->multiplier,
            'potentialValue' => $potentialValue,
            'potentialCardId' => $potentialCard->cardId,
            'blogUrl' => $potentialCard->link,
            'isEveryPurchaseCategory' => $potentialCard->earningAllTransactions,
            'earningPotentialColor' => self::potentialValueDiffColor((float) $data['milesValue'], $potentialValue),
        ]);

        return $result;
    }

    public static function potentialDiffColor(float $potential, float $multiplier)
    {
        $diff = $potential - $multiplier;
        $epColor = "";

        if ($diff > 0) {
            $epColor = 'yellow';
        }

        if ($diff > 1.1) {
            $epColor = 'orange';
        }

        if ($diff > 2.1) {
            $epColor = 'orangedark';
        }

        if ($diff > 3.1) {
            $epColor = 'red';
        }

        return $epColor;
    }

    public static function potentialValueDiffColor(float $realValue, float $potentialValue): ?string
    {
        if (abs($potentialValue) < 0.005) {
            return null;
        }

        $profit = abs($potentialValue) - abs($realValue);

        if ($profit > 50) {
            return "red";
        }

        if ($profit > 25) {
            return "orangedark";
        }

        if ($profit > 5) {
            return "orange";
        }

        return "yellow";
    }

    public static function potentialDiffColorByCashEq(float $realValue, float $potentialValue): string
    {
        $diff = $potentialValue - $realValue;

        if ($diff < 0.50) {
            return self::POTENTIAL_LEVEL_LOW;
        }

        if ($diff < 10) {
            return self::POTENTIAL_LEVEL_SMALL;
        }

        if ($diff < 50) {
            return self::POTENTIAL_LEVEL_MEDIUM;
        }

        return self::POTENTIAL_LEVEL_HIGH;
    }

    public function findPotentialCards(
        Merchant $merchant,
        ?Usr $user = null,
        ?array $filterIds = null,
        ?\DateTime $date = null,
        ?Context $context = null,
        ?array $existedUserCardsMap = null
    ): array {
        $filterIdsMap = [];

        if (empty($filterIds)) {
            if ($context) {
                $filterIdsMap = $context->getCreditCardsMap()->getValue();
            } else {
                /** @var CreditCard $card */
                foreach ($this->creditCardRep->findAll() as $card) {
                    $filterIdsMap[$card->getId()] = $card;
                }
            }
        } else {
            $filterIdsMap = \array_flip($filterIds);
        }

        $existedCardsMap = [];

        if ($user instanceof Usr) {
            $initial = $this->getSpentAnalysisInitial($user, false, $context);
            $existedCardsMap = \array_flip($initial['userExistedCardsId']);
        }

        // cards mapped by ShoppingCategory group
        $category = $merchant->chooseShoppingCategory();
        $multipliers = [];

        /** @var $group ShoppingCategoryGroup */
        if ($category instanceof ShoppingCategory
            && ($categoryGroup = $category->getGroup())
            && $categoryGroup instanceof ShoppingCategoryGroup
        ) {
            $multipliers = $categoryGroup->getMultipliers()->toArray();
            $offerCategory = $categoryGroup;
        }

        // cards mapped by Merchant group
        $merchantGroupMultipliers = [];

        if (($merchantPattern = $merchant->getMerchantPattern())
            && ($merchantPatternGroups = $merchantPattern->getGroups())
            && $merchantPatternGroups->count()
        ) {
            /** @var MerchantPatternGroup $connection */
            foreach ($merchantPatternGroups as $connection) {
                $merchantGroupMultipliers += $connection->getMerchantGroup()->getMultipliers()->toArray();
            }
        }

        $multipliers = \array_merge($multipliers, $merchantGroupMultipliers);

        $allTransactionMultipliers = $context
            ? $context->getAllTransactionMultipliers()->getValue()
            : $this->em
                ->createQueryBuilder()
                ->select('ccscg, cc')
                ->from(CreditCardShoppingCategoryGroup::class, 'ccscg')
                ->join('ccscg.creditCard', 'cc')
                ->where('ccscg.shoppingCategoryGroup is null')
                ->getQuery()
                ->execute();

        $multipliers = \array_merge($multipliers, $allTransactionMultipliers);
        $cards = [];

        // composing all known multipliers
        /** @var CreditCardShoppingCategoryGroup|CreditCardMerchantGroup $item */
        foreach ($multipliers as $item) {
            $itemCreditCard = $item->getCreditCard();
            $itemCreditCardId = $itemCreditCard->getId();

            if (!isset($filterIdsMap[$itemCreditCardId])) {
                continue;
            }

            if ($itemCreditCard->isDiscontinued() && !isset($existedCardsMap[$itemCreditCardId])) {
                continue;
            }

            $link = $itemCreditCard->getClickURL();
            $earningAllTransactions = false;

            if ($item instanceof CreditCardShoppingCategoryGroup) {
                $itemShoppingCategoryGroup = $item->getShoppingCategoryGroup();
                $earningAllTransactions = !$itemShoppingCategoryGroup instanceof ShoppingCategoryGroup;

                if (empty($link) && $itemShoppingCategoryGroup instanceof ShoppingCategoryGroup) {
                    $link = $itemShoppingCategoryGroup->getClickURL();
                }
            }

            if ($item instanceof CreditCardMerchantGroup) {
                $itemMerchantShop = $item->getMerchantGroup();

                if (empty($link) && $itemMerchantShop instanceof MerchantGroup) {
                    $link = $itemMerchantShop->getClickURL();
                }
            }

            $startDate = $item->getStartDate();
            $endDate = $item->getEndDate();
            $date = $date ?: new \DateTime();

            if ($startDate) {
                $endDate = $endDate ?: (clone $date)->add(new \DateInterval('P3M'));

                if ($startDate > $date || $endDate < $date) {
                    continue;
                }
            } elseif ($endDate) {
                if ($endDate < $date) {
                    continue;
                }
            }

            $mileValueItem = $this->mileValueCards->getCardMileValueCost($itemCreditCard, $context);
            $mileValue = $mileValueItem->getPrimaryValue();

            if (null === $mileValueItem->getPrimaryValue()) {
                continue;
            }

            if ($itemCreditCard->isCashBackOnly()
                && CreditCard::CASHBACK_TYPE_POINT === $itemCreditCard->getCashBackType()
                && null === $itemCreditCard->getCobrandProvider()) {
                $mileValue *= 100;
            }

            if (!($itemCreditCard->__cachedPicturePathIsSet ?? false)) {
                $itemCreditCard->__cachedPicturePath = $itemCreditCard->getPicturePath('medium');
                $itemCreditCard->__cachedPicturePathIsSet = true;
            }

            if (array_key_exists($itemCreditCardId, $cards)
                && $cards[$itemCreditCardId]->multiplier > $item->getMultiplier()) {
                continue;
            }

            $cards[$itemCreditCardId] = new OfferCreditCardItem(
                $itemCreditCardId,
                $itemCreditCard->getName(),
                (float) $item->getMultiplier(),
                (float) $mileValue,
                $earningAllTransactions,
                $item->getDescription(),
                $link ?: null,
                $itemCreditCard->__cachedPicturePath,
                null !== $existedUserCardsMap ? array_key_exists($itemCreditCardId, $existedUserCardsMap) : null,
                $itemCreditCard,
                $mileValueItem->getMinValue(),
                $mileValueItem->getMaxValue()
            );
        }

        $cards = $this->sortOfferCreditCards($cards);

        return [$cards, $offerCategory ?? null];
    }

    public function sortOfferCreditCards(array $cards): array
    {
        $cards = array_values($cards);
        $transfers = $this->mileValueService->getTransferableProviders();

        usort($cards, static function (OfferCreditCardItem $a, OfferCreditCardItem $b) {
            $valA = $a->value;
            $valB = $b->value;

            if ($valA === $valB) {
                if ($a->isUserHas === $b->isUserHas) {
                    return $a->creditCard->isBusiness() <=> $b->creditCard->isBusiness();
                }

                return $b->isUserHas <=> $a->isUserHas;
            }

            return $valB <=> $valA;
        });

        // usort($cards, self::getOfferCardsComparator($transfers));

        $isBreak = false;

        while (!$isBreak) {
            foreach ($cards as $pos => $card) {
                if (null === $card || $card->isSortCheck) {
                    continue;
                }

                $card->isSortCheck = true;
                $foundBestTransferPos = $this->compareCards($card, $cards, $transfers);

                if (!$foundBestTransferPos) {
                    continue;
                }

                array_splice($cards, $foundBestTransferPos + 1, 0, [$card]);
                $cards[$pos] = null;

                break;
            }

            if (!array_search(false, array_column($cards, 'isSortCheck'))) {
                $isBreak = true;
            }
        }

        return array_values(array_filter($cards));
    }

    /**
     * Finding Chase Freedom cashback limits.
     *
     * @return array
     */
    public function freedomCardConditions(Account $account)
    {
        $result = [];

        if ($account->getProviderid()->getProviderid() !== Provider::CHASE_ID) {
            return $result;
        }

        $props = $account->getProperties()->toArray();
        /** @var Accountproperty $prop */
        $limitIsReached = null;
        $quarter = null;

        foreach ($props as $prop) {
            if ($prop->getProviderpropertyid()->getCode() === 'CurrentQuarter') {
                $quarter = $prop->getVal();
            }

            if ($prop->getProviderpropertyid()->getCode() === 'MaxReached') {
                $limitIsReached = $prop->getVal();
            }
        }

        if (empty($quarter)) {
            return $result;
        }

        $startDate = BankTransactionsDateUtils::getCurrentQuarterLimits((new \DateTime())->setTimestamp(intval($quarter)));
        $result[$startDate['start']] = intval($limitIsReached);

        return $result;
    }

    private function calcMultiplierForRow(
        int $creditCardId,
        float $amount,
        float $miles,
        float $milesValue,
        Context $context
    ): float {
        /** @var CreditCard $creditCard */
        $creditCard = $context->getCreditCardsMap()[$creditCardId];

        if ($creditCard->isCashBackOnly() && CreditCard::CASHBACK_TYPE_USD === $creditCard->getCashBackType()) {
            $miles *= 100;
        }

        return $this->calcMultiplier(
            $creditCard->getBaseAmountForPoints($amount),
            round($miles)
        );
    }

    private function calcMultiplier(float $baseAmount, int $miles): float
    {
        $result = round($miles / $baseAmount, 1);

        // pull up to nearest rounded ratio, if matches
        $roundedRatio = round(round($result * 2) / 2, 1);

        if (abs($roundedRatio - $result) > 0.09) {
            $roundedMiles = (int) round($baseAmount * $roundedRatio);

            if ($roundedMiles === $miles) {
                return $roundedRatio;
            }
        }

        return $result;
    }

    /**
     * @param OfferCreditCardItem[] $cards
     * @return OfferCreditCardItem[]
     */
    private function filterExcludedCards(array $cards, Merchant $merchant): array
    {
        $stat = $merchant->getStat();

        $list = [];

        foreach ($cards as $card) {
            $cardId = $card->cardId;
            $totalTransactions = (int) ($stat['byCard'][$cardId] ?? 0);
            $xKey = Merchant::getCardAndMultiplierStatKey($cardId, round($card->multiplier, 1));
            $expectedMultiplierTransactions = (int) ($stat['byCardAndMultiplier'][$xKey] ?? 0);

            // see SpentAnalysisService -> buildCardsListToOffer
            if (true === $card->earningAllTransactions // confirmed
                || $totalTransactions < SpentAnalysisService::MIN_MULTIPLIER_TRANSACTIONS // unconfirmed
                || $expectedMultiplierTransactions >= SpentAnalysisService::MIN_MULTIPLIER_TRANSACTIONS // confirmed
            ) {
                $list[] = $card;
            }
        }

        return $list;
    }

    private function accountListOptions(): Options
    {
        return $this->optionsFactory->createDefaultOptions()
            ->set(Options::OPTION_FILTER,
                " AND p.IsEarningPotential = 1 and a.Disabled = 0")
            ->set(Options::OPTION_COUPON_FILTER, " AND 0 = 1")
            ->set(Options::OPTION_LOAD_PHONES, Options::VALUE_PHONES_NOLOAD)
            ->set(Options::OPTION_LOAD_SUBACCOUNTS, false);
    }

    private function buildInitial(Options $listOptions, ?bool $scanFullHistory = false, ?Context $context = null): array
    {
        $accounts = $this->accountListManager->getAccountList($listOptions);

        $accountRepository = $this->em->getRepository(Account::class);
        $subAccRepo = $this->em->getRepository(Subaccount::class);
        $userAgentRepo = $this->em->getRepository(Useragent::class);

        $result = [
            'ownersList' => [],
        ];

        if ($scanFullHistory) {
            $limits = ["start" => null, "end" => null];
        } else {
            $limits = BankTransactionsDateUtils::findRangeLimits(BankTransactionsDateUtils::LAST_QUARTER);
        }

        $existAnyTransactions = [];

        foreach ($accounts as $account) {
            /** @var Useragent $userAgent */
            $userAgent = null;
            $userAgentId = isset($account['UserAgentID']) ? (int) $account['UserAgentID'] : 0;
            $userId = isset($account['UserID']) ? (int) $account['UserID'] : 0;

            /** @var Account $accountEntity */
            $accountEntity = $accountRepository->find($account['ID']);

            if ($userId === 0
                || null === $this->tokenStorage->getToken()
                || true !== $this->authorizationChecker->isGranted('READ_TRANSACTIONS', $accountEntity)) {
                continue;
            }

            // we want context in card matcher cc_multiple_matches messages
            $this->logger->pushProcessor(function ($record) use ($account, $userId) {
                $record['context']['AccountID'] = $account['ID'];
                $record['context']['UserID'] = $userId;

                return $record;
            });

            try {
                $index = $account['UserID'] . '_' . $userAgentId;

                if (!isset($result['ownersList'][$index])) {
                    if ($userId !== 0 && $userId !== $listOptions->get(Options::OPTION_USER)) {
                        $userAgent = $userAgentRepo->findOneBy(['agentid' => $listOptions->get(Options::OPTION_USER),
                            'clientid' => $userId]);
                    }

                    $result['ownersList'][$index] = [
                        'name' => $account['UserName'],
                        'userId' => $account['UserID'],
                        'avatar' => $this->userAvatar->getUserUrl($accountEntity->getUser()),
                        'userAgentId' => $userAgent ? $userAgent->getId() : $account['UserAgentID'],
                        'availableCards' => [],
                        'haveCardsId' => [],
                        'accountsId' => [],
                    ];
                }

                $existAnyTransactions[$index] = $existAnyTransactions[$index] ?? false;
                $subAccounts = $subAccRepo->findBy(['accountid' => $account['ID']]);

                $result['ownersList'][$index]['accountsId'][] = $account['ID'];

                /** @var Subaccount $subAccount */
                foreach ($subAccounts as $subAccount) {
                    if (!$subAccount->getCreditcard() instanceof CreditCard) {
                        continue;
                    }
                    $existAnyTransactions[$index] = $existAnyTransactions[$index]
                        || $this->checkTransactionsExists($subAccount->getId(), $limits['start']);

                    $result['ownersList'][$index]['availableCards'][] = [
                        'providerId' => $account['ProviderID'],
                        'providerCode' => $account['ProviderCode'],
                        'providerName' => $account['ProviderName'],
                        'subAccountId' => $subAccount->getSubaccountid(),
                        'creditCardId' => $subAccount->getCreditcard()->getId(),
                        'creditCardName' => CreditCard::formatCreditCardName(
                            $subAccount->getDisplayname(),
                            $subAccount->getCreditcard()->getName(),
                            $account['ProviderID']
                        ),
                        'creditCardImage' => $this->getHostUrl($subAccount->getCreditcard()->getPicturePath('medium')),
                        'historyPage' => $this->router->generate(
                            "aw_subaccount_history_view",
                            ["accountId" => $subAccount->getAccountid()->getId(),
                                "subAccountId" => $subAccount->getId()]
                        ),
                        'transactionsPage' => $this->router->generate("aw_transactions_subaccount", ["subAccountId" => $subAccount->getId()]),
                    ];

                    $result['ownersList'][$index]['haveCardsId'][] = $subAccount->getCreditcard()->getId();
                }

                if (!$existAnyTransactions[$index]) {
                    $result['ownersList'][$index]['availableCards'] = [];
                }

                $detectedCards = $account['MainProperties']['DetectedCards']['DetectedCards'] ?? [];

                foreach ($detectedCards as $detectedCard) {
                    $cardId = $this->cardMatcher->identify($detectedCard['DisplayName'], $account['ProviderID']);

                    if ($cardId) {
                        $result['ownersList'][$index]['haveCardsId'][] = $cardId;
                    }
                }
            } finally {
                $this->logger->popProcessor();
            }
        }

        $user = $listOptions->get(Options::OPTION_USER);
        $existedCards = [];

        if ($user instanceof Usr) {
            $existedEntities = $this->em->getRepository(UserCreditCard::class)->findBy([
                'user' => $user,
                'isClosed' => false,
            ]);
            $existedCards = array_map(static function (UserCreditCard $item) {
                return $item->getCreditCard()->getId();
            }, $existedEntities);
        }

        // фильтр всех доступных карт для оффера
        $cardsFilter = [];
        $cards = $context ?
            $context->getCreditCardsMap()->getValue() :
            $this->em
            ->createQueryBuilder()
            ->select('cc, ccm')
            ->from(CreditCard::class, 'cc')
            ->leftJoin('cc.multipliers', 'ccm')
            ->getQuery()
            ->execute();

        /** @var CreditCard $card */
        foreach ($cards as $card) {
            if ($card->getMultipliers()->count() < 1) {
                continue;
            }
            $isExisted = in_array($card->getId(), $existedCards);

            if ($card->isDiscontinued() && !$isExisted) {
                continue;
            }

            if (!isset($cardsFilter[$card->getProvider()->getId()])) {
                $cardsFilter[$card->getProvider()->getId()] = [
                    'providerId' => $card->getProvider()->getId(),
                    'providerCode' => $card->getProvider()->getCode(),
                    'displayName' => $card->getProvider()->getShortname(),
                    'cardsList' => [],
                ];
            }

            $cardsFilter[$card->getProvider()->getId()]['cardsList'][] = [
                'creditCardId' => $card->getId(),
                'creditCardName' => $card->getName(),
                'creditCardImage' => $this->getHostUrl($card->getPicturePath('medium')),
                'description' => $card->getDescription(),
                'isBusiness' => $card->isBusiness(),
                'existedCard' => $isExisted,
            ];
        }

        $result['offerCardsFilter'] = array_values($cardsFilter);
        $result['userExistedCardsId'] = $existedCards;

        $result['dateRanges'] = [
            [
                'name' => $this->translator->trans(/** @Desc("Current Month") */
                    "spent-analysis.this-month"),
                'value' => BankTransactionsDateUtils::THIS_MONTH,
            ],
            [
                'name' => $this->translator->trans(/** @Desc("Current Quarter") */
                    "spent-analysis.this-quarter"),
                'value' => BankTransactionsDateUtils::THIS_QUARTER,
            ],
            [
                'name' => $this->translator->trans(/** @Desc("Last Month") */
                    "spent-analysis.last-month"),
                'value' => BankTransactionsDateUtils::LAST_MONTH,
            ],
            [
                'name' => $this->translator->trans(/** @Desc("Last Quarter") */
                    "spent-analysis.last-quarter"),
                'value' => BankTransactionsDateUtils::LAST_QUARTER,
            ],
            [
                'name' => $this->translator->trans(/** @Desc("This Year") */
                    "spent-analysis.this-year"),
                'value' => BankTransactionsDateUtils::THIS_YEAR,
            ],
            [
                'name' => $this->translator->trans(/** @Desc("Last Year") */
                    "spent-analysis.last-year"),
                'value' => BankTransactionsDateUtils::LAST_YEAR,
            ],
        ];

        $result['accounts'] = array_map(static function ($account) {
            return ['providerCode' => $account['ProviderCode'], 'accountId' => $account['ID']];
        }, $accounts->getAccounts());

        return $result;
    }

    private function queryData(array $subAccIds, $startDate, $endDate, $merchantId = null)
    {
        $sql = "
            SELECT 
                h.UUID, DATE_FORMAT(h.PostingDate, \"%Y-%m-%d\") as postingDate, h.MerchantID as merchantId, 
                COALESCE(m.DisplayName, m.Name) as merchantName, cc.Name as cardName, Amount as amount, Miles as miles, 
                sa.DisplayName as displayName, sc.Name as merchantCategory, sa.CreditCardID as cardId, h.SubAccountID as subAccountId,
                a.ProviderID as providerId
            FROM AccountHistory h 
                JOIN Merchant m ON h.MerchantID = m.MerchantID
                LEFT JOIN ShoppingCategory sc ON m.ShoppingCategoryID = sc.ShoppingCategoryID
                JOIN SubAccount sa ON h.SubAccountID = sa.SubAccountID
                JOIN Account a on h.AccountID = a.AccountID
                JOIN CreditCard cc ON cc.CreditCardID = sa.CreditCardID
            WHERE h.SubAccountID IN (?)
            AND h.PostingDate >= ? AND h.PostingDate < ?
            AND Miles > 0
            AND h.Multiplier > 0
            ::MERCHANT::
            ORDER BY h.MerchantID, h.PostingDate DESC
        ";

        $sqlReplace = $merchantId !== null ? "AND h.MerchantID = $merchantId" : '';
        $sql = str_replace('::MERCHANT::', $sqlReplace, $sql);

        $rows = $this->replica->executeQuery(
            $sql,
            [$subAccIds, $startDate, $endDate],
            [Connection::PARAM_INT_ARRAY, \PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_INT]
        )->fetchAll();

        return $rows;
    }

    private function compareCards(OfferCreditCardItem $card, $listCards, $transfers): ?int
    {
        $foundConvertablePos = null;

        /** @var OfferCreditCardItem $listCard */
        foreach ($listCards as $pos => $listCard) {
            if (null === $listCard
                || $card->cardId === $listCard->cardId
                || $listCard->value > $card->value
                || PROVIDER_KIND_CREDITCARD !== $listCard->providerKind
                || $listCard->creditCard->isCashBackOnly()
            ) {
                continue;
            }

            $listCardProviderId = $listCard->providerId;
            $cardProviderId = $card->providerId;

            $isTransferable = isset($transfers[$listCardProviderId][$cardProviderId]);

            if (!$isTransferable) {
                continue;
            }

            $ratio = round(
                $transfers[$listCardProviderId][$cardProviderId]['TargetRate'] / $transfers[$listCardProviderId][$cardProviderId]['SourceRate'],
                2
            );

            $listCardRatioValue = $listCard->multiplier * $ratio;
            $cardRatioValue = $card->multiplier;

            if ($listCardRatioValue >= $cardRatioValue) {
                if ($listCard->value === $card->value
                    && $listCard->providerId === $card->providerId) {
                    continue;
                }

                $foundConvertablePos = $pos;
            }
        }

        return $foundConvertablePos;
    }

    private function getHostUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        return $this->channel . '://' . $this->host . $path;
    }
}
