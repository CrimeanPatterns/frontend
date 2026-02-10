<?php

namespace AwardWallet\MainBundle\Service\MileValue;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class MileValueCost
{
    private ?float $primaryValue;
    private ?float $minValue;
    private ?float $maxValue;
    private ?bool $isCashBackOnly;
    private ?int $cashBackType;
    private ?int $cobrandProviderId;

    public function __construct(
        ?float $primaryValue,
        ?float $minValue = null,
        ?float $maxValue = null,
        ?bool $isCashBackOnly = null,
        ?int $cobrandProviderId = null,
        ?int $cashBackType = null
    ) {
        $this->primaryValue = $primaryValue;
        $this->minValue = $minValue;
        $this->maxValue = $maxValue;
        $this->isCashBackOnly = $isCashBackOnly;
        $this->cobrandProviderId = $cobrandProviderId;
        $this->cashBackType = $cashBackType;
    }

    /**
     * Can be user value, manually set or auto-calculated.
     */
    public function getPrimaryValue(): ?float
    {
        return $this->primaryValue;
    }

    /**
     * if transferable, MINimum possible value
     * MileValueService::extractRangeValuesForTransfers.
     */
    public function getMinValue(): ?float
    {
        return $this->minValue;
    }

    /**
     * if transferable, MAXimum possible value
     * MileValueService::extractRangeValuesForTransfers.
     */
    public function getMaxValue(): ?float
    {
        return $this->maxValue;
    }

    public function isCashBackOnly(): ?bool
    {
        return $this->isCashBackOnly;
    }

    public function getCashBackType(): ?int
    {
        return $this->cashBackType;
    }

    public function getCobrandProviderId(): ?int
    {
        return $this->cobrandProviderId;
    }
}
