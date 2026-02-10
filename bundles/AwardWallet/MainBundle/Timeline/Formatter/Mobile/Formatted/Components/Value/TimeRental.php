<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Value;

use AwardWallet\MainBundle\Globals\JsonSerialize\FilterNull;

class TimeRental extends Time implements \JsonSerializable
{
    use FilterNull;

    /**
     * @var int
     */
    public $days;

    public function __construct($date, ?int $days = null)
    {
        parent::__construct($date);

        $this->days = $days;
    }
}
