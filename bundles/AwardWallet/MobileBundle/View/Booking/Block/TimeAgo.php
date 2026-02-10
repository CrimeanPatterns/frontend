<?php

namespace AwardWallet\MobileBundle\View\Booking\Block;

use AwardWallet\MobileBundle\View\Date;

class TimeAgo extends Field
{
    /** @var Date */
    public $value;

    /**
     * @param Date $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }
}
