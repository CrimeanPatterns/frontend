<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\AlternativeFlights;

class Layover extends Block
{
    public function __construct(string $code, string $duration)
    {
        parent::__construct(self::KIND_LAYOVER, null, $code, $duration);
    }
}
