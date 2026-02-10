<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Value;

use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Date;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\LocalizedDate;

class Time
{
    /**
     * @var Date|LocalizedDate
     */
    public $date;

    public function __construct($date)
    {
        $this->date = $date;
    }
}
