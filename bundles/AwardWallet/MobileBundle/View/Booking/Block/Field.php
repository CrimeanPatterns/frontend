<?php

namespace AwardWallet\MobileBundle\View\Booking\Block;

use AwardWallet\MobileBundle\View\AbstractBlock;

class Field extends AbstractBlock
{
    /** @var string */
    public $name;

    /** @var string */
    public $value;

    public function __construct($name, $value)
    {
        parent::__construct();
        $this->setName($name);
        $this->setValue($value);
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }
}
