<?php

namespace AwardWallet\MainBundle\Service\AccountHistory;

abstract class AbstractAccountHistoryQuery
{
    protected $limit = 100;
    /** @var int[]|null */
    protected $offerCards; // CreditCardIDs to offer
    /** @var NextPageToken */
    protected $nextPageToken;
    /** @var string */
    protected $descriptionFilter;

    public function setOfferCards(?array $offerCards): self
    {
        $this->offerCards = $offerCards;

        return $this;
    }

    public function getOfferCards(): ?array
    {
        return $this->offerCards;
    }

    public function getDescriptionFilter(): ?string
    {
        return $this->descriptionFilter;
    }

    public function getNextPageToken(): ?NextPageToken
    {
        return $this->nextPageToken;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function setLimit(?int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }
}
