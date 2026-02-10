<?php

namespace AwardWallet\MainBundle\Service\FlightInfo\Response\FlightStats\Airline;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class Airline
{
    public $fs;
    public $iata;
    public $icao;
    public $name;
    public $phoneNumber;
    public $active; // bool
    public $category;

    public function import($data)
    {
        foreach ((new \ReflectionClass(static::class))->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $field = $property->getName();

            if (array_key_exists($field, $data)) {
                $this->$field = $data[$field];
            }
        }

        if ($this->active === 'false') {
            $this->active = false;
        } else {
            $this->active = (bool) $this->active;
        }

        return $this;
    }
}
