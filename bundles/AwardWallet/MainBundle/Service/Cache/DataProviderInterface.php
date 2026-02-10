<?php

namespace AwardWallet\MainBundle\Service\Cache;

interface DataProviderInterface
{
    public function getData(array $missingKeysList);
}
