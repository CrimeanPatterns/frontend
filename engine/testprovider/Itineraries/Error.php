<?php

namespace AwardWallet\Engine\testprovider\Itineraries;

use AwardWallet\Engine\testprovider\Success;

class Error extends Success
{
    public function ParseItineraries()
    {
        throw new \Exception('Parse Error');
    }
}
