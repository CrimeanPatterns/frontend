<?php

namespace AwardWallet\MainBundle\Loyalty\Resources\Itineraries;

use JMS\Serializer\Annotation\Type;

/**
 * Class Trip.
 *
 * @property $segments
 * @property $travelers
 * @property $ticketNumbers
 */
class Flight extends Itinerary
{
    /**
     * @var FlightSegment[]
     * @Type("array<AwardWallet\MainBundle\Loyalty\Resources\Itineraries\FlightSegment>")
     */
    protected $segments;
    /**
     * @var Person[]
     * @Type("array<AwardWallet\MainBundle\Loyalty\Resources\Itineraries\Person>")
     */
    protected $travelers;
    /**
     * @var array
     * @Type("array")
     */
    protected $ticketNumbers;
}
