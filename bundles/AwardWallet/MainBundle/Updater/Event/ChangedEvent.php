<?php

namespace AwardWallet\MainBundle\Updater\Event;

class ChangedEvent extends AbstractAccountEvent
{
    public $balance;

    public $change;

    public $increased;

    public function __construct($accountId, $balance, $change, $increased)
    {
        parent::__construct($accountId, 'changed');
        $this->balance = $balance;
        $this->change = $change;
        $this->increased = $increased;
    }
}
