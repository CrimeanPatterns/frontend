<?php

namespace AwardWallet\MainBundle\Service;

interface ProviderAuthInterface
{
    public function getAuthUrl($accountId);
}
