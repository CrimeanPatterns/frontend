<?php

namespace AwardWallet\MainBundle\Updater\Event;

class SwitchToBrowserEvent extends AbstractEvent
{
    public string $token;

    public function __construct(string $token)
    {
        parent::__construct(null, 'switch_to_browser');

        $this->token = $token;
    }
}
