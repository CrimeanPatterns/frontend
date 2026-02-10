<?php

namespace AwardWallet\MainBundle\Service\Notification;

class PushCollector
{
    private array $collected = [];

    public function onPush(PushEvent $event)
    {
        $this->collected[] = $event;
    }

    public function clear(): void
    {
        $this->collected = [];
    }

    /**
     * @return PushEvent[]
     */
    public function getCollected(): array
    {
        return $this->collected;
    }
}
