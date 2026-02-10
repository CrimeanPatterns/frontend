<?php

namespace AwardWallet\MainBundle\Updater\Event;

/**
 * Class ExtensionEvent
 * check in client.
 */
class ExtensionEvent extends AbstractEvent
{
    public $expectedDuration;
    public $providerCode;
    public $checkIts;

    public function __construct($accountId, $expectedDuration, $providerCode, $checkIts)
    {
        parent::__construct($accountId, 'extension');
        $this->expectedDuration = $expectedDuration;
        $this->providerCode = $providerCode;
        $this->checkIts = $checkIts;
    }
}
