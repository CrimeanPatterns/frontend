<?php

namespace AwardWallet\MainBundle\Service\Account\Model\ExpiredAccount;

class Translatable
{
    /**
     * @var string
     */
    private $key;

    /**
     * @var array
     */
    private $parameters = [];

    /**
     * @var string
     */
    private $domain = 'email';

    /**
     * @var int|null
     */
    private $count;

    /**
     * @var bool
     */
    private $escape = true;

    public function __construct($key, $parameters = [], $domain = 'email', $count = null, $escape = true)
    {
        $this->key = $key;
        $this->parameters = $parameters;
        $this->domain = $domain;
        $this->count = $count;
        $this->escape = $escape;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * @return int|null
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @return bool
     */
    public function isEscape()
    {
        return $this->escape;
    }
}
