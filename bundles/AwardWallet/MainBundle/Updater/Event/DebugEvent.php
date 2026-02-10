<?php

namespace AwardWallet\MainBundle\Updater\Event;

/**
 * Class DebugEvent.
 */
class DebugEvent extends AbstractEvent
{
    public $message;

    public function __construct($accountId, $message = '')
    {
        parent::__construct($accountId, 'debug');
        $this->message = $message;
    }
}
