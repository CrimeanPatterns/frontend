<?php

namespace AwardWallet\MainBundle\Service\AccountHistory;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class TransactionQuery extends AbstractAccountHistoryQuery
{
    /** @var int[] */
    protected $subAccountIds = [];
    /** @var bool */
    private $withEarningPotential = true;
    /** @var \DateTime */
    private $startDate;
    /** @var \DateTime */
    private $endDate;
    /** @var array */
    private $categories;
    /** @var TransactionQueryCondition */
    private $amountCondition;
    /** @var array */
    private $pointsMultiplier;
    /** @var array */
    private $earningPotentialMultiplier;

    public function __construct(
        array $subAccountIds,
        ?string $descriptionFilter = null,
        ?NextPageToken $nextPageToken = null
    ) {
        $this->subAccountIds = $subAccountIds;
        $this->descriptionFilter = $descriptionFilter;
        $this->nextPageToken = $nextPageToken;
    }

    public function getSubAccountIds(): ?array
    {
        return $this->subAccountIds;
    }

    public function withEarningPotential(): ?bool
    {
        return $this->withEarningPotential;
    }

    public function setWithEarningPotential(bool $withEarningPotential): self
    {
        $this->withEarningPotential = $withEarningPotential;

        return $this;
    }

    public function setRangeLimits(\DateTime $startDate, \DateTime $endDate): self
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;

        return $this;
    }

    public function getStartDate(): ?\DateTime
    {
        return $this->startDate;
    }

    public function getEndDate(): ?\DateTime
    {
        return $this->endDate;
    }

    public function getCategories(): ?array
    {
        return $this->categories;
    }

    public function setCategories(?array $categories)
    {
        $this->categories = $categories;

        return $this;
    }

    public function getAmountCondition(): ?TransactionQueryCondition
    {
        return $this->amountCondition;
    }

    public function setAmountCondition(TransactionQueryCondition $amountCondition)
    {
        $this->amountCondition = $amountCondition;

        return $this;
    }

    public function getPointsMultiplier(): ?array
    {
        return $this->pointsMultiplier;
    }

    public function setPointsMultiplier(array $pointsMultiplier)
    {
        $this->pointsMultiplier = $pointsMultiplier;

        return $this;
    }

    public function getEarningPotentialMultiplier(): ?array
    {
        return $this->earningPotentialMultiplier;
    }

    public function setEarningPotentialMultiplier(array $earningPotentialMultiplier)
    {
        $this->earningPotentialMultiplier = $earningPotentialMultiplier;

        return $this;
    }
}
