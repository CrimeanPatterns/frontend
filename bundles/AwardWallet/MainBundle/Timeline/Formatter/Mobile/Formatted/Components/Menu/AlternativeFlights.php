<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Menu;

class AlternativeFlights
{
    public const MAIN_LIMIT = 2;
    /**
     * @var AlternativeFlight[]
     */
    public $main;

    /**
     * @var AlternativeFlight[]
     */
    public $extra;

    /**
     * @var array<string, key>
     */
    public $map;
}
