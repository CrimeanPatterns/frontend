<?php

namespace AwardWallet\MainBundle\Command\CreditCards\CompleteTransactionsCommand;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Service\AccountHistory\MultiplierService;
use AwardWallet\MainBundle\Service\CreditCards\MerchantMatcher\MerchantMatcher;
use AwardWallet\MainBundle\Service\CreditCards\MerchantUpserter;
use AwardWallet\MainBundle\Service\CreditCards\ShoppingCategoryMatcher;
use AwardWallet\MainBundle\Service\ProgressLogger;
use Psr\Log\LoggerInterface;

class HistoryRowsProcessor
{
    public const DUMP_STATUS_TIME = 30;
    public const PACKAGE_SIZE = 50;

    private MainQuery $mainQuery;
    private LoggerInterface $logger;
    private MerchantUpserter $merchantUpserter;
    private MerchantMatcher $merchantMatcher;
    private ShoppingCategoryMatcher $categoryMatcher;

    public function __construct(
        LoggerInterface $logger,
        MerchantUpserter $merchantUpserter,
        MerchantMatcher $merchantMatcher,
        ShoppingCategoryMatcher $categoryMatcher
    ) {
        $this->logger = $logger;
        $this->merchantUpserter = $merchantUpserter;
        $this->merchantMatcher = $merchantMatcher;
        $this->categoryMatcher = $categoryMatcher;
    }

    /**
     * @psalm-type ParsedCategoriesMap = array<string, bool>
     * @param iterable<AccountHistoryRow> $historyRows
     * @return \Generator<never, int, AccountHistoryRow, array{categoriesMap: ParsedCategoriesMap, processedHistoryRowsCount: int, upsertedMerchantsCount: int, merchantMatcherStats: array}>
     */
    public function process(iterable $historyRows, bool $fetchOnly, bool $dryRun): \Generator
    {
        $processedHistoryRowsCount = 0;
        /** @var ParsedCategoriesMap $logParsedCategoriesMap */
        $logParsedCategoriesMap = [];
        /** @var array<string, list<AccountHistoryRow>> $upsertMerchantsByCacheKeyMap */
        $upsertMerchantsByCacheKeyMap = [];
        $upsertMerchantsHistoryRowsCount = 0;
        $upsertMerchantsCount = 0;
        $upsertedMerchantsCount = 0;

        $progressLogger = new ProgressLogger($this->logger, 500, self::DUMP_STATUS_TIME);
        $upsertedMerchantsGen = function () use ($dryRun, &$upsertMerchantsHistoryRowsCount, &$upsertMerchantsCount, &$upsertedMerchantsCount, &$upsertMerchantsByCacheKeyMap) {
            $cacheUpdates = $this->merchantUpserter->upsert($upsertMerchantsByCacheKeyMap);
            $upsertedMerchantsCount += \count($upsertMerchantsByCacheKeyMap);
            $this->merchantMatcher->updateCache($cacheUpdates);

            if (!$dryRun) {
                foreach ($upsertMerchantsByCacheKeyMap as $upsertMerchants) {
                    yield from $upsertMerchants;
                }
            }

            $upsertMerchantsByCacheKeyMap = [];
            $upsertMerchantsHistoryRowsCount = 0;
            $upsertMerchantsCount = 0;
        };

        /** @var AccountHistoryRow $historyRow */
        foreach ($historyRows as $historyRow) {
            $progressLogger->showProgress("reading AccountHistory", $processedHistoryRowsCount);
            $processedHistoryRowsCount++;

            $categoryId = null;
            $merchantData = null;
            $multiplier = null;

            if (!$fetchOnly) {
                $providerId = (int) $historyRow->ProviderID;
                $categoryId = !empty($historyRow->Category) ? $this->doIdentifyCategory($historyRow, $providerId) : null;
                $merchantData = !empty($historyRow->Description) ? $this->merchantMatcher->identify($historyRow->Description, $categoryId, true, false, true) : null;
                $multiplier = MultiplierService::calculate((float) $historyRow->Amount, (float) $historyRow->Miles, $providerId);
            }

            if (!isset($logParsedCategoriesMap[$historyRow->Category])) {
                $logParsedCategoriesMap[$historyRow->Category] = true;
            }

            $historyRow->CalculatedMerchantData = new CalculatedMerchantData(
                $categoryId,
                $merchantData,
                $multiplier
            );

            // merchant can be an instance of PostponedMerchantUpdate
            if (\is_object($merchantData)) {
                if (isset($upsertMerchantsByCacheKeyMap[$merchantData->cacheKey])) {
                    $upsertMerchantsByCacheKeyMap[$merchantData->cacheKey][] = $historyRow;
                } else {
                    $upsertMerchantsCount++;
                    $upsertMerchantsByCacheKeyMap[$merchantData->cacheKey] = [$historyRow];
                }

                $upsertMerchantsHistoryRowsCount++;
            } elseif (!$dryRun) {
                yield $historyRow;
            }

            if (($upsertMerchantsHistoryRowsCount === 10 * self::PACKAGE_SIZE)
                || ($upsertMerchantsCount === self::PACKAGE_SIZE)
            ) {
                yield from $upsertedMerchantsGen();
            }
        }

        if ($upsertMerchantsByCacheKeyMap) {
            yield from $upsertedMerchantsGen();
        }

        return [
            'categoriesMap' => $logParsedCategoriesMap,
            'processedHistoryRowsCount' => $processedHistoryRowsCount,
            'upsertedMerchantsCount' => $upsertedMerchantsCount,
            'merchantMatcherStats' => $this->merchantMatcher->getStats(),
        ];
    }

    protected function doIdentifyCategory(AccountHistoryRow $historyRow, int $providerId): ?int
    {
        // chase had broken categories parsing before 2022-07-04 12:33:52 UTC
        // fix https://github.com/AwardWallet/engine/commit/c7a3aa8b03d486fd43c513e86840c2659f052765
        // https://redmine.awardwallet.com/issues/21472
        if (
            (Provider::CHASE_ID === $providerId)
            && ($historyRow->UpdateDate <= '2022-07-04 12:33:52')
        ) {
            return null;
        }

        return $this->categoryMatcher->identify($historyRow->Category, $providerId);
    }
}
