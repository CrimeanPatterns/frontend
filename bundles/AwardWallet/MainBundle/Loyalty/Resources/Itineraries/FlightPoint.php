<?php

namespace AwardWallet\MainBundle\Loyalty\Resources\Itineraries;

use JMS\Serializer\Annotation\Type;

/**
 * Class TripPoint.
 *
 * @property $airportCode
 * @property $stationCode
 * @property $terminal
 * @property $name
 * @property $localDateTime
 * @property $address
 * @property $gate
 * @property $baggage
 */
class FlightPoint extends LoggerEntity
{
    /**
     * @var string
     * @Type("string")
     */
    protected $airportCode;

    /**
     * @var string
     * @Type("string")
     */
    protected $stationCode;

    /**
     * @var string
     * @Type("string")
     */
    protected $terminal;

    /**
     * @var string
     * @Type("string")
     */
    protected $name;

    /**
     * @var string
     * @Type("string")
     */
    protected $localDateTime;

    /**
     * @var Address
     * @Type("AwardWallet\MainBundle\Loyalty\Resources\Itineraries\Address")
     */
    protected $address;

    /**
     * @var string
     * @Type("string")
     */
    protected $gate;

    /**
     * @var string
     * @Type("string")
     */
    protected $baggage;
}
