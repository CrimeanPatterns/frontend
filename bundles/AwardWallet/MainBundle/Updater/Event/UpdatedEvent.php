<?php

namespace AwardWallet\MainBundle\Updater\Event;

class UpdatedEvent extends AbstractAccountEvent
{
    public $balance;

    public function __construct($accountId, $balance)
    {
        parent::__construct($accountId, 'updated');
        $this->balance = $balance;
    }
}
