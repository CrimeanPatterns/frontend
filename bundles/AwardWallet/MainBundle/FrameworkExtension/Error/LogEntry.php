<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Error;

class LogEntry
{
    private string $message;
    private array $context;
    private string $messageHash;

    public function __construct(string $message, array $context, string $messageHash)
    {
        $this->message = $message;
        $this->context = $context;
        $this->messageHash = $messageHash;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getMessageHash(): string
    {
        return $this->messageHash;
    }
}
