<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Sources;

interface SourceListInterface
{
    public function addSource(SourceInterface $source);

    /**
     * @return SourceInterface[]
     */
    public function getSources(): array;

    /**
     * @param SourceInterface[] $sources
     */
    public function setSources(array $sources);
}
