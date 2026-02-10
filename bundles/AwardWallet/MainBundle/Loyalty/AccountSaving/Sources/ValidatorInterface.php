<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Sources;

interface ValidatorInterface
{
    public function isValid(SourceInterface $source): ?bool;
}
