<?php

namespace AwardWallet\MainBundle\Service\Cache\Exceptions;

class DoNotStoreException extends CacheException
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }
}
