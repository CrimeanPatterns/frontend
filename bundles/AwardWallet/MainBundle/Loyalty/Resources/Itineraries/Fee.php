<?php

namespace AwardWallet\MainBundle\Loyalty\Resources\Itineraries;

use JMS\Serializer\Annotation\Type;

/**
 * Class Fee.
 *
 * @property $name
 * @property $charge
 */
class Fee extends LoggerEntity
{
    /**
     * @var string
     * @Type("string")
     */
    protected $name;

    /**
     * @var float
     * @Type("double")
     */
    protected $charge;
}
