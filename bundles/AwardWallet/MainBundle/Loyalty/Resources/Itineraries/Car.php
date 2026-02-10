<?php

namespace AwardWallet\MainBundle\Loyalty\Resources\Itineraries;

use JMS\Serializer\Annotation\Type;

/**
 * Class RentalCar.
 *
 * @property $type
 * @property $model
 * @property $imageUrl
 */
class Car extends LoggerEntity
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
    protected $model;
    /**
     * @var string
     * @Type("string")
     */
    protected $imageUrl;
}
