<?php

namespace AwardWallet\MainBundle\Service\Cache\Exceptions;

class CacheDataRaceException extends CacheException
{
    /**
     * @var string
     */
    private $key;

    public function __construct($key, $code = null)
    {
        $this->key = $key;
        $this->code = $code;

        $this->message = sprintf('Data race on "%s" key', $key);
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }
}
