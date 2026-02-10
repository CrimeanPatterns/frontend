<?php

namespace AwardWallet\MainBundle\Updater\Event;

/**
 * Class ErrorEvent
 * account errors.
 */
class ErrorEvent extends AbstractAccountEvent
{
    public $errorCode;

    public function __construct($accountId, $errorCode, ?string $errorMessage = null)
    {
        parent::__construct($accountId, 'error');
        $this->errorCode = $errorCode;
        empty($errorMessage) ?: $this->errorMessage = $errorMessage;
    }
}
