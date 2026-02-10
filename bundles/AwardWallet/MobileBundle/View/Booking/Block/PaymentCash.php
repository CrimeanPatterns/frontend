<?php

namespace AwardWallet\MobileBundle\View\Booking\Block;

use AwardWallet\MobileBundle\View\AbstractBlock;

class PaymentCash extends AbstractBlock
{
    /** @var string */
    public $value;

    public function __construct($value)
    {
        parent::__construct();
        $this->setValue($value);
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }
}
