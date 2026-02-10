<?php

namespace AwardWallet\MainBundle\Service\FlightNotification;

use AwardWallet\MainBundle\Service\TaskScheduler\Task;

class FlightEmailAlertTask extends Task
{
    private int $tripSegmentId;

    private int $userId;

    private ?int $userAgentId;

    private ?int $providerId;

    private bool $copy;

    private string $kind;

    public function __construct(int $tripSegmentId, int $userId, ?int $userAgentId, ?int $providerId, bool $copy, string $kind)
    {
        parent::__construct(FlightEmailAlertConsumer::class);

        $this->tripSegmentId = $tripSegmentId;
        $this->userId = $userId;
        $this->userAgentId = $userAgentId;
        $this->providerId = $providerId;
        $this->copy = $copy;
        $this->kind = $kind;
    }

    public function getTripSegmentId(): int
    {
        return $this->tripSegmentId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getUserAgentId(): ?int
    {
        return $this->userAgentId;
    }

    public function getProviderId(): ?int
    {
        return $this->providerId;
    }

    public function isCopy(): bool
    {
        return $this->copy;
    }

    public function getKind(): string
    {
        return $this->kind;
    }
}
