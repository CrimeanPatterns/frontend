<?php

namespace AwardWallet\MainBundle\Updater\Event;

/**
 * Class DisabledEvent
 * updater-side disabled account messages.
 */
class DisabledEvent extends AbstractAccountEvent
{
    public $message;

    public function __construct($accountId)
    {
        parent::__construct($accountId, 'disabled');
    }
}
