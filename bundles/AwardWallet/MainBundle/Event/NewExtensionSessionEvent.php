<?php

namespace AwardWallet\MainBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class NewExtensionSessionEvent extends Event
{
    public const NAME = 'aw.new_extension_session';
    private string $browserExtensionSessionId;
    private string $browserExtensionConnectionToken;

    public function __construct(string $browserExtensionSessionId, string $browserExtensionConnectionToken)
    {
        $this->browserExtensionSessionId = $browserExtensionSessionId;
        $this->browserExtensionConnectionToken = $browserExtensionConnectionToken;
    }

    public function getBrowserExtensionSessionId(): string
    {
        return $this->browserExtensionSessionId;
    }

    public function getBrowserExtensionConnectionToken(): string
    {
        return $this->browserExtensionConnectionToken;
    }
}
