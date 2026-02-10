<?php

namespace AwardWallet\MainBundle\Loyalty\Resources\Itineraries;

use JMS\Serializer\Annotation\Type;

/**
 * Class Room.
 *
 * @property $type
 * @property $description
 */
class Room extends LoggerEntity
{
    /**
     * @var string
     * @Type("string")
     */
    protected $type;
    /**
     * @var string
     * @Type("string")
     */
    protected $description;
}
