<?php

namespace AwardWallet\MainBundle\Service\Notification;

use Symfony\Contracts\EventDispatcher\Event;

class PushEvent extends Event
{
    private ?int $userId;
    private string $message;

    public function __construct(?int $userId, string $message)
    {
        $this->userId = $userId;
        $this->message = $message;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
