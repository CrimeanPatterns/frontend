<?php

namespace AwardWallet\MainBundle\Service\AccountBalanceCombinator;

interface BalanceInterface
{
    public function getId(): int;

    public function getProviderId(): int;

    public function getBalance(): float;

    public function getConvertedBalance(): float;

    public function getTotalConvertedBalance(): float;

    public function getAvgPointValue(): ?float;

    public function getMultiplier(): float;

    public function getStep(): ?float;

    public function isTransferable(): bool;
}
