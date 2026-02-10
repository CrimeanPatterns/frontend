<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\Globals\Utils\Criteria;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Model\CacheItemReference;
use AwardWallet\MainBundle\Service\Cache\Tags;
use Doctrine\DBAL\Connection;

use function AwardWallet\MainBundle\Globals\Utils\f\coalesce;
use function AwardWallet\MainBundle\Globals\Utils\f\column;
use function AwardWallet\MainBundle\Globals\Utils\f\compareBy;
use function AwardWallet\MainBundle\Globals\Utils\f\compareColumns;
use function AwardWallet\MainBundle\Globals\Utils\f\orderBy;
use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtAssoc;

class ProviderPhoneResolver
{
    private const STATUS_COUNTRY_PATTERN = [
        [true, true],
        [true, false],
        [false, true],
        [false, false],
    ];

    private CacheManager $cacheManager;

    private Connection $connection;

    public function __construct(CacheManager $cacheManager, Connection $connection)
    {
        $this->cacheManager = $cacheManager;
        $this->connection = $connection;
    }

    public function getUsefulPhones($providersQueryList, $regionsList = [], array $orderByList = [])
    {
        // account column should exist  in all $provider rows or nowhere
        $isExistsAccounts = isset($providersQueryList[0]['account']);

        // collapse providers by keys:
        //      provider_15-status_gold_golden-country_united_states => [
        //          ...
        //          'accounts' => [1001, 1002, 1003],
        //          ...
        //      provider_20_status_empty_status-country_empty_country
        $cacheMetadataByCacheKeyMap = $this->prepareCacheMetadata($providersQueryList);
        $phonesByCacheKeyMap = $this->loadDataFromCache($cacheMetadataByCacheKeyMap, $regionsList);

        if (!$orderByList) {
            if (\count($regionsList) > 0) {
                $orderByList = [
                    [compareColumns('CountryID'),                     Criteria::DESC],
                    [compareBy(coalesce(column('DefaultPhone'), 0)), Criteria::DESC],
                    [compareColumns('PhoneFor'),                     Criteria::ASC],
                    [compareColumns('Phone'),                        Criteria::ASC],
                ];
            } else {
                $orderByList = [
                    [compareBy(coalesce(column('DefaultPhone'), 0)), Criteria::DESC],
                    [compareColumns('PhoneFor'),                     Criteria::ASC],
                    [compareColumns('Phone'),                        Criteria::ASC],
                ];
            }
        }

        $orderByList = \array_merge(
            [
                [compareColumns('AccountID'),    Criteria::ASC],
                [compareColumns('PhoneGroupID'), Criteria::ASC],
            ],
            $orderByList
        );

        $phonesList = it($phonesByCacheKeyMap)->flatten(1);

        if ($isExistsAccounts) {
            $phonesList->flatMap(function (array $providerPhone) use ($cacheMetadataByCacheKeyMap) {
                $cacheKey = $providerPhone['CacheKey'];

                foreach ($cacheMetadataByCacheKeyMap[$cacheKey]['accounts'] ?? [] as $accountId) {
                    $providerPhone['AccountID'] = (string) $accountId;

                    yield $providerPhone;
                }
            });
        }

        $phonesList = $phonesList
            ->map(function (array $providerPhone) {
                unset($providerPhone['CacheKey']);

                return $providerPhone;
            })
            ->collect()
            ->usort(orderBy(...$orderByList))
            ->toArray();

        return \array_values($phonesList);
    }

    protected function prepareCacheMetadata(array $providers): array
    {
        return
            it($providers)
            ->reindex(function (array $provider): string {
                if (isset($provider['status'])) {
                    $normalizedStatuses =
                        it((array) $provider['status'])
                        ->mapToLower('UTF-8')
                        ->unique()
                        ->toJSON();
                } else {
                    $normalizedStatuses = 'empty_status';
                }

                $normalizedStatuses = 'status_' . \preg_replace('/[^a-zA-Z0-9_-]/', '_', $normalizedStatuses);
                $cacheKeyParts = [
                    'provider_' . $provider['provider'],
                    $normalizedStatuses,
                    'country_' . \preg_replace('/[^a-zA-Z0-9_-]/', '_', $provider['country'] ?? 'empty_country'),
                ];

                return 'provider_phones_v3_' . \implode('-', $cacheKeyParts);
            })
            ->collapseByKey()
            ->map(function (array $group): array {
                $firstRow = $group[0];
                // collapse account ids
                $firstRow['accounts'] = it($group)
                    ->filterIsSetColumn('account')
                    ->column('account')
                    ->mapToInt()
                    ->unique()
                    ->toArray();
                unset($firstRow['account']);

                if (isset($firstRow['status'])) {
                    $firstRow['status'] = (array) $firstRow['status'];
                }

                return $firstRow;
            })
            ->toArrayWithKeys();
    }

    protected function loadDataFromCache(array $cacheMetadataMap, array $regionsList = []): array
    {
        return $this->cacheManager->load((new CacheItemReference(
            \array_keys($cacheMetadataMap),
            Tags::addTagPrefix([
                Tags::TAG_REGIONS,
                Tags::TAG_ELITE_LEVELS,
            ]),
            function (array $missingKeysList) use ($cacheMetadataMap, $regionsList) {
                $resultByKey =
                    it($missingKeysList)
                    ->flatMap(function (string $missingKey) use ($cacheMetadataMap, $regionsList) {
                        yield from $this->generateQueryData($missingKey, $cacheMetadataMap, $regionsList);
                    })
                    ->chunk(100)
                    ->flatMap(function (array $queriesDataChunk) {
                        $sqls = \array_column($queriesDataChunk, 'sql');
                        $params =
                            it($queriesDataChunk)
                            ->column('params')
                            ->flatten(1)
                            ->toArray();

                        yield from stmtAssoc(
                            $this->connection->executeQuery(
                                \implode("\n UNION \n", $sqls),
                                \array_column($params, 0),
                                \array_column($params, 1)
                            )
                        )
                            ->reindexByColumn('CacheKey')
                            ->collapseByKey();
                    })
                    ->toArrayWithKeys();

                foreach (
                    it($missingKeysList)
                    ->filterNotByInMap($resultByKey) as $missingKey
                ) {
                    $resultByKey[$missingKey] = [];
                }

                return $resultByKey;
            }))
            ->setOptions(CacheItemReference::OPTION_RETURN_MAP)
        );
    }

    protected function generateQueryData(string $missingKey, array $cacheMetadataMap, array $regionsList): \Generator
    {
        $isExistRegions = \count($regionsList) > 0;
        $providerData = $cacheMetadataMap[$missingKey];
        $countryCondition = (isset($providerData['country']) && \strtolower($providerData['country']) == 'united states') ?
            'c.Name like \'united states\'' :
            '1 = 0';
        $queryParams = it([]);
        $querySqls = [];

        foreach (self::STATUS_COUNTRY_PATTERN as [$withStatus, $withCountry]) {
            $categoryConditions = ['p.ProviderID = ?'];
            $categoryParams = [
                [$missingKey, \PDO::PARAM_STR],
                [$providerData['provider'], \PDO::PARAM_INT],
            ];

            if ($withStatus) {
                if (isset($providerData['status'])) {
                    $statuses = \array_unique($providerData['status']);

                    if ($withCountry) {
                        $categoryConditions[] = 'tel.ValueText in (?)';
                        $categoryParams[] = [$statuses, Connection::PARAM_STR_ARRAY];
                    } else {
                        $categoryConditions[] = '(tel.ValueText IN (?) OR el.Name IN (?))';
                        $categoryParams[] = [$statuses, Connection::PARAM_STR_ARRAY];
                        $categoryParams[] = [$statuses, Connection::PARAM_STR_ARRAY];
                    }
                } else {
                    continue;
                }
            }

            if ($withCountry) {
                if ($isExistRegions) {
                    $categoryConditions[] = "(ph.CountryID IN (?) OR ph.CountryID IS NULL)";
                    $categoryParams[] = [$regionsList, Connection::PARAM_INT_ARRAY];
                } elseif (isset($providerData['country'])) {
                    $categoryConditions[] = "((c.Name like ?) OR ($countryCondition))";
                    $categoryParams[] = [$providerData['country'], \PDO::PARAM_STR];
                } else {
                    continue;
                }
            } else {
                if ($isExistRegions) {
                    continue;
                } elseif (isset($providerData['country'])) {
                    $categoryConditions[] = "(c.Name not like ? OR c.Name IS NULL)";
                    $categoryParams[] = [$providerData['country'], \PDO::PARAM_STR];
                }
            }

            $sqlMethod = [$this,
                'getQuery' .
                ($withStatus ? 'With' : 'Without') . 'Status' .
                ($withCountry ? 'With' : 'Without') . 'Country',
            ];

            $querySqls[] = $sqlMethod($categoryConditions);
            $queryParams->chain($categoryParams);
        }

        if ($querySqls) {
            yield [
                'sql' => \implode("\n UNION \n", $querySqls),
                'params' => $queryParams,
            ];
        }
    }

    protected function getQueryWithStatusWithCountry(array $whereConditions): string
    {
        return '(' . "
            SELECT
                ? as CacheKey                       ,
                p.ProviderID						,
                NULL AS AccountID                   , 
                ph.*	     						,
                el.Name AS Name						,
                'StatusWithRegion' AS PhoneGroup	,
                '1' AS PhoneGroupID					,
                c.Name  AS RegionCaption
            FROM
                Provider p 
                JOIN EliteLevel el
                    ON p.ProviderID = el.ProviderID
                JOIN ProviderPhone ph ON 
                    el.ProviderID = ph.ProviderID AND 
                    el.EliteLevelID = ph.EliteLevelID
                JOIN TextEliteLevel tel ON 
                    el.EliteLevelID = tel.EliteLevelID
                LEFT JOIN Country c ON 
                    c.CountryID = ph.CountryID
                WHERE  
                    " . \implode("\n AND \n ", $whereConditions) . " and # with status with country
                    ph.Valid = 1
        " . ')';
    }

    protected function getQueryWithStatusWithoutCountry(array $whereConditions)
    {
        return '(' . "
            SELECT
                ? as CacheKey                       ,
                p.ProviderID						,
                NULL AS AccountID                   ,
                ph.*	     						,
                el.Name AS Name						,
                'StatusWithoutRegion' AS PhoneGroup	,
                '2' AS PhoneGroupID					,
                c.Name  AS RegionCaption
            FROM
                Provider p
                JOIN EliteLevel el ON 
                    p.ProviderID = el.ProviderID
                JOIN ProviderPhone ph ON 
                    el.ProviderID = ph.ProviderID AND 
                    el.EliteLevelID = ph.EliteLevelID
                JOIN TextEliteLevel tel ON 
                    el.EliteLevelID = tel.EliteLevelID
                LEFT JOIN Country c ON 
                    c.CountryID = ph.CountryID
            WHERE    
                " . \implode("\n AND \n ", $whereConditions) . " and # with status without country
                ph.Valid = 1
        " . ')';
    }

    protected function getQueryWithoutStatusWithCountry(array $whereConditions)
    {
        return '(' . "
            SELECT
                ? as CacheKey                       ,
                p.ProviderID						,
                NULL AS AccountID                   ,
                ph.*	     						,
                CASE ph.PhoneFor
                    WHEN '1' THEN 'General'
                    WHEN '2' THEN 'Reservations'
                    WHEN '3' THEN 'Customer support'
                    WHEN '4' THEN 'Member Services'
                    WHEN '5' THEN 'Award Travel'
                    END AS Name,
                'WithoutStatusWithRegion' AS PhoneGroup	,
                '3' AS PhoneGroupID					,
                c.Name  AS RegionCaption
            FROM
                Provider p
                JOIN ProviderPhone ph ON 
                    p.ProviderID = ph.ProviderID AND 
                    ph.EliteLevelID IS NULL
                LEFT JOIN Country c ON 
                    c.CountryID = ph.CountryID
            WHERE
                " . \implode("\n AND \n ", $whereConditions) . " and # without status with country
                ph.Valid = 1
        " . ')';
    }

    protected function getQueryWithoutStatusWithoutCountry(array $whereConditions)
    {
        return '(' . "
            SELECT
                ? as CacheKey                       ,
                p.ProviderID						,
                NULL AS AccountID                   ,
                ph.*	     						,
                CASE ph.PhoneFor
                    WHEN '1' THEN 'General'
                    WHEN '2' THEN 'Reservations'
                    WHEN '3' THEN 'Customer support'
                    WHEN '4' THEN 'Member Services'
                    WHEN '5' THEN 'Award Travel'
                    END AS Name,
                'WithoutStatusWithoutRegion' AS PhoneGroup	,
                '4' AS PhoneGroupID					,
                c.Name  AS RegionCaption
            FROM
                Provider p
                JOIN ProviderPhone ph ON 
                    p.ProviderID = ph.ProviderID AND 
                    ph.EliteLevelID IS NULL
                LEFT JOIN Country c ON 
                    c.CountryID = ph.CountryID
            WHERE
                " . \implode("\n AND \n ", $whereConditions) . " and # without status without country
                ph.Valid = 1
        " . ')';
    }
}
