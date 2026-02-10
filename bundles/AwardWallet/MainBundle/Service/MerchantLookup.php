<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\Entity\Merchant;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Repositories\ParameterRepository;
use AwardWallet\MainBundle\Entity\ShoppingCategory;
use AwardWallet\MainBundle\Entity\ShoppingCategoryGroup;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Globals\Utils\IteratorFluent;
use AwardWallet\MainBundle\Repository\CreditCardRepository;
use AwardWallet\MainBundle\Repository\MerchantRepository;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Exceptions\DoNotStoreException;
use AwardWallet\MainBundle\Service\Cache\Memoizer;
use AwardWallet\MainBundle\Service\Cache\Model\CacheItemReference;
use AwardWallet\MainBundle\Service\Cache\Tags;
use AwardWallet\MainBundle\Service\CreditCards\Schema\CreditCardCategoryList;
use AwardWallet\MainBundle\Service\MerchantLookup\IncoherentResultException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ForwardCompatibility\DriverStatement;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function Duration\hours;

class MerchantLookup
{
    public const EXCLUDE_REVERSE_LOOKUP_GROUPS = [2/* Lyft */ , 3/* Walgreens */ , 11/* Amex Travel */ , 18/* Chase Pay */];
    protected const PATTERN_GROUP_MIN_TRANSACTIONS_THRESHOLD_PERCENT = 5;
    protected const MAX_TOP_MERCHANTS_WITHOUT_CC = 1;
    protected const PREFLIGHT_SET_SIZE = 1000;
    protected const TOP_MERCHANTS_TRANSACTIONS_STEPS = [10000, 5000, 1000, 100];

    protected const PREFLIGHT_RESULT_FULL = 1;
    protected const PREFLIGHT_RESULT_SLOW = 2;
    protected const PREFLIGHT_RESULT_PARTIAL = 3;

    private EntityManagerInterface $em;
    private CacheManager $cacheManager;
    private ParameterRepository $paramRepository;
    private LoggerInterface $logger;
    private Memoizer $memoizer;
    private Connection $databaseConnection;
    private CreditCardRepository $creditCardRepository;
    private MerchantRepository $merchantRepository;
    private Connection $sphinxConnection;
    private CreditCardCategoryList $creditCardCategoryList;

    public function __construct(
        Connection $databaseConnection,
        EntityManagerInterface $em,
        CreditCardRepository $creditCardRepository,
        MerchantRepository $merchantRepository,
        CacheManager $cacheManager,
        ParameterRepository $paramRepository,
        LoggerInterface $logger,
        Memoizer $memoizer,
        Connection $sphinxConnection,
        CreditCardCategoryList $creditCardCategoryList
    ) {
        $this->em = $em;
        $this->cacheManager = $cacheManager;
        $this->paramRepository = $paramRepository;
        $this->logger = $logger;
        $this->memoizer = $memoizer;
        $this->databaseConnection = $databaseConnection;
        $this->creditCardRepository = $creditCardRepository;
        $this->merchantRepository = $merchantRepository;
        $this->sphinxConnection = $sphinxConnection;
        $this->creditCardCategoryList = $creditCardCategoryList;
    }

    public function getReverseLookupInitial()
    {
        return $this->cacheManager->load(
            new CacheItemReference(
                'spend_analisys_credit_cards_info_' . mt_rand(),
                Tags::addTagPrefix([Tags::TAG_CREDIT_CARDS_INFO]),
                function () {
                    return $this->buildReverseLookupInitial();
                }
            )
        );
    }

    public function buildReverseLookupOffer(ShoppingCategoryGroup $group)
    {
        $result = [];

        $categories = $group->getCategories()->getValues();

        if (empty($categories)) {
            return $result;
        }

        $ids = [];

        /** @var ShoppingCategory $category */
        foreach ($categories as $category) {
            $ids[] = $category->getId();
        }

        $qb = $this->merchantRepository->createQueryBuilder('m');
        $query = $qb->where($qb->expr()->in('m.shoppingcategory', $ids))
                    ->andWhere('m.transactions > 10')
                    ->andWhere('LENGTH(m.name) < 60')
                    ->orderBy('m.transactions', 'desc')
                    ->setMaxResults(100)
                    ->getQuery();

        /** @var Merchant $merchant */
        foreach ($query->getResult() as $merchant) {
            $knownCategories = $this->buildMerchantKnownCategories($merchant, true);

            if (empty($knownCategories)) {
                continue;
            }
            $result[] = [
                'name' => str_replace('#', '', $merchant->getName()),
                'knownCategories' => $knownCategories,
            ];
        }

        return $result;
    }

    /**
     * @return array{tokens: list<string>, merchants: list<int>}
     */
    public function getMerchantFullTextOnlyList(string $query): array
    {
        $this->logger->info('merchant lookup query: ' . $query);
        $tokens =
            MySQLFullTextSearchUtils::createTokens(MySQLFullTextSearchUtils::filterQueryForFullTextSearch($query))
            ->usort(fn (string $a, string $b) => \strlen($b) <=> \strlen($a))
            ->toArray();

        $this->logger->info('merchant lookup keywords breakdown', ['merchant_keywords_list' => $tokens]);
        $result = [
            'tokens' => $tokens,
            'merchants' => [],
        ];

        if (!$tokens) {
            return $result;
        }

        $fullTextQuery =
            it($tokens)
                ->map(fn (string $part) => "{$part}*")
                ->joinToString(' ');

        $merchantIds = $this->sphinxConnection->executeQuery("select id from Merchant where match(:fullTextQuery) limit " . self::PREFLIGHT_SET_SIZE, ['fullTextQuery' => $fullTextQuery])->fetchFirstColumn();
        $this->logger->info('found merchant ids', ['merchant_ids' => $merchantIds]);

        if (count($merchantIds) === 0) {
            return $result;
        }

        $result['merchants'] = $merchantIds;

        return $result;
    }

    public function getMerchantLookupList(string $query, int $maxResults = 10, bool $cached = true): array
    {
        ['merchants' => $merchantIds, 'tokens' => $tokens] = $this->getMerchantFullTextOnlyList($query);

        if (!$merchantIds) {
            return [];
        }

        $sqlFilters = [];
        $params = [];
        $pregMatchParts = [];

        foreach ($tokens as $i => $token) {
            $pregMatchParts[] = "regexp_like(subInner.DisplayName, :rePattern{$i}, 'i')";
            $params["rePattern{$i}"] = ['\\b' . \preg_quote($token, '/'), ParameterType::STRING];
        }

        $sqlFilters[] = ' and ' . \implode(' and ', $pregMatchParts);

        return $cached ?
            $this->doMemoizedSearch(
                $sqlFilters,
                $params,
                $maxResults,
                $merchantIds
            ) :
            (function () use ($maxResults, $params, $sqlFilters, $merchantIds) {
                try {
                    return $this->doSearch(
                        $sqlFilters,
                        $params,
                        $maxResults,
                        $merchantIds
                    );
                } catch (IncoherentResultException $e) {
                    return $e->getMerchants();
                }
            })();
    }

    public function getMerchantByExactName(string $name, ?int $categoryGroupId): ?Merchant
    {
        $queryBuilder = $this->merchantRepository->createQueryBuilder('merchant')
            ->where('merchant.shoppingcategory IS NOT NULL')
            ->andWhere('LENGTH(merchant.name) < 60')
            ->andWhere('merchant.name = :query')
            ->andWhere('merchant.notnullgroupid = :categorygroup')
            ->setParameter(':query', $name)
            ->setParameter(':categorygroup', $categoryGroupId ?? 0)
            ->setMaxResults(1);

        /** @var Merchant[] $result */
        $result = $queryBuilder->getQuery()->getResult();

        return empty($result) ? null : $result[0];
    }

    public function buildMerchantKnownCategories(Merchant $merchant, $filterEmptyCategory = false)
    {
        $currentVersion = $this->paramRepository->getParam(ParameterRepository::MERCHANT_REPORT_VERSION);

        $trusted = 0.01;
        $trustedTransactions = 50;

        $result = [];
        $rows = $this->databaseConnection->executeQuery("
            SELECT c.ProviderID, p.ShortName, r.ShoppingCategoryID, sc.Name, sum(r.Transactions) AS Transactions 
            FROM MerchantReport r USE INDEX(MerchantReportSelectIndex)
                LEFT JOIN ShoppingCategory sc ON r.ShoppingCategoryID = sc.ShoppingCategoryID
                JOIN CreditCard c ON r.CreditCardID = c.CreditCardID
                JOIN Provider p ON c.ProviderID = p.ProviderID
            WHERE r.Version = ?
            AND r.MerchantID = ?
            GROUP BY c.ProviderID, p.ShortName, r.ShoppingCategoryID, sc.Name
            ORDER BY c.ProviderID, Transactions DESC
        ", [(int) $currentVersion, $merchant->getId()], [\PDO::PARAM_INT, \PDO::PARAM_INT])->fetchAll();

        $summ = 0;

        foreach ($rows as $row) {
            $summ += (int) $row['Transactions'];
        }

        $toIgnore = ShoppingCategory::IGNORED_CATEGORIES;

        if ($filterEmptyCategory) {
            $toIgnore[] = 0;
        }

        foreach ($rows as $row) {
            if (in_array((int) $row['ShoppingCategoryID'], $toIgnore)) {
                continue;
            }

            $transactions = (int) $row['Transactions'];

            if ($transactions < $trustedTransactions) {
                continue;
            }

            $percentage = $transactions / $summ;

            /* Способ доверия данным = 5%($trusted) от всех транзакций по мерчанту */
            if ($percentage < $trusted) {
                continue;
            }

            if (Provider::BANKOFAMERICA_ID === (int) $row['ProviderID']) {
                $row['ShortName'] = str_replace('BofA', 'Bank of America', $row['ShortName']);
            }

            if (!isset($result[$row['ShortName']])) {
                $result[$row['ShortName']] = [];
            }

            $result[$row['ShortName']][] = [
                'name' => $row['Name'],
                'percentage' => round($percentage * 100),
                'providerId' => (int) $row['ProviderID'],
            ];
        }

        return $result;
    }

    private static function createLikeSearchPattern(IteratorFluent $smallTokens): string
    {
        $pattern = $smallTokens
            ->map(fn (string $smallToken) => \str_replace(['\\', '_', '%'], ['\\\\', '\\_', '\\%'], $smallToken))
            ->collect()
            ->usort(fn ($a, $b) => \strlen($b) <=> \strlen($a))
            ->joinToString('%');

        if (StringUtils::isEmpty($pattern)) {
            return '';
        }

        return "%{$pattern}%";
    }

    private function buildReverseLookupInitial()
    {
        $cards = $this->creditCardRepository->findBy([], ["provider" => "ASC", "name" => "ASC"]);
        $result = [];

        foreach ($cards as $card) {
            $cardGroups = [];
            $categories = $this->creditCardCategoryList->getCategories($card->getId());

            foreach ($categories as $category) {
                if ('CreditCardShoppingCategoryGroup' !== $category['SchemaName']
                    || in_array((int) $category['GroupID'], self::EXCLUDE_REVERSE_LOOKUP_GROUPS)
                    || empty($category['Name'])
                ) {
                    continue;
                }

                $cardGroups[] = [
                    'id' => (int) $category['ID'],
                    'groupId' => (int) $category['GroupID'],
                    'groupName' => $category['Name'],
                    'multiplier' => $category['Multiplier'],
                ];
            }

            if (empty($cardGroups)) {
                continue;
            }

            $result[] = [
                'cardId' => $card->getId(),
                'name' => $card->getName(),
                'providerName' => $card->getProvider()->getShortname(),
                'multipliers' => $cardGroups,
            ];
        }

        return $result;
    }

    private function doMemoizedSearch(array $sqlFilters, array $params, int $maxResults, array $preFlightSet): array
    {
        $cacheSignificantParams = $params;
        unset($cacheSignificantParams['fullTextMerchantIds']);

        return $this->memoizer->memoize(
            'merchant_lookup_v14',
            hours(12),
            function (array $sqlFilters, array $_, int $maxResults) use ($params, $preFlightSet) {
                try {
                    return $this->doSearch(
                        $sqlFilters,
                        $params,
                        $maxResults,
                        $preFlightSet
                    );
                } catch (IncoherentResultException $e) {
                    throw new DoNotStoreException($e->getMerchants());
                }
            },
            $sqlFilters,
            $cacheSignificantParams,
            $maxResults
        );
    }

    private function tryPreflightSearch(string $displayNameSqlFilter, array $params)
    {
        [$values, $types] = $this->splitParamsAndTypes($params);

        try {
            $preFlightSet = $this->databaseConnection
                ->executeQuery('
                    select /*+ MAX_EXECUTION_TIME(5000) */ mInitial.MerchantID
                    from Merchant mInitial
                    where 1=1 ' . $displayNameSqlFilter . '
                    limit ' . self::PREFLIGHT_SET_SIZE,
                    $values,
                    $types
                )
                ->fetchFirstColumn();
        } catch (\Throwable $e) {
            if (\stripos($e->getMessage(), 'execution time') !== false) {
                $this->logger->error('merchant lookup preflight query timed out after 5000ms');

                return [self::PREFLIGHT_RESULT_SLOW, []];
            }

            throw $e;
        }

        return [
            count($preFlightSet) < self::PREFLIGHT_SET_SIZE ?
                self::PREFLIGHT_RESULT_FULL :
                self::PREFLIGHT_RESULT_PARTIAL,
            $preFlightSet,
        ];
    }

    private function searchMerchants(string $innerSQL, array $extraFields, array $params, int $maxResults, bool $useMaxTopMerchantsCriterion, bool $skipRanking = false): DriverStatement
    {
        if ($useMaxTopMerchantsCriterion) {
            $params['maxTopMerchantsWithoutCC'] = [
                self::MAX_TOP_MERCHANTS_WITHOUT_CC,
                ParameterType::INTEGER,
            ];
        }

        $params['patternGroupMinTransactionsThreshold'] = [
            self::PATTERN_GROUP_MIN_TRANSACTIONS_THRESHOLD_PERCENT / 100,
            ParameterType::STRING,
        ];
        [$values, $types] = $this->splitParamsAndTypes($params);

        $extraFieldsSQL = $extraFields ?
            (
                ', '
                . it($extraFields)
                ->map(fn (string $field) => "sub.{$field}")
                ->joinToString(', ')
            )
            : '';

        return
           $this->databaseConnection
            ->executeQuery('
                select /*+ MAX_EXECUTION_TIME(30000) */ 
                    sub.MerchantID
                    ' . $extraFieldsSQL . '
                from (
                    select 
                        m.MerchantID, 
                        m.Transactions,
                        m.MerchantPatternID,
                        (
                            EXISTS(                        
                                select 1
                                from ShoppingCategoryGroup scg
                                left join CreditCardShoppingCategoryGroup ccscg on 
                                    scg.ShoppingCategoryGroupID = ccscg.ShoppingCategoryGroupID
                                    and (
                                        ccscg.StartDate is null
                                        or ccscg.StartDate <= NOW()
                                    )
                                    and (
                                        ccscg.EndDate is null
                                        or ccscg.EndDate > NOW()
                                    )
                                where m.ShoppingCategoryGroupID = scg.ShoppingCategoryGroupID
                            )
                            or
                            EXISTS(
                                select 1
                                from MerchantPatternGroup mpg
                                join CreditCardMerchantGroup ccmg on 
                                    mpg.MerchantGroupID = ccmg.MerchantGroupID
                                    and (
                                        ccmg.StartDate is null
                                        or ccmg.StartDate <= NOW()
                                    )
                                    and (
                                        ccmg.EndDate is null
                                        or ccmg.EndDate > NOW()
                                    )
                                where m.MerchantPatternID = mpg.MerchantPatternID
                            )
                        ) as HasCreditCards,
                        row_number() over (order by m.Transactions desc) as RowN,
                        sum(m.Transactions) over (partition by ifnull(m.MerchantPatternID, 0)) as TotalPatternTransactions
                    from (
                        ' . $innerSQL . '
                    ) mInner
                    join Merchant m on m.MerchantID = mInner.MerchantID
                    where
                        m.ShoppingCategoryID is not null
                        and LENGTH(m.Name) < 60
                ) sub
                where '
               . (
                   $skipRanking ?
                       '1=1' :
                        (
                            (
                                $useMaxTopMerchantsCriterion ?
                                    'sub.RowN <= :maxTopMerchantsWithoutCC /* sub.RowN starts from 1 */' :
                                    '1=0'
                            )
                            . '
                                or (
                                    sub.HasCreditCards
                                    and (
                                        sub.MerchantPatternID is null
                                        or (sub.Transactions / sub.TotalPatternTransactions >= :patternGroupMinTransactionsThreshold)
                                    )
                                )'
                        )
               ) .
                ' order by sub.RowN
                limit ' . $maxResults,
                $values,
                $types
            );
    }

    private function splitParamsAndTypes(array $params): array
    {
        $values = [];
        $types = [];

        foreach ($params as $name => [$value, $type]) {
            $values[$name] = $value;
            $types[$name] = $type;
        }

        return [$values, $types];
    }

    private function doSearch(array $sqlFilters, array $params, int $maxResults, array $preFlightSet): array
    {
        [$regexpFilter] = $sqlFilters;
        $preFlightStatus = \count($preFlightSet) < self::PREFLIGHT_SET_SIZE ?
            self::PREFLIGHT_RESULT_FULL :
            self::PREFLIGHT_RESULT_PARTIAL;

        if (self::PREFLIGHT_RESULT_FULL === $preFlightStatus) {
            $params['preFlightSet'] = [$preFlightSet, Connection::PARAM_INT_ARRAY];
            $merchantsIds =
                $this->searchMerchants('
                    select subInner.MerchantID
                    from Merchant subInner
                    where subInner.MerchantID in (:preFlightSet)',
                    [],
                    $params,
                    $maxResults,
                    true,
                    \count($preFlightSet) <= $maxResults
                )
                ->fetchFirstColumn();
        } else {
            $merchantsIds =
                it(\array_merge(
                    [\PHP_INT_MAX],  // for the upper boundary in first between
                    self::TOP_MERCHANTS_TRANSACTIONS_STEPS
                ))
                ->sliding(2)
                ->flatMapIndexed(fn (array $transactionsBoundaries, int $i) =>
                    $this->searchMerchants('
                        select subInner.MerchantID
                        from Merchant subInner
                        where
                            (subInner.Transactions between :minTransactions and :maxTransactions)
                            ' . $regexpFilter,
                        [],
                        \array_merge(
                            $params,
                            [
                                'minTransactions' => [$transactionsBoundaries[1], ParameterType::INTEGER],
                                'maxTransactions' => [$transactionsBoundaries[0] - 1, ParameterType::INTEGER],
                            ]
                        ),
                        $maxResults,
                        $i === 0
                    )
                    ->fetchFirstColumn()
                )
                ->take($maxResults)
                ->toArray();
        }

        $merchantsIds = \array_unique($merchantsIds);

        if (!$merchantsIds) {
            return [];
        }

        $q = $this->em->createQuery("
            select m
            from AwardWallet\MainBundle\Entity\Merchant m
            where 
                m.id in (:merchantIds)
            order by
                m.transactionsLast3Months desc
        ");
        $q->setParameter(':merchantIds', $merchantsIds);

        $merchants = it($q->execute())->toArray();
        $result = [];

        /** @var Merchant $merchant */
        foreach ($merchants as $merchant) {
            $merchantGroup = $merchant->chooseShoppingCategory()->getGroup();
            $categoryName = $merchant->chooseShoppingCategory()->getName();
            $categoryUrl = null;

            if ($merchantGroup instanceof ShoppingCategoryGroup) {
                $categoryName = $merchantGroup->getName();
                $categoryUrl = $merchantGroup->getClickURL();
            }

            $result[] = [
                'id' => $merchant->getId(),
                'label' => $merchant->getDisplayName() ?? $merchant->getName(),
                'nameToUrl' =>
                    \urlencode($merchant->getName())
                    . '_' . $merchant->getId(),
                'category' => \html_entity_decode($categoryName),
                'url' => $categoryUrl,
            ];
        }

        if (\count($merchantsIds) !== \count($merchants)) {
            throw new IncoherentResultException($result);
        }

        return $result;
    }
}
