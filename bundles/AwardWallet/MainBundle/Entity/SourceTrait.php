<?php

namespace AwardWallet\MainBundle\Entity;

use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\SourceInterface;

trait SourceTrait
{
    /**
     * @var SourceInterface[]
     * @ORM\Column(type="jms_json", name="Sources")
     */
    private $sources;

    public function addSource(SourceInterface $source)
    {
        $existing = $this->sources[$source->getId()] ?? null;

        if ($existing === null) {
            $this->sources[$source->getId()] = $source;
        } else {
            $existing->stillExists();
        }
    }

    /**
     * @return SourceInterface[]
     */
    public function getSources(): array
    {
        return $this->sources ?? [];
    }

    /**
     * @param SourceInterface[] $sources
     */
    public function setSources(array $sources): self
    {
        $this->sources = $sources;

        return $this;
    }
}
