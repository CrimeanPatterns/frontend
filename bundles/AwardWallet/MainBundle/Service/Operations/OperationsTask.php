<?php

namespace AwardWallet\MainBundle\Service\Operations;

use AwardWallet\MainBundle\Worker\AsyncProcess\Task;

class OperationsTask extends Task
{
    private string $operation;

    private string $providerCode;

    private int $limit;

    private int $checkStart;

    private int $checkEnd;

    private int $withBackgroundCheckOff;

    private string $responseChannel;

    public function __construct(
        string $responseChannel,
        string $operation,
        string $providerCode,
        int $limit,
        int $withBackgroundCheckOff,
        int $checkStart,
        int $checkEnd
    ) {
        parent::__construct(OperationsExecutor::class, bin2hex(random_bytes(10)));
        $this->operation = $operation;
        $this->providerCode = $providerCode;
        $this->limit = $limit;
        $this->checkStart = $checkStart;
        $this->checkEnd = $checkEnd;
        $this->withBackgroundCheckOff = $withBackgroundCheckOff;
        $this->responseChannel = $responseChannel;
    }

    public function getOperation(): string
    {
        return $this->operation;
    }

    public function getProviderCode(): string
    {
        return $this->providerCode;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getCheckStart(): ?int
    {
        return $this->checkStart;
    }

    public function getCheckEnd(): ?int
    {
        return $this->checkEnd;
    }

    public function getWithBackgroundCheckOff(): int
    {
        return $this->withBackgroundCheckOff;
    }

    public function getResponseChannel(): string
    {
        return $this->responseChannel;
    }
}
