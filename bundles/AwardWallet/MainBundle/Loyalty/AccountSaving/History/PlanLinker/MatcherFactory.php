<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\History\PlanLinker;

class MatcherFactory
{
    private $matchers = [];

    public function __construct(iterable $matchers)
    {
        foreach ($matchers as $matcher) {
            /** @var MatcherInterface $matcher */
            foreach ($matcher->getProviderCodes() as $providerCode) {
                if (isset($this->dataSources[$providerCode])) {
                    throw new \Exception("multiple data sources for provider " . $providerCode);
                }
                $this->matchers[$providerCode] = $matcher;
            }
        }
    }

    public function getMatcher(string $providerCode): ?MatcherInterface
    {
        return $this->matchers[$providerCode] ?? null;
    }
}
