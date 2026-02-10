<?php

namespace AwardWallet\MainBundle\Updater\Event;

/**
 * Class ExtensionEvent
 * check in client.
 */
class ExtensionV3Event extends AbstractEvent
{
    public int $expectedDuration;
    public string $sessionId;
    public string $connectionToken;

    public function __construct(int $accountId, string $sessionId, string $connectionToken, int $expectedDuration)
    {
        parent::__construct($accountId, 'extension_v3');
        $this->expectedDuration = $expectedDuration;
        $this->sessionId = $sessionId;
        $this->connectionToken = $connectionToken;
    }
}
