<?php

namespace AwardWallet\MainBundle\Loyalty\Resources\Itineraries;

use JMS\Serializer\Annotation\Type;

/**
 * Class TripSegment.
 *
 * @property FlightPoint $departure
 * @property FlightPoint $arrival
 * @property $seats
 * @property $transport
 * @property $flightNumber
 * @property $scheduleNumber
 */
class FlightSegment extends LoggerEntity
{
    /**
     * @var FlightPoint
     * @Type("AwardWallet\MainBundle\Loyalty\Resources\Itineraries\FlightPoint")
     */
    protected $departure;

    /**
     * @var FlightPoint
     * @Type("AwardWallet\MainBundle\Loyalty\Resources\Itineraries\FlightPoint")
     */
    protected $arrival;

    /**
     * @var array
     * @Type("array")
     */
    protected $seats;
    /**
     * @var Transport
     * @Type("AwardWallet\MainBundle\Loyalty\Resources\Itineraries\Transport")
     */
    protected $transport;
    /**
     * @var string
     * @Type("string")
     */
    protected $flightNumber;
    /**
     * @var string
     * @Type("string")
     */
    protected $scheduleNumber;
    /**
     * @var string
     * @Type("string")
     */
    protected $airlineName;
    /**
     * @var string
     * @Type("string")
     */
    protected $operator;
    /**
     * @var string
     * @Type("string")
     */
    protected $aircraft;
    /**
     * @var string
     * @Type("string")
     */
    protected $traveledMiles;
    /**
     * @var string
     * @Type("string")
     */
    protected $cabin;
    /**
     * @var string
     * @Type("string")
     */
    protected $bookingClass;
    /**
     * @var string
     * @Type("string")
     */
    protected $duration;
    /**
     * @var string
     * @Type("string")
     */
    protected $meal;
    /**
     * @var string
     * @Type("string")
     */
    protected $smoking;
    /**
     * @var string
     * @Type("string")
     */
    protected $pendingUpgradeTo;
    /**
     * @var int
     * @Type("integer")
     */
    protected $stops;
}
