<?php

namespace AwardWallet\MainBundle\Service\TaskScheduler;

class Task implements TaskInterface
{
    protected string $serviceId;

    protected string $requestId;

    protected int $maxRetriesCount;

    protected int $currentRetriesCount;

    public function __construct(
        string $serviceId,
        ?string $requestId = null,
        int $maxRetriesCount = 1,
        int $currentRetriesCount = 0
    ) {
        $this->serviceId = $serviceId;

        if (is_null($requestId)) {
            $requestId = bin2hex(random_bytes(10));
        }

        $this->requestId = $requestId;
        $this->maxRetriesCount = $maxRetriesCount;
        $this->currentRetriesCount = $currentRetriesCount;
    }

    public function getServiceId(): string
    {
        return $this->serviceId;
    }

    public function getRequestId(): string
    {
        return $this->requestId;
    }

    public function getMaxRetriesCount(): int
    {
        return $this->maxRetriesCount;
    }

    public function getCurrentRetriesCount(): int
    {
        return $this->currentRetriesCount;
    }

    public function incrementRetriesCount(): void
    {
        $this->currentRetriesCount++;
    }
}
