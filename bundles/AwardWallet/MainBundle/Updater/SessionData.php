<?php

namespace AwardWallet\MainBundle\Updater;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use Duration\Duration;

/**
 * @NoDI()
 */
class SessionData
{
    private string $sessionType;
    private int $eventIndex;
    private Duration $lastTickTime;
    private int $userId;
    private string $serializedRequest;
    private int $totalTickCount;
    private ?int $impersonatorUserId;

    public function __construct(
        string $sessionType,
        int $eventIndex,
        Duration $lastTickTime,
        int $totalTickCount,
        int $userId,
        string $serializedRequest,
        ?int $impersonatorUserId = null
    ) {
        $this->sessionType = $sessionType;
        $this->eventIndex = $eventIndex;
        $this->lastTickTime = $lastTickTime;
        $this->userId = $userId;
        $this->serializedRequest = $serializedRequest;
        $this->totalTickCount = $totalTickCount;
        $this->impersonatorUserId = $impersonatorUserId;
    }

    public function __serialize(): array
    {
        return [
            'sessionType' => $this->sessionType,
            'eventIndex' => $this->eventIndex,
            'lastTickTime' => $this->lastTickTime,
            'userId' => $this->userId,
            'serializedRequest' => $this->serializedRequest,
            'totalTickCount' => $this->totalTickCount,
            'impersonatorUserId' => $this->impersonatorUserId,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->sessionType = $data['sessionType'];
        $this->eventIndex = $data['eventIndex'];
        $this->lastTickTime = $data['lastTickTime'];
        $this->userId = $data['userId'];
        $this->serializedRequest = $data['serializedRequest'];
        $this->totalTickCount = $data['totalTickCount'];
        $this->impersonatorUserId = $data['impersonatorUserId'] ?? null;
    }

    public function getSessionType(): string
    {
        return $this->sessionType;
    }

    public function getEventIndex(): int
    {
        return $this->eventIndex;
    }

    public function getLastTickTime(): Duration
    {
        return $this->lastTickTime;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getSerializedRequest(): string
    {
        return $this->serializedRequest;
    }

    public function getTotalTickCount(): int
    {
        return $this->totalTickCount;
    }

    public function getImpersonatorUserId(): ?int
    {
        return $this->impersonatorUserId;
    }
}
