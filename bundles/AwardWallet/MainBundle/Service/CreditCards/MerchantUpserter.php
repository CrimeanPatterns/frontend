<?php

namespace AwardWallet\MainBundle\Service\CreditCards;

use AwardWallet\MainBundle\Command\CreditCards\CompleteTransactionsCommand\AccountHistoryRow;
use AwardWallet\MainBundle\Service\CreditCards\MerchantMatcher\MerchantMatcher;
use AwardWallet\MainBundle\Service\CreditCards\Query\ExistingMerchantsQuery;
use AwardWallet\MainBundle\Service\CreditCards\Query\UpsertNewMerchantsQuery;
use Clock\ClockInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\DBAL\Exception\LockWaitTimeoutException;
use Psr\Log\LoggerInterface;

use function Duration\milliseconds;

class MerchantUpserter
{
    private ExistingMerchantsQuery $existingMerchantsQuery;
    private UpsertNewMerchantsQuery $upsertNewMerchantsQuery;
    private LoggerInterface $logger;
    private ClockInterface $clock;
    private Connection $sphinxConnection;

    public function __construct(
        ExistingMerchantsQuery $existingMerchantsQuery,
        UpsertNewMerchantsQuery $upsertNewMerchantsQuery,
        LoggerInterface $logger,
        ClockInterface $clock,
        Connection $sphinxConnection
    ) {
        $this->existingMerchantsQuery = $existingMerchantsQuery;
        $this->upsertNewMerchantsQuery = $upsertNewMerchantsQuery;
        $this->logger = $logger;
        $this->clock = $clock;
        $this->sphinxConnection = $sphinxConnection;
    }

    /**
     * @param array<string, list<AccountHistoryRow>> $upsertMerchantsByCacheKeyMap
     * @return array cache updates
     */
    public function upsert(array $upsertMerchantsByCacheKeyMap): array
    {
        $this->logger->info('upserting ' . \count($upsertMerchantsByCacheKeyMap) . ' merchants...');
        $selectCriteria = [];

        foreach ($upsertMerchantsByCacheKeyMap as $historyRowsGroup) {
            $historyRow = $historyRowsGroup[0];
            $merchant = $historyRow->CalculatedMerchantData->merchantId;
            $selectCriteria[] = [$merchant->name, $merchant->shoppingCategoryGroupID ?? 0];
        }

        $cacheUpdates = [];
        $existingMerchantsData = $this->existingMerchantsQuery->execute($selectCriteria);

        foreach ($existingMerchantsData as [$name, $merchantId, $notNullGroupID]) {
            $cacheKey = MerchantMatcher::createCacheKey($name, $notNullGroupID);

            /** @var AccountHistoryRow $historyRow */
            foreach ($upsertMerchantsByCacheKeyMap[$cacheKey] as $historyRow) {
                $calculated = $historyRow->CalculatedMerchantData;
                $cacheKey = $calculated->merchantId->cacheKey;

                if (!isset($cacheUpdates[$cacheKey])) {
                    $cacheUpdates[$cacheKey] = (int) $merchantId;
                }

                $calculated->merchantId = (int) $merchantId;
            }
        }

        $selectCriteria = [];
        $params = [];

        /** @var AccountHistoryRow[] $historyRowsGroup */
        foreach ($upsertMerchantsByCacheKeyMap as $historyRowsGroup) {
            $historyRow = $historyRowsGroup[0];
            $merchant = $historyRow->CalculatedMerchantData->merchantId;

            if (\is_int($merchant)) {
                // merchant was loaded before
                continue;
            }

            $selectCriteria[] = [$merchant->name, $merchant->shoppingCategoryGroupID ?? 0];
            $params[] = $merchant->name;
            $params[] = $merchant->displayName;
            $params[] = $merchant->shoppingCategoryGroupID;
            $params[] = $merchant->merchantPatternId;
        }

        if (!$selectCriteria) {
            return $cacheUpdates;
        }

        $maxAttempts = 50;

        foreach (\range(1, $maxAttempts) as $attempt) {
            try {
                $this->upsertNewMerchantsQuery->execute($params);
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

        $existingMerchantsData = $this->existingMerchantsQuery->execute($selectCriteria);

        $sphinxParams = [];

        foreach ($existingMerchantsData as [$name, $merchantId, $notNullGroupID]) {
            $cacheKey = MerchantMatcher::createCacheKey($name, $notNullGroupID);

            /** @var AccountHistoryRow $historyRow */
            foreach ($upsertMerchantsByCacheKeyMap[$cacheKey] as $historyRow) {
                $calculated = $historyRow->CalculatedMerchantData;

                if (\is_int($calculated->merchantId)) {
                    continue;
                }

                $cacheKey = $calculated->merchantId->cacheKey;

                if (!isset($cacheUpdates[$cacheKey])) {
                    $cacheUpdates[$cacheKey] = (int) $merchantId;
                }

                $sphinxParams[] = $merchantId;
                $sphinxParams[] = $calculated->merchantId->displayName;
                $calculated->merchantId = (int) $merchantId;
            }
        }

        if (count($sphinxParams) > 0) {
            $this->sphinxConnection->executeStatement("replace into Merchant(id, DisplayName) values "
            . join(", ", array_fill(0, count($sphinxParams) / 2, "(?, ?)")), $sphinxParams);
        }

        return $cacheUpdates;
    }
}
