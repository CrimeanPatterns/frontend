<?php

namespace AwardWallet\MainBundle\Loyalty\Resources\Itineraries;

use JMS\Serializer\Annotation\Type;

/**
 * Class Cruise.
 *
 * @property $cruiseDetails
 */
class Cruise extends Flight
{
    /**
     * @var CruiseDetails
     * @Type("AwardWallet\MainBundle\Loyalty\Resources\Itineraries\CruiseDetails")
     */
    protected $cruiseDetails;
}
