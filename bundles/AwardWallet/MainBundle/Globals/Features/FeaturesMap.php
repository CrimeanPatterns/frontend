<?php

namespace AwardWallet\MainBundle\Globals\Features;

class FeaturesMap
{
    /**
     * @param array<string, bool> $features
     */
    private array $features;

    /**
     * @param array<string, bool> $features
     */
    public function __construct(array $features)
    {
        $this->features = $features;
    }

    public function supports(string $feature): bool
    {
        return isset($this->features[$feature]) && $this->features[$feature];
    }

    public function notSupports(string $feature): bool
    {
        return !$this->supports($feature);
    }
}
