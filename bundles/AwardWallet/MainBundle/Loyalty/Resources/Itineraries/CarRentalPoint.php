<?php

namespace AwardWallet\MainBundle\Loyalty\Resources\Itineraries;

use JMS\Serializer\Annotation\Type;

/**
 * Class RentalPoint.
 *
 * @property $address
 * @property $localDateTime
 * @property $openingHours
 * @property $phone
 * @property $fax
 */
class CarRentalPoint extends LoggerEntity
{
    /**
     * @var Address
     * @Type("AwardWallet\MainBundle\Loyalty\Resources\Itineraries\Address")
     */
    protected $address;
    /**
     * @var string
     * @Type("string")
     */
    protected $localDateTime;
    /**
     * @var string
     * @Type("string")
     */
    protected $openingHours;
    /**
     * @var string
     * @Type("string")
     */
    protected $phone;
    /**
     * @var string
     * @Type("string")
     */
    protected $fax;
}
