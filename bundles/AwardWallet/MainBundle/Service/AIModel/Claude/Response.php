<?php

namespace AwardWallet\MainBundle\Service\AIModel\Claude;

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
     * Get token usage information including cache details.
     */
    public function getTokenUsage(): array
    {
        return $this->rawResponse['usage'] ?? [];
    }

    /**
     * Get the number of cached tokens read from cache.
     */
    public function getCachedTokens(): ?int
    {
        return $this->rawResponse['usage']['cache_read_input_tokens'] ?? null;
    }

    /**
     * Get the number of tokens written to cache.
     */
    public function getCacheWriteTokens(): ?int
    {
        return $this->rawResponse['usage']['cache_creation_input_tokens'] ?? null;
    }

    /**
     * Get the stop reason for the response.
     *
     * @return string The stop reason (e.g., "end_turn", "max_tokens", "stop_sequence")
     */
    public function getStopReason(): string
    {
        return $this->rawResponse['stop_reason'] ?? '';
    }

    /**
     * Get finish reason (alias for getStopReason for compatibility).
     *
     * @return string The finish reason
     */
    public function getFinishReason(): string
    {
        return $this->getStopReason();
    }

    public function isSuccessfulFinishReason(): bool
    {
        return $this->getStopReason() === 'end_turn';
    }

    public function isTruncated(): bool
    {
        return $this->getStopReason() === 'max_tokens';
    }

    public function isContentFiltered(): bool
    {
        // Claude doesn't use content_filter, but can have safety-related stops
        return false;
    }
}
