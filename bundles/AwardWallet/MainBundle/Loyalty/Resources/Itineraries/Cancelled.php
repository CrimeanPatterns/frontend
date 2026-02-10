<?php

namespace AwardWallet\MainBundle\Loyalty\Resources\Itineraries;

use JMS\Serializer\Annotation\Type;

/**
 * Class Cancelled.
 *
 * @property $type
 * @property $itineraryType
 * @property $confirmationNumber
 */
class Cancelled extends LoggerEntity
{
    /**
     * @var string
     * @Type("string")
     */
    protected $type = 'cancelled';

    /**
     * @var string
     * @Type("string")
     */
    protected $itineraryType;

    /**
     * @var string
     * @Type("string")
     */
    protected $confirmationNumber;
}
