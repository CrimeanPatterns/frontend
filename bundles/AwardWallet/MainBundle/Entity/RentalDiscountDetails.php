<?php

namespace AwardWallet\MainBundle\Entity;

use JMS\Serializer\Annotation as Serializer;

class RentalDiscountDetails
{
    /**
     * @var string
     * @Serializer\Type("string")
     */
    private $name;

    /**
     * @var string
     * @Serializer\Type("string")
     */
    private $code;

    public function __construct(string $name, string $code)
    {
        $this->name = $name;
        $this->code = $code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public static function getPropertiesArray(): array
    {
        return [
            'name',
            'code',
        ];
    }
}
