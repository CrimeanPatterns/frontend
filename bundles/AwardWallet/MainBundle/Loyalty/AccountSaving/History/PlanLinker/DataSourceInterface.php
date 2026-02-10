<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\History\PlanLinker;

interface DataSourceInterface
{
    /**
     * @return string[]
     */
    public function getProviderCodes(): array;

    public function getRows(string $provider): iterable;
}
