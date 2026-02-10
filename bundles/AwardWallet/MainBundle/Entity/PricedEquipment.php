<?php

namespace AwardWallet\MainBundle\Entity;

use JMS\Serializer\Annotation as Serializer;

class PricedEquipment
{
    /**
     * @var string
     * @Serializer\Type("string")
     */
    private $name;

    /**
     * @var float
     * @Serializer\Type("float")
     */
    private $charge;

    public function __construct(string $name, float $charge)
    {
        $this->name = $name;
        $this->charge = $charge;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCharge(): float
    {
        return $this->charge;
    }

    public static function getPropertiesArray(): array
    {
        return [
            'name',
            'charge',
        ];
    }
}
