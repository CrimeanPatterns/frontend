<?php

namespace AwardWallet\MainBundle\Globals\Updater\Engine;

class CheckAccountResponse
{
    private int $accountId;
    private ?string $browserExtensionSessionId;
    private ?string $browserExtensionConnectionToken;
    private string $loyaltyRequestId;

    public function __construct(
        string $loyaltyRequestId,
        int $accountId,
        ?string $browserExtensionSessionId,
        ?string $browserExtensionConnectionToken
    ) {
        $this->loyaltyRequestId = $loyaltyRequestId;
        $this->accountId = $accountId;
        $this->browserExtensionSessionId = $browserExtensionSessionId;
        $this->browserExtensionConnectionToken = $browserExtensionConnectionToken;
    }

    public function getAccountId(): int
    {
        return $this->accountId;
    }

    public function getBrowserExtensionSessionId(): ?string
    {
        return $this->browserExtensionSessionId;
    }

    public function getBrowserExtensionConnectionToken(): ?string
    {
        return $this->browserExtensionConnectionToken;
    }

    public function getLoyaltyRequestId(): string
    {
        return $this->loyaltyRequestId;
    }
}
