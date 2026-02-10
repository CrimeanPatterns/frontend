<?php

namespace AwardWallet\MainBundle\Updater;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class AddAccount
{
    public const LOW_PRIORITY = 0;
    public const HIGH_PRIORITY = 1;
    private int $accountId;
    private int $priority;

    public function __construct(int $accountId, int $priority)
    {
        $this->accountId = $accountId;
        $this->priority = $priority;
    }

    public function getAccountId(): int
    {
        return $this->accountId;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public static function createLowPriority(int $accountId): self
    {
        return new self($accountId, self::LOW_PRIORITY);
    }

    public static function createHighPriority(int $accountId): self
    {
        return new self($accountId, self::HIGH_PRIORITY);
    }
}
