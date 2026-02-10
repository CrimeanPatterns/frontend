<?php

namespace AwardWallet\MainBundle\Globals;

class NumberHandler
{
    public static function numberPrecision(float $number, int $decimals = 0): ?float
    {
        $negation = ($number < 0) ? (-1) : 1;
        $coefficient = 10 ** $decimals;

        return $negation * floor((string) (abs($number) * $coefficient)) / $coefficient;
    }
}
