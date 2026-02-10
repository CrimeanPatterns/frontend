<?php

namespace AwardWallet\MainBundle\Service\Account;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class ManualUpdateResult
{
    private ?string $eliteLevel;
    private ?array $eliteLevelOptions;
    private bool $isMailboxConnected;
    private bool $isNotifyMe;

    public function __construct(
        ?string $eliteLevel,
        ?array $eliteLevelOptions,
        bool $isMailboxConnected,
        bool $isNotifyMe
    ) {
        $this->eliteLevel = $eliteLevel;
        $this->eliteLevelOptions = $eliteLevelOptions;
        $this->isMailboxConnected = $isMailboxConnected;
        $this->isNotifyMe = $isNotifyMe;
    }

    public function getEliteLevel(): ?string
    {
        return $this->eliteLevel;
    }

    public function getEliteLevelOptions(): ?array
    {
        return $this->eliteLevelOptions;
    }

    public function isMailboxConnected(): bool
    {
        return $this->isMailboxConnected;
    }

    public function isNotifyMe(): bool
    {
        return $this->isNotifyMe;
    }
}
