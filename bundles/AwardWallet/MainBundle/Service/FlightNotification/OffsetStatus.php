<?php

namespace AwardWallet\MainBundle\Service\FlightNotification;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class OffsetStatus
{
    private ?int $providerId;

    private string $kind;

    /**
     * @var string[]
     */
    private array $categories;

    private float $offsetHours;

    private int $offset;

    private int $sendingDelay;

    private int $deadline;

    private int $timestamp;

    private ?OffsetStatus $nextStatus;

    /**
     * @param int $offset seconds
     * @param int $sendingDelay seconds
     */
    public function __construct(
        ?int $providerId,
        string $kind,
        array $categories,
        float $offsetHours,
        int $offset,
        int $sendingDelay,
        int $deadline,
        int $timestamp,
        ?OffsetStatus $nextStatus = null
    ) {
        $this->providerId = $providerId;
        $this->kind = $kind;
        $this->categories = $categories;
        $this->offsetHours = $offsetHours;
        $this->offset = $offset;
        $this->sendingDelay = $sendingDelay;
        $this->deadline = $deadline;
        $this->timestamp = $timestamp;
        $this->nextStatus = $nextStatus;
    }

    public function __toString()
    {
        return sprintf(
            'provider: %s, "%s", [%s], offset: %d (%s), delay: %d, deadline: %d, ts: %d',
            $this->providerId ?? 'unknown',
            $this->kind,
            implode(', ', $this->categories),
            $this->offset,
            $this->offsetHours,
            $this->sendingDelay,
            $this->deadline,
            $this->timestamp,
        );
    }

    public function getProviderId(): ?int
    {
        return $this->providerId;
    }

    public function getKind(): string
    {
        return $this->kind;
    }

    public function addCategory(string $category)
    {
        $this->categories[] = $category;
        $this->categories = array_unique($this->categories);
    }

    /**
     * @return string[]
     */
    public function getCategories(): array
    {
        return $this->categories;
    }

    public function hasCategory(string $category): bool
    {
        return array_search($category, $this->categories) !== false;
    }

    public function getOffsetHours(): float
    {
        return $this->offsetHours;
    }

    /**
     * @return int seconds
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @return int seconds
     */
    public function getSendingDelay(): int
    {
        return $this->sendingDelay;
    }

    public function getDeadline(): int
    {
        return $this->deadline;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function setNextStatus(?OffsetStatus $nextStatus): self
    {
        $this->nextStatus = $nextStatus;

        return $this;
    }

    public function getNextStatus(): ?OffsetStatus
    {
        return $this->nextStatus;
    }
}
