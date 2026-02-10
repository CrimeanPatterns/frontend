<?php

namespace AwardWallet\MainBundle\Service\HotelPointValue;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class DeviationCalculatorResult
{
    private ?float $deviation;
    private ?float $average;
    private int $basedOnRecords;

    public function __construct(
        ?float $deviation,
        ?float $average,
        int $basedOnRecords
    ) {
        $this->deviation = $deviation;
        $this->average = $average;
        $this->basedOnRecords = $basedOnRecords;
    }

    public function getDeviation(): ?float
    {
        return $this->deviation;
    }

    public function getAverage(): ?float
    {
        return $this->average;
    }

    public function getBasedOnRecords(): int
    {
        return $this->basedOnRecords;
    }
}
