<?php

namespace AwardWallet\MobileBundle\View\Booking\Block;

use AwardWallet\MobileBundle\View\AbstractBlock;

class Toggle extends AbstractBlock
{
    public $phone = [];
    public $tablet = [];

    public function addToPhone(AbstractBlock $block)
    {
        $this->phone[] = $block;
    }

    public function addToTablet(AbstractBlock $block)
    {
        $this->tablet[] = $block;
    }
}
