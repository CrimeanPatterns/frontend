<?php

namespace AwardWallet\MainBundle\Service\FlightStats;

use AwardWallet\Common\Memcached\Util;
use Psr\Log\NullLogger;

class AirlineConverterMock extends AirlineConverter
{
    public function __construct(Util $memcachedUtil)
    {
        $logger = new NullLogger();
        parent::__construct($memcachedUtil, $logger, '', '');
    }

    /**
     * @return string|null
     */
    public function IataToFSCode(string $iata)
    {
        return null;
    }

    /**
     * @return string|null
     */
    public function FSCodeToIata(string $FSCode)
    {
        return null;
    }

    /**
     * @return string|null
     */
    public function FSCodeToName(string $FSCode)
    {
        return null;
    }
}
