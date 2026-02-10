<?php

namespace AwardWallet\MainBundle\Service\ProviderSignal;

class UserSignalSchema extends \TBaseSchema
{
    public function __construct()
    {
        parent::__construct();
        $this->ListClass = UserSignalList::class;
    }
}
