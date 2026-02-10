<?php

namespace AwardWallet\MainBundle\Service\AIModel;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI
 */
class BatchConfig
{
    private int $batchSize;
    private int $maxTokensPerBatch;
    private int $maxRetries;
    private int $retryDelayBase;

    public function __construct(
        int $batchSize = 50,
        int $maxTokensPerBatch = 3000,
        int $maxRetries = 3,
        int $retryDelayBase = 2
    ) {
        $this->batchSize = $batchSize;
        $this->maxTokensPerBatch = $maxTokensPerBatch;
        $this->maxRetries = $maxRetries;
        $this->retryDelayBase = $retryDelayBase;
    }

    public static function create(): self
    {
        return new self();
    }

    public function withBatchSize(int $batchSize): self
    {
        $clone = clone $this;
        $clone->batchSize = $batchSize;

        return $clone;
    }

    public function withMaxTokensPerBatch(int $maxTokensPerBatch): self
    {
        $clone = clone $this;
        $clone->maxTokensPerBatch = $maxTokensPerBatch;

        return $clone;
    }

    public function withMaxRetries(int $maxRetries): self
    {
        $clone = clone $this;
        $clone->maxRetries = $maxRetries;

        return $clone;
    }

    public function withRetryDelayBase(int $retryDelayBase): self
    {
        $clone = clone $this;
        $clone->retryDelayBase = $retryDelayBase;

        return $clone;
    }

    public function getBatchSize(): int
    {
        return $this->batchSize;
    }

    public function getMaxTokensPerBatch(): int
    {
        return $this->maxTokensPerBatch;
    }

    public function getMaxRetries(): int
    {
        return $this->maxRetries;
    }

    public function getRetryDelayBase(): int
    {
        return $this->retryDelayBase;
    }
}
