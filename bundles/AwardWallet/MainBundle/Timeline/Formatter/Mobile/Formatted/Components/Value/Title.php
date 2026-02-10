<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Value;

class Title
{
    /**
     * @var string
     */
    public $number;

    /**
     * TripTitle constructor.
     *
     * @param string $icon
     * @param string $number
     */
    public function __construct($icon, $number = null)
    {
        $this->number = $number;
    }
}
