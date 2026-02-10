<?php

namespace AwardWallet\MainBundle\Updater\Event;

class LimitEvent extends AbstractAccountEvent
{
    public const ERROR_CODE_LOCKOUT = -3;

    public $message;
    public $code = self::ERROR_CODE_LOCKOUT;

    public function __construct($accountId, $message)
    {
        parent::__construct($accountId, FailEvent::TYPE);
        $this->message = $message;
    }
}
