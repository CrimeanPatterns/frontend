<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Value;

class Location
{
    /**
     * @var string
     */
    public $code;

    /**
     * @var string
     */
    public $name;

    /**
     * Point constructor.
     *
     * @param string $code
     * @param string $name
     */
    public function __construct($name, $code = null)
    {
        $this->code = $code;
        $this->name = $name;
    }
}
