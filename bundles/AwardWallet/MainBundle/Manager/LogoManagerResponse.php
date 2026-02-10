<?php

namespace AwardWallet\MainBundle\Manager;

class LogoManagerResponse
{
    public $image;
    public $class;
    public $shortName;

    public function __construct($image, $class = 'BOOK', $shortName = null)
    {
        $this->image = $image;
        $this->class = $class;
        $this->shortName = $shortName;
    }
}
