<?php

namespace AwardWallet\MainBundle\Globals\Utils;

interface RandomInterface
{
    public function generateInt(int $min, int $max): int;
}
