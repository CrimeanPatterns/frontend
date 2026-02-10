<?php

namespace AwardWallet\MobileBundle\View\Booking\Block;

use AwardWallet\MobileBundle\View\AbstractBlock;

class Text extends AbstractBlock
{
    public $value;

    public function __construct($value)
    {
        parent::__construct();
        $this->setValue($value);
    }

    public function setValue($value)
    {
        $this->value = $value;
    }
}
