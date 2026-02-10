<?php

namespace AwardWallet\MainBundle\Globals\Features;

class FeaturesBitSet
{
    private int $featuresBitSet;

    public function __construct(int $featuresBitSet)
    {
        $this->featuresBitSet = $featuresBitSet;
    }

    public static function fromMap(array $features): self
    {
        $featuresBitSet = 0;

        foreach ($features as $featureBit => $isEnabled) {
            if ($isEnabled) {
                $featuresBitSet |= $featureBit;
            }
        }

        return new self($featuresBitSet);
    }

    public function supports(int $features): bool
    {
        return (bool) ($features & $this->featuresBitSet);
    }

    public function notSupports(int $features): bool
    {
        return !$this->supports($features);
    }

    public function all(): int
    {
        return $this->featuresBitSet;
    }

    public function enable(int $features): self
    {
        return new self($this->featuresBitSet | $features);
    }

    public function disable(int $features): self
    {
        return new self($this->featuresBitSet & ~$features);
    }
}
