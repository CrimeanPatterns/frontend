<?php

namespace AwardWallet\MainBundle\Service\AccountHistory;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\MileValue\MileValueCost;

/**
 * @NoDI()
 */
class HistoryRowValue
{
    private ?float $miles;
    private ?float $pointValue;
    private ?float $multiplier;
    private ?float $minValue;
    private ?float $maxValue;
    private ?MileValueCost $mileValueCost;
    private ?bool $isTransferable = false;

    public function __construct(
        ?float $miles,
        ?float $pointValue,
        ?float $multiplier,
        ?float $minMileValue,
        ?float $maxMileValue,
        MileValueCost $mileValueCost
    ) {
        $this->miles = $miles;
        $this->pointValue = $pointValue;
        $this->multiplier = $multiplier;
        $this->minValue = $minMileValue;
        $this->maxValue = $maxMileValue;
        $this->mileValueCost = $mileValueCost;

        if (!empty($minMileValue)) {
            $this->isTransferable = true;
        }
    }

    public function getMiles(): ?float
    {
        return $this->miles;
    }

    /**
     * already calculated value
     * $miles * $mileValue.
     */
    public function getPointValue(): ?float
    {
        return $this->pointValue;
    }

    public function getMultiplier(): ?float
    {
        return $this->multiplier;
    }

    /**
     * already calculated value
     * $miles * $minValue.
     */
    public function getMinValue(): ?float
    {
        return $this->minValue;
    }

    /**
     * already calculated value
     * $miles * $maxvalue.
     */
    public function getMaxValue(): ?float
    {
        return $this->maxValue;
    }

    public function getMileValueCost(): ?MileValueCost
    {
        return $this->mileValueCost;
    }

    public function isTransferable(): bool
    {
        return $this->isTransferable;
    }
}
