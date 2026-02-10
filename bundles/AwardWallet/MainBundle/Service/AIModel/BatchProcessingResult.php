<?php

namespace AwardWallet\MainBundle\Service\AIModel;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI
 */
class BatchProcessingResult
{
    private array $data;

    private array $stats;

    public function __construct(array $data, array $stats)
    {
        $this->data = $data;
        $this->stats = $stats;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getPromptTokens(?array $providers = null): int
    {
        $total = 0;
        $stats = $this->filterStatsByProviders($providers);

        foreach ($stats as $providerStats) {
            $total += $providerStats['prompt_tokens'];
        }

        return $total;
    }

    public function getCompletionTokens(?array $providers = null): int
    {
        $total = 0;
        $stats = $this->filterStatsByProviders($providers);

        foreach ($stats as $providerStats) {
            $total += $providerStats['completion_tokens'];
        }

        return $total;
    }

    public function getCost(?array $providers = null): float
    {
        $total = 0.0;
        $stats = $this->filterStatsByProviders($providers);

        foreach ($stats as $providerStats) {
            $total += $providerStats['cost'];
        }

        return $total;
    }

    public function getRequestCount(?array $providers = null): int
    {
        $total = 0;
        $stats = $this->filterStatsByProviders($providers);

        foreach ($stats as $providerStats) {
            $total += $providerStats['requests'];
        }

        return $total;
    }

    public function getProviders(): array
    {
        return array_keys($this->stats);
    }

    private function filterStatsByProviders(?array $providers): array
    {
        if (empty($providers)) {
            return $this->stats;
        }

        return array_intersect_key($this->stats, array_flip($providers));
    }
}
