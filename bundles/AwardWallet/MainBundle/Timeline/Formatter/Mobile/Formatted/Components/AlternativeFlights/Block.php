<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\AlternativeFlights;

use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Block as CommonBlock;

class Block extends CommonBlock
{
    public const KIND_CHOICE = 'choice';
    public const KIND_LAYOVER = 'layover';
    public const KIND_DATES = 'dates';
}
