<?php

namespace AwardWallet\MainBundle\Service\HotelPointValue;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class SpentAwardsParserResult
{
    /**
     * @var float
     */
    private $total;
    /**
     * @var string
     */
    private $currencySymbol;
    /**
     * @var int
     */
    private $points;

    public function __construct(float $total, string $currencySymbol, int $points)
    {
        $this->total = $total;
        $this->currencySymbol = $currencySymbol;
        $this->points = $points;
    }

    public function getTotal(): float
    {
        return $this->total;
    }

    public function getCurrencySymbol(): string
    {
        return $this->currencySymbol;
    }

    public function getPoints(): int
    {
        return $this->points;
    }
}
