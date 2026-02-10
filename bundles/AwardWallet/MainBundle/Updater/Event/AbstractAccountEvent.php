<?php

namespace AwardWallet\MainBundle\Updater\Event;

abstract class AbstractAccountEvent extends AbstractEvent
{
    public $accountData;

    public function setAccountData($data)
    {
        $this->accountData = $data;
    }
}
