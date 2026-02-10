<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\ListView;

use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\LocalizedDateParts;

class TripChainView
{
    /**
     * @var string
     */
    public $kind = 'tripChain';

    /**
     * @var string
     */
    public $dep;

    /**
     * @var string
     */
    public $arr;

    /**
     * @var array|LocalizedDateParts
     */
    public $arrDate;
    public ?string $duration;

    /**
     * TripChainView constructor.
     *
     * @param string $dep
     * @param string $arr
     * @param array $arrDate
     */
    public function __construct($dep, $arr, $arrDate, ?string $duration)
    {
        $this->dep = $dep;
        $this->arr = $arr;
        $this->arrDate = $arrDate;
        $this->duration = $duration;
    }
}
