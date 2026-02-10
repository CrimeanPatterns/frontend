<?php

namespace AwardWallet\MainBundle\Loyalty\SuccessRate;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class ProviderStats
{
    private string $providerCode;
    private int $errorsCount;
    private int $successCount;

    public function __construct(string $providerCode, int $errorsCount, int $successCount)
    {
        $this->providerCode = $providerCode;
        $this->errorsCount = $errorsCount;
        $this->successCount = $successCount;
    }

    public function getProviderCode(): string
    {
        return $this->providerCode;
    }

    public function getErrorsCount(): int
    {
        return $this->errorsCount;
    }

    public function getSuccessCount(): int
    {
        return $this->successCount;
    }
}
