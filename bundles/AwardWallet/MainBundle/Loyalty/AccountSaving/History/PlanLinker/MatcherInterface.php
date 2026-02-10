<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\History\PlanLinker;

interface MatcherInterface
{
    /**
     * @return string[]
     */
    public function getProviderCodes(): array;

    /**
     * @return Match[]
     */
    public function findMatchingItineraries(string $provider, int $userId, ?int $userAgentId, array $row): array;
}
