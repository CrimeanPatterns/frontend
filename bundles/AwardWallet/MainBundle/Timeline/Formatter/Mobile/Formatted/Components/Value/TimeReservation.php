<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Value;

use AwardWallet\MainBundle\Globals\JsonSerialize\FilterNull;

class TimeReservation extends Time implements \JsonSerializable
{
    use FilterNull;

    /**
     * @var int
     */
    public $nights;

    public function __construct($date, $nights)
    {
        parent::__construct($date);

        $this->nights = $nights;
    }
}
