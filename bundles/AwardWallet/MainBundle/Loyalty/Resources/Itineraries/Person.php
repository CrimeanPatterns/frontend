<?php

namespace AwardWallet\MainBundle\Loyalty\Resources\Itineraries;

use JMS\Serializer\Annotation\Type;

/**
 * Class Person.
 *
 * @property $fullName
 */
class Person extends LoggerEntity
{
    /**
     * @var string
     * @Type("string")
     */
    protected $fullName;
}
