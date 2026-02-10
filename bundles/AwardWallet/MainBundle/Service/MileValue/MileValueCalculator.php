<?php

namespace AwardWallet\MainBundle\Service\MileValue;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class MileValueCalculator
{
    public static function calc(float $alternativeCost, float $spentTaxes, int $spentAwards): float
    {
        $value = ($alternativeCost - $spentTaxes) / $spentAwards * 100;

        return round($value, $value >= 0.0099 ? 2 : 4);
    }

    public static function calculateEarning(float $mileValue, float $miles): float
    {
        return round($mileValue * 0.01 * $miles, 2);
    }
}
