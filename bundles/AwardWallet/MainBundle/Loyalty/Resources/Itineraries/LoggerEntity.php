<?php

namespace AwardWallet\MainBundle\Loyalty\Resources\Itineraries;

class LoggerEntity
{
    public function __set($key, $val)
    {
        $this->$key = $val;
    }

    public function __get($key)
    {
        return $this->$key;
    }

    public function __isset($key)
    {
        return isset($this->$key);
    }

    /**
     * Itinerary properties setter.
     * Example: If property $recordLocator is defined in child $obj, just call $obj->setRecordLocator($value).
     */
    public function __call($method, $args)
    {
        $prefix = substr($method, 0, 3);

        $prop = lcfirst(substr($method, 3));

        if ($prefix !== 'set' || !property_exists(__CLASS__, $prop)) {
            throw new \RuntimeException('Call to undefined method');
        }

        if (!isset($args[0])) {
            throw new \RuntimeException('Undefined argumet for method ' . $method);
        }

        $this->$prop = $args[0];

        return $this;
    }
}
