<?php

namespace AwardWallet\MainBundle\Service\CreditCards\MerchantMatcher;

use AwardWallet\Common\MemoryCache\Cache;
use AwardWallet\MainBundle\Entity\ShoppingCategory;
use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use AwardWallet\MainBundle\Globals\Utils\BinaryLogger\BinaryLoggerFactory;
use AwardWallet\MainBundle\Service\CreditCards\MerchantDisplayNameGenerator;
use AwardWallet\MainBundle\Service\CreditCards\MerchantMatcher\MultiRegexBuilder\MultiRegexBuilder;
use AwardWallet\MainBundle\Service\CreditCards\MerchantMatcherStats;
use AwardWallet\MainBundle\Service\CreditCards\MerchantNameBlacklist;
use AwardWallet\MainBundle\Service\CreditCards\MerchantNameNormalizer;
use Clock\ClockNative;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\DBAL\Exception\LockWaitTimeoutException;
use Doctrine\DBAL\Statement;
use Psr\Log\LoggerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtAssoc;
use function Duration\milliseconds;

class MerchantMatcher
{
    public const PATTERNS_MEMCACHED_KEY = "credit_cards_merchant_patterns_v23";

    public const IGNORE_PATTERNS_GROUPING_INDEX = -1;
    public const VIRTUAL_MERCHANT_ID = PHP_INT_MIN;
    private const MERCHANT_NAME_FIELD_LENGTH = 250;
    private const MERCHANT_DISPLAY_NAME_FIELD_LENGTH = 250;
    private const COMMON_NAME_PARTS = 'mc|mac|mck|mr|mrs|miss|sir|dr|lady|lord|ms|von|van|ter|la|le|fitz|de|di|du|das';
    private const COMMON_NAME_PREFIX_PATTERN_BEGIN = '/^(' . self::COMMON_NAME_PARTS . ')(?=[a-zA-Z]{3})/i';
    private const COMMON_NAME_PREFIX_PATTERN_SURROUNDED = '/(?<=[a-zA-Z]{2})(' . self::COMMON_NAME_PARTS . ')(?=[a-zA-Z]{2})/i';

    /** @var LoggerInterface */
    private $logger;
    /** @var Connection */
    private $connection;
    /** @var array */
    private $cache = [];
    /** @var array */
    private $categoryToGroupMap = [];
    /** @var array */
    private $cachedTravelGroups = [];
    /** @var Statement */
    private $updateQuery;
    /** @var \Memcached */
    private $memcached;
    private MerchantNameNormalizer $nameNormalizer;
    private Cache $memoryCache;

    private MerchantNameBlacklist $merchantNameBlacklist;

    private MerchantMatcherStats $stats;
    private ClockNative $clock;
    private BinaryLoggerFactory $check;
    private Connection $sphinxConnection;
    private Statement $sphinxStatement;

    public function __construct(
        LoggerInterface $logger,
        Connection $connection,
        \Memcached $memcached,
        Cache $cache,
        MerchantNameNormalizer $nameNormalizer,
        MerchantNameBlacklist $merchantNameBlacklist,
        Connection $sphinxConnection
    ) {
        $this->logger = (new ContextAwareLoggerWrapper($logger))
            ->withClass(self::class)
            ->withTypedContext();
        $this->check = (new BinaryLoggerFactory($this->logger))->toInfo()->uppercaseInfix();
        $this->connection = $connection;
        $this->memcached = $memcached;
        $this->nameNormalizer = $nameNormalizer;
        $this->memoryCache = $cache;
        $this->merchantNameBlacklist = $merchantNameBlacklist;
        $this->stats = new MerchantMatcherStats();
        $this->clock = new ClockNative();
        $this->sphinxConnection = $sphinxConnection;
    }

    /**
     * @return PostponedMerchantUpdate|int|null
     */
    public function identify(
        ?string $name = null,
        ?int $categoryId = null,
        bool $useProcessCache = true,
        bool $debug = false,
        bool $postponeUpdates = false,
        bool $updateDbRows = true
    ) {
        if (empty(trim($name))) {
            return null;
        }

        $groupId = $this->detectCategoryGroupId($categoryId, $debug);
        $normalizedName = $this->nameNormalizer->normalize($name);

        if (empty(trim($normalizedName))) {
            return null;
        }

        if ($this->merchantNameBlacklist->isBlacklisted($normalizedName)) {
            if ($debug) {
                $this->logger->debug("merchant name blacklisted");
            }

            return null;
        }

        return $this->detect(
            $normalizedName,
            $name,
            $groupId,
            $useProcessCache,
            $postponeUpdates,
            $debug,
            $updateDbRows
        );
    }

    public function detectCategoryGroupId(?int $categoryId, bool $debug = false): ?int
    {
        $categoryIsIgnored = false;

        if (
            ($categoryIsEmpty = ($categoryId === null))
            || ($categoryIsIgnored = in_array($categoryId, ShoppingCategory::IGNORED_CATEGORIES))
        ) {
            if ($debug) {
                $this->check->that('category')->is('empty')->toDebug()
                    ->on($categoryIsEmpty);

                if (!$categoryIsEmpty) {
                    $this->check->that('category')->is('in ignored categories list (Entity\ShoppingCategory::IGNORED_CATEGORIES)')->toDebug()
                        ->on($categoryIsIgnored);
                }

                $this->logger->debug("no category and no group");
            }

            $groupId = null;
        } else {
            $groupId = $this->checkGroupByMap($categoryId);

            if ($debug) {
                $this->logger->debug("have category $categoryId, group: " . $this->groupName($groupId, $debug));
            }
        }

        return $groupId;
    }

    public function clearCache(): void
    {
        $this->memcached->delete(self::PATTERNS_MEMCACHED_KEY);
        $this->memoryCache->clear();
    }

    public function getStats(): MerchantMatcherStats
    {
        return $this->stats;
    }

    public function updateCache(array $updates): void
    {
        foreach ($updates as $cacheKey => $value) {
            $this->cache[$cacheKey] = $value;
        }
    }

    public static function createCacheKey(string $name, ?int $scGroupId): string
    {
        return \mb_strtolower($name) . '_' . ($scGroupId ?? 0);
    }

    public static function enhancePattern(string $template): string
    {
        // add optional apostrophe: wegmans => wegman'?s
        $template = \preg_replace('/([a-zA-Z]{3})(s)/i', '$1(?:\\s+)?$2', $template);
        // add optional apostrophes for n: park'n'fly => park n fly => park'n'fly
        $template = \preg_replace('/(?<=[a-zA-Z]{2})(n)(?=[a-zA-Z]{2})/i', '(?:\\s+)?$1(?:\\s+)?', $template);
        // mcdondalds -> mc(?:\s+)?donalds
        $template = \preg_replace(self::COMMON_NAME_PREFIX_PATTERN_BEGIN, '$1(?:\\s+)?', $template);
        $template = \preg_replace(self::COMMON_NAME_PREFIX_PATTERN_SURROUNDED, '(?:\\s+)?$1(?:\\s+)?', $template);

        return $template;
    }

    public static function shrinkPattern(string $template): string
    {
        if ('#' === $template[0] && '#' === $template[\strlen($template) - 1]) {
            $template = \substr($template, 1, -1);
        }

        if ('^' === $template[0]) {
            $template = \substr($template, 1);
        }

        return $template;
    }

    public function processPatterns(
        array $merchantPatterns,
        array $positivePatterns,
        array $negativePatterns,
        string $normalizedName,
        bool $debug = false
    ): ?array {
        return $this->doProcessPatterns(
            $merchantPatterns,
            $positivePatterns,
            $negativePatterns,
            $normalizedName,
            $debug,
            true
        );
    }

    public static function createTemplateFromMetadata(array $patternMetadata): string
    {
        if ($patternMetadata['isPreg']) {
            $template = $patternMetadata['template'];
        } else {
            $template = \preg_quote($patternMetadata['template'], '#');

            if ($patternMetadata['beginSymbol']) {
                $template = "^{$template}";
            }

            $template = "#{$template}#";
        }

        return $template;
    }

    /**
     * @return array{0: list<string>, 1: list<string>} tuple of positive (first) and negative (second) patterns lists
     */
    public static function fillBuilders(array $loadedPatterns): array
    {
        $positiveBuilder = new MultiRegexBuilder();
        $negativeBuilder = new MultiRegexBuilder();

        foreach ($loadedPatterns as $merchantIdx => $merchantPattern) {
            foreach ($merchantPattern['Patterns'] as $patternIdx => $pattern) {
                $key = "{$merchantIdx}_{$patternIdx}";

                $template = self::createTemplateFromMetadata($pattern);
                $shrunkTemplate = $template;
                $template = self::enhancePattern($template);

                if ($pattern['isPositive']) {
                    $positiveBuilder->addPattern(
                        $template,
                        self::shrinkPattern($shrunkTemplate),
                        '#',
                        $key,
                        $merchantPattern,
                    );
                } else {
                    $negativeBuilder->addPattern(
                        $template,
                        self::shrinkPattern($shrunkTemplate),
                        '#',
                        $key,
                        $merchantPattern,
                    );
                }
            }
        }

        return [
            $positiveBuilder->buildMegaPatterns(20_000, 3),
            $negativeBuilder->buildMegaPatterns(20_000, 3),
        ];
    }

    public function loadMerchantPatterns(): array
    {
        $memcacheKey = self::PATTERNS_MEMCACHED_KEY;

        return $this->memoryCache->get($memcacheKey, 300, function () use ($memcacheKey) {
            $result = $this->memcached->get($memcacheKey);

            if (false !== $result) {
                return $result;
            }

            $loadedPatterns =
                it($this->loadPatterns())
                ->filter(fn (array $pattern) => !$pattern['HasErrPreg'])
                ->toArray();
            [$positiveBuilder, $negativeBuilder] = self::fillBuilders($loadedPatterns);
            $result = [
                $loadedPatterns,
                $positiveBuilder,
                $negativeBuilder,
            ];
            $this->memcached->set($memcacheKey, $result, 300);

            return $result;
        });
    }

    private function doProcessPatterns(
        array $merchantPatterns,
        array $positivePatterns,
        array $negativePatterns,
        string $normalizedName,
        bool $debug = false,
        bool $usePregMatchAll = false,
        ?array $negativeMap = null
    ) {
        foreach ($positivePatterns as $idx => $positivePattern) {
            $this->stats->patternSearches++;
            $res = $usePregMatchAll ?
                \preg_match_all($positivePattern, $normalizedName, $positiveMatches) :
                \preg_match($positivePattern, $normalizedName, $positiveMatches);

            if (
                $res
                && isset($positiveMatches['MARK'])
            ) {
                $marks = $usePregMatchAll ?
                    $positiveMatches['MARK'] :
                    [$positiveMatches['MARK']];

                foreach ($marks as $markIdx => $mark) {
                    [$merchantIndex, $patternIndex] = \explode('_', $mark);
                    $marks[$markIdx] = [$merchantPatterns[$merchantIndex], $merchantIndex, $patternIndex];
                }

                if (\count($marks) > 1) {
                    \usort(
                        $marks,
                        static fn (array $a, array $b) => $a[0]['DetectPriority'] <=> $b[0]['DetectPriority']
                    );
                }

                foreach ($marks as [$merchantPattern, $merchantIndex, $patternIndex]) {
                    if ($merchantPattern['HasNegativePatterns']) {
                        if (!isset($negativeMap)) {
                            $negativeMap = $this->getNegativeMatchesMap($negativePatterns, $normalizedName);
                        }

                        if (isset($negativeMap[$merchantIndex])) {
                            if ($usePregMatchAll) {
                                // try next mark
                                continue;
                            }

                            return $this->doProcessPatterns(
                                $merchantPatterns,
                                \array_slice($positivePatterns, $idx),
                                $negativePatterns,
                                $normalizedName,
                                $debug,
                                true,
                                $negativeMap
                            );
                        }
                    }

                    $pattern = $merchantPattern['Patterns'][$patternIndex];
                    $result = [(int) $merchantPattern['MerchantPatternID'], $merchantPattern['Name']];

                    if ($debug) {
                        $this->logger->debug("matched by pattern {$pattern['template']} to merchant pattern {$result[0]}");
                    }

                    return $result;
                }
            }
        }

        return null;
    }

    /**
     * @param list<string> $negativePatterns
     * @return array<int, bool>
     */
    private function getNegativeMatchesMap(array $negativePatterns, string $normalizedName): array
    {
        $map = [];

        foreach ($negativePatterns as $negativePattern) {
            $this->stats->patternSearches++;

            if (
                \preg_match_all($negativePattern, $normalizedName, $negativeMatches)
                && isset($negativeMatches['MARK'])
            ) {
                foreach ($negativeMatches['MARK'] as $mark) {
                    [$merchantIndex, $patternIndex] = \explode('_', $mark);
                    $map[(int) $merchantIndex] = true;
                }
            }
        }

        return $map;
    }

    private function checkGroupByMap(?int $shoppingCategoryId = null): ?int
    {
        if (is_null($shoppingCategoryId)) {
            return null;
        }

        if (empty($this->categoryToGroupMap)) {
            $this->categoryToGroupMap = $this->connection
               ->executeQuery("
                   SELECT ShoppingCategoryID, ShoppingCategoryGroupID 
                   FROM ShoppingCategory 
                   WHERE ShoppingCategoryGroupID IS NOT NULL
               ")
               ->fetchAllKeyValue()
            ;
        }

        return $this->categoryToGroupMap[$shoppingCategoryId] ?? null;
    }

    /**
     * @return PostponedMerchantUpdate|int|null
     */
    private function detect(
        string $normalizedName,
        string $name,
        ?int $groupId,
        bool $useProcessCache,
        bool $postponeUpdates,
        bool $debug = false,
        bool $updateDbRows = true
    ) {
        $cacheKey = self::createCacheKey(\mb_substr($normalizedName, 0, self::MERCHANT_NAME_FIELD_LENGTH), $groupId);

        if (isset($this->cache[$cacheKey]) && $useProcessCache) {
            if ($debug) {
                $this->logger->debug("merchant found in cache for name {$normalizedName}: {$this->cache[$cacheKey]}");
            }

            $this->stats->cacheHits++;

            return $this->cache[$cacheKey];
        }

        [$availablePatterns, $positiveMegaPatterns, $negativeMegaPatterns] = $this->loadMerchantPatterns();

        if ($debug) {
            $this->logger->debug("testing against " . count($availablePatterns) . " merchant patterns glued together into " . \count($positiveMegaPatterns) . ' tree-like expression(s)');
        }

        $merchantPatternInfo = $this->processPatterns(
            $availablePatterns,
            $positiveMegaPatterns,
            $negativeMegaPatterns,
            $normalizedName,
            $debug
        );
        $displayName = null;
        $merchantPatternId = null;

        if ($merchantPatternInfo) {
            [$merchantPatternId, $displayName] = $merchantPatternInfo;
            $normalizedName = $displayName;
            $cacheKey = self::createCacheKey(\mb_substr($normalizedName, 0, self::MERCHANT_NAME_FIELD_LENGTH), $groupId);

            if ($debug) {
                $this->logger->debug("matched by pattern, will try to find/create merchant with name {$normalizedName}");
            }
        }

        if ($useProcessCache && isset($this->cache[$cacheKey])) {
            if ($debug) {
                $this->logger->debug("merchant found in cache for name {$normalizedName}: {$this->cache[$cacheKey]}");
            }

            return $this->cache[$cacheKey];
        }

        // проверить на полное совпадение имени
        $this->stats->nameSearches++;

        $displayName ??= MerchantDisplayNameGenerator::create($name);

        if ($postponeUpdates) {
            if ($debug) {
                $this->logger->debug("new Merchant will be created: {$normalizedName}, displayName: {$displayName}, groupId: {$groupId}, merchantPatternId: {$merchantPatternId}");
            }

            return new PostponedMerchantUpdate(
                $cacheKey,
                \mb_substr($normalizedName, 0, self::MERCHANT_NAME_FIELD_LENGTH),
                \mb_substr($displayName, 0, self::MERCHANT_DISPLAY_NAME_FIELD_LENGTH),
                $groupId,
                $merchantPatternId
            );
        } else {
            // this query will run only for insert, ignore here is only for concurrency issues
            // in case of existing merchant the Transactions field will be updated later by ReportBuilderCommand
            if ($this->updateQuery === null) {
                $this->updateQuery = $this->connection->prepare("
                    INSERT INTO Merchant (Name, DisplayName, ShoppingCategoryGroupID, Transactions, MerchantPatternID) 
                    VALUES (:Name, :DisplayName, :ShoppingCategoryGroupID, 1, :MerchantPatternID)
                    ON DUPLICATE KEY UPDATE MerchantPatternID = VALUES(MerchantPatternID)
                ");
            }

            $maxAttempts = 50;
            $this->connection->executeQuery('select last_insert_id(0)');
            $message = 'was created';
            $existingMerchantId = null;
            $existingMerchantSearched = false;

            if ($updateDbRows) {
                foreach (\range(1, $maxAttempts) as $attempt) {
                    try {
                        $this->updateQuery->executeStatement([
                            ":Name" => $normalizedName,
                            ":DisplayName" => $displayName,
                            ":ShoppingCategoryGroupID" => $groupId,
                            ":MerchantPatternID" => $merchantPatternId,
                        ]);
                        $lastException = null;

                        break;
                    } catch (DeadlockException|LockWaitTimeoutException $lastException) {
                        $sleepTime = milliseconds(\random_int(1, 100));
                        $this->logger->info(\get_class($lastException) . " on {$attempt} attempt, " . (($attempt < $maxAttempts) ? "retrying in {$sleepTime}..." : 'giving up!!!'));

                        if ($attempt < $maxAttempts) {
                            $this->clock->sleep($sleepTime);
                        }
                    }
                }

                if (isset($lastException)) {
                    throw $lastException;
                }

                $lastInsertId = (int) $this->connection->lastInsertId();

                if ($lastInsertId) {
                    $this->sphinxStatement ??= $this->sphinxConnection->prepare("replace into Merchant(id, DisplayName) values (:id, :DisplayName)");
                    $this->sphinxStatement->executeStatement(["id" => $lastInsertId, "DisplayName" => $displayName]);
                }
            } else {
                $existingMerchantId = $this->loadMerchantId($normalizedName, $groupId, $debug);
                $existingMerchantSearched = true;
                $lastInsertId = $existingMerchantId ? null : self::VIRTUAL_MERCHANT_ID;
            }

            if (!$lastInsertId) {
                $message = 'was updated';
                $lastInsertId = $existingMerchantSearched ?
                    $existingMerchantId :
                    ((int) $this->loadMerchantId($normalizedName, $groupId, $debug));
            }

            $this->cache[$cacheKey] = $lastInsertId;

            if ($debug) {
                $this->logger->debug("Merchant {$message}: " . $normalizedName . ", id: " . ($lastInsertId === self::VIRTUAL_MERCHANT_ID ? '(SHOULD BE INSERTED WHEN PROCESSED)' : $this->cache[$cacheKey]));
            }

            return $this->cache[$cacheKey];
        }
    }

    private function loadMerchantId(string $normalizedName, ?int $groupId, bool $debug): ?int
    {
        $id = $this->connection
            ->executeQuery('
                select MerchantID 
                from Merchant 
                where 
                    Name = ? 
                    and NotNullGroupID = ?',
                [$normalizedName, $groupId ?? 0],
            )
            ->fetchOne();

        if ($debug) {
            $this->logger->info("Searched for merchant with name: {$normalizedName}, group: {$groupId}, found id: " . ($id ?: 'null'));
        }

        return $id === false ? null : $id;
    }

    private function loadPatterns(): array
    {
        return
            stmtAssoc($this->connection->executeQuery("
                SELECT
                   mp.MerchantPatternID,
                   mp.Name,
                   mp.Patterns,
                   mp.DetectPriority
                FROM MerchantPattern mp"
            ))
            ->map(function (array $row) {
                $row["MerchantPatternID"] = (int) $row["MerchantPatternID"];
                $row['DetectPriority'] = (int) $row['DetectPriority'];
                $row['Patterns'] = RegexMetadataFactory::create($row["Patterns"]);
                $positiveCount =
                    it($row["Patterns"])
                    ->filter(fn (array $pattern) => $pattern['isPositive'])
                    ->count();
                $row['HasNegativePatterns'] = (\count($row["Patterns"]) - $positiveCount) > 0;
                $row['HasErrPreg'] =
                    it($row["Patterns"])
                    ->any(fn (array $pattern) => isset($pattern['pregError']));

                if (0 === $positiveCount) {
                    throw new \LogicException('empty positive patterns list for merchant pattern');
                }

                return $row;
            })
            ->toArray()
        ;
    }

    private function groupName(?int $groupId, bool $debug): string
    {
        if ($groupId === null) {
            return "<null>";
        }

        if (!$debug) {
            return $groupId;
        }

        return "$groupId " . $this->connection->executeQuery("select Name from ShoppingCategoryGroup where ShoppingCategoryGroupID = ?", [$groupId])->fetchOne();
    }

    /**
     * @param array $resultByGroup [$shoppingCategoryGroupId => [$merchantId => ["pattern1", "pattern2"], ...
     */
    private function addChildGroups(array $resultByGroup): array
    {
        it($this->connection->fetchAllAssociative("select ParentGroupID, ChildGroupID from ShoppingCategoryGroupChildren order by ParentGroupID"))
            ->reindexByColumn("ParentGroupID")
            ->map(fn (array $row) => $row['ChildGroupID'])
            ->collapseByKey()
            ->applyIndexed(function (array $childGroups, int $parentGroupId) use (&$resultByGroup) {
                // $childGroups = [$childGroupId1, $childGroupId2 ...]
                $childMerchants = array_intersect_key($resultByGroup, array_flip($childGroups));
                $childMerchants = array_replace(...$childMerchants);
                $resultByGroup[$parentGroupId] = array_replace($resultByGroup[$parentGroupId] ?? [], $childMerchants);
            });

        return $resultByGroup;
    }
}
