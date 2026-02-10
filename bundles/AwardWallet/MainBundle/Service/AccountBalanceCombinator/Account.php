<?php

namespace AwardWallet\MainBundle\Service\AccountBalanceCombinator;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI
 */
class Account implements BalanceInterface
{
    private int $id;

    private int $providerId;

    private ?int $userAgent;

    private bool $isShareable;

    private string $displayName;

    private float $balance;

    private ?float $avgPointValue;

    private float $multiplier;

    private ?float $step;

    private bool $transferable;

    public function __construct(
        int $id,
        int $providerId,
        ?int $userAgent,
        bool $isShareable,
        string $displayName,
        float $balance,
        ?float $avgPointValue,
        float $multiplier,
        ?float $step,
        bool $transferable
    ) {
        $this->id = $id;
        $this->providerId = $providerId;
        $this->userAgent = $userAgent;
        $this->isShareable = $isShareable;
        $this->displayName = $displayName;
        $this->balance = $balance;
        $this->avgPointValue = $avgPointValue;
        $this->multiplier = $multiplier;
        $this->step = $step;
        $this->transferable = $transferable;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getProviderId(): int
    {
        return $this->providerId;
    }

    public function getUserAgent(): ?int
    {
        return $this->userAgent;
    }

    public function isShareable(): bool
    {
        return $this->isShareable;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function getBalance(): float
    {
        return $this->balance;
    }

    public function getConvertedBalance(): float
    {
        if (is_null($this->step)) {
            return $this->getTotalConvertedBalance();
        }

        return (intval($this->balance / $this->step) * $this->step) * $this->multiplier;
    }

    public function getTotalConvertedBalance(): float
    {
        return $this->balance * $this->multiplier;
    }

    public function getAvgPointValue(): ?float
    {
        return $this->avgPointValue;
    }

    public function getMultiplier(): float
    {
        return $this->multiplier;
    }

    public function getStep(): ?float
    {
        return $this->step;
    }

    public function isTransferable(): bool
    {
        return $this->transferable;
    }

    public static function create(
        int $id,
        int $providerId,
        ?int $userAgent,
        bool $isShareable,
        string $displayName,
        float $balance,
        ?float $avgPointValue,
        float $multiplier,
        ?float $step,
        bool $transferable
    ): self {
        return new self(
            $id,
            $providerId,
            $userAgent,
            $isShareable,
            $displayName,
            $balance,
            $avgPointValue,
            $multiplier,
            $step,
            $transferable
        );
    }
}
