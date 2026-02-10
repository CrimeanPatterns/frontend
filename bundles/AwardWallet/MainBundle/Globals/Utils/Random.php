<?php

namespace AwardWallet\MainBundle\Globals\Utils;

class Random implements RandomInterface
{
    public function generateInt(int $min, int $max): int
    {
        return \random_int($min, $max);
    }
}
