<?php

namespace AwardWallet\MainBundle\Globals;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;

class GuzzleClientFactory
{
    public function createClient(array $config = []): ClientInterface
    {
        return new Client($config);
    }
}
