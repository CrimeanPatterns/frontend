<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components;

class LocalizedDate
{
    /**
     * Unixtime.
     *
     * @var int
     */
    public $ts;

    /**
     * Old value.
     *
     * @var self
     */
    public $old;

    /**
     * Localized parts.
     *
     * @var LocalizedDateParts
     */
    public $fmt;

    /**
     * @var int[]
     */
    public $fmtParts;

    /**
     * Date in Y-m-d format.
     *
     * @var string
     */
    public $localYMD;
    /**
     * @var int
     */
    public $offset;
}
