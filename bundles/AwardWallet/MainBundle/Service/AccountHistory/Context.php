<?php

namespace AwardWallet\MainBundle\Service\AccountHistory;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Entity\CreditCard;
use AwardWallet\MainBundle\Entity\CreditCardShoppingCategoryGroup;
use AwardWallet\MainBundle\Globals\Utils\LazyVal;

/**
 * @NoDI()
 */
class Context
{
    private LazyVal $mileValueCache;
    private LazyVal $allTransactionMultipliers;
    private LazyVal $creditCardsMap;
    private LazyVal $mileValueAvailableProvidersMap;
    private LazyVal $mileValueCacheMapByProvider;
    private LazyVal $currentMerchantReportVersion;
    private ?LazyVal $merchantReportExpectedMultiplier;
    private ?LazyVal $cards;

    public function __construct(
        LazyVal $mileValueCache,
        LazyVal $mileValueCacheMapByProvider,
        LazyVal $mileValueAvailableProvidersMap,
        LazyVal $allTransactionMultipliers,
        LazyVal $creditCardsMap,
        LazyVal $currentMerchantReportVersion,
        ?LazyVal $merchantReportExpectedMultiplier,
        LazyVal $cards
    ) {
        $this->mileValueCache = $mileValueCache;
        $this->allTransactionMultipliers = $allTransactionMultipliers;
        $this->creditCardsMap = $creditCardsMap;
        $this->mileValueAvailableProvidersMap = $mileValueAvailableProvidersMap;
        $this->mileValueCacheMapByProvider = $mileValueCacheMapByProvider;
        $this->currentMerchantReportVersion = $currentMerchantReportVersion;
        $this->merchantReportExpectedMultiplier = $merchantReportExpectedMultiplier;
        $this->cards = $cards;
    }

    /**
     * @return LazyVal<array>
     */
    public function getMileValueCacheMapByProvider(): LazyVal
    {
        return $this->mileValueCacheMapByProvider;
    }

    /**
     * @return LazyVal<CreditCardShoppingCategoryGroup[]>
     */
    public function getAllTransactionMultipliers(): LazyVal
    {
        return $this->allTransactionMultipliers;
    }

    /**
     * @return LazyVal<array>
     */
    public function getMileValueCache(): LazyVal
    {
        return $this->mileValueCache;
    }

    /**
     * @return LazyVal<CreditCard[]>
     */
    public function getCreditCardsMap(): LazyVal
    {
        return $this->creditCardsMap;
    }

    /**
     * see AnalyzerContextFactory::makeCacheContext.
     *
     * @return LazyVal<array>
     */
    public function getMileValueAvailableProvidersMap(): LazyVal
    {
        return $this->mileValueAvailableProvidersMap;
    }

    /**
     * @return LazyVal<int>
     */
    public function getCurrentMerchantReportVersion(): LazyVal
    {
        return $this->currentMerchantReportVersion;
    }

    /**
     * @return ?LazyVal<array>
     */
    public function getMerchantReportExpectedMultiplier(): ?LazyVal
    {
        return $this->merchantReportExpectedMultiplier;
    }

    /**
     * @param LazyVal<array> $lazy
     */
    public function setMerchantReportExpectedMultiplier(LazyVal $lazy): void
    {
        $this->merchantReportExpectedMultiplier = $lazy;
    }

    public function getAllCardsAssociative(): LazyVal
    {
        return $this->cards;
    }
}
