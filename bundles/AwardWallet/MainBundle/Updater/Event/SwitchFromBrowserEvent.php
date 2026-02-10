<?php

namespace AwardWallet\MainBundle\Updater\Event;

class SwitchFromBrowserEvent extends AbstractEvent
{
    public function __construct()
    {
        parent::__construct(null, 'switch_from_browser');
    }
}
