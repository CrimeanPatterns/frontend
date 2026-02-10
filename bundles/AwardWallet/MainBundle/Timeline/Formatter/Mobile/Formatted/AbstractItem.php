<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted;

use AwardWallet\MainBundle\Globals\JsonSerialize\FilterNull;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Date;

abstract class AbstractItem implements \JsonSerializable
{
    use FilterNull;
    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $id;

    /**
     * @var string
     */
    public $futureCountId;

    /**
     * @var bool
     */
    public $changed;

    /**
     * @var Date
     */
    public $startDate;

    /**
     * @var bool
     */
    public $breakAfter;
}
