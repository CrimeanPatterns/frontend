<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Value;

use AwardWallet\MainBundle\Globals\JsonSerialize\FilterNull;

class AirportLocation extends Location implements \JsonSerializable
{
    use FilterNull;

    /**
     * @var int
     */
    public $segmentId;

    /**
     * @var string
     */
    public $stage;

    /**
     * @var int
     */
    public $lounges;

    /**
     * @var array
     */
    public $listOfLounges;
}
