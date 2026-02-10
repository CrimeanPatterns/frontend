<?php

namespace AwardWallet\MainBundle\Loyalty\Resources\Itineraries;

use JMS\Serializer\Annotation\Type;

/**
 * Class Event.
 *
 * @property $eventName
 * @property $address
 * @property $startDateTime
 * @property $endDateTime
 * @property $phone
 * @property $fax
 * @property $guests
 * @property $guestCount
 */
class Event extends Itinerary
{
    /**
     * @var string
     * @Type("string")
     */
    protected $eventName;
    /**
     * @var Address
     * @Type("AwardWallet\MainBundle\Loyalty\Resources\Itineraries\Address")
     */
    protected $address;
    /**
     * @var string
     * @Type("string")
     */
    protected $startDateTime;
    /**
     * @var string
     * @Type("string")
     */
    protected $endDateTime;
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
    /**
     * @var Person[]
     * @Type("array<AwardWallet\MainBundle\Loyalty\Resources\Itineraries\Person>")
     */
    protected $guests;
    /**
     * @var int
     * @Type("integer")
     */
    protected $guestCount;
}
