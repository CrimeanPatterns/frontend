<?php

namespace AwardWallet\MainBundle\Service\AccountHistory;

class MerchantMobileRecommendation
{
    private ?OfferCreditCardItem $topHasUserCard;
    private ?OfferCreditCardItem $topRecommended;
    private ?bool $isTransactionsExists;

    public function __construct(
        ?OfferCreditCardItem $topHasUserCard,
        ?OfferCreditCardItem $topRecommended,
        ?bool $isTransactionsExists = null
    ) {
        $this->topHasUserCard = $topHasUserCard;
        $this->topRecommended = $topRecommended;
        $this->isTransactionsExists = $isTransactionsExists;
    }

    public function getTopHasUserCard(): ?OfferCreditCardItem
    {
        return $this->topHasUserCard;
    }

    public function getTopRecommended(): ?OfferCreditCardItem
    {
        return $this->topRecommended;
    }

    public function isTransactionsExists(): ?bool
    {
        return $this->isTransactionsExists;
    }
}
