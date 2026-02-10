<?php

namespace AwardWallet\MainBundle\Service\AIModel\Deepseek;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\AIModel\AbstractResponse;

/**
 * @NoDI
 */
class Response extends AbstractResponse
{
    /**
     * Get the model used for the response.
     */
    public function getModel(): string
    {
        return $this->rawResponse['model'] ?? '';
    }

    /**
     * Get token usage information.
     */
    public function getTokenUsage(): array
    {
        return $this->rawResponse['usage'] ?? [];
    }

    /**
     * Get finish reason for the response.
     *
     * @return string The finish reason (e.g., "stop", "length")
     */
    public function getFinishReason(): string
    {
        return $this->rawResponse['choices'][0]['finish_reason'] ?? '';
    }

    public function isSuccessfulFinishReason(): bool
    {
        return $this->getFinishReason() === 'stop';
    }

    public function isTruncated(): bool
    {
        return $this->getFinishReason() === 'length';
    }

    public function isContentFiltered(): bool
    {
        return $this->getFinishReason() === 'content_filter';
    }

    public function isInsufficientSystemResource(): bool
    {
        return $this->getFinishReason() === 'insufficient_system_resource';
    }
}
