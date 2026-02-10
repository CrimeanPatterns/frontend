<?php

namespace AwardWallet\MainBundle\Service\OneTimeCodeProcessor;

use AwardWallet\MainBundle\Entity\Account;

class FinderResult
{
    public array $candidates = [];
    public ?Account $found;

    public function __construct()
    {
        $this->found = null;
    }
}
