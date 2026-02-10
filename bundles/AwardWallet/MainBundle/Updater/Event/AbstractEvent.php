<?php

namespace AwardWallet\MainBundle\Updater\Event;

abstract class AbstractEvent
{
    public $accountId;

    public $type;

    public function __construct($accountId, $type)
    {
        $this->accountId = $accountId;
        $this->type = $type;
    }
}
