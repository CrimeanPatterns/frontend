<?php

namespace AwardWallet\MainBundle\Service\Overlay\Type;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Loyalty\Resources\Itineraries\FlightSegment;

/**
 * @NoDI
 */
class TripSegment
{
    public $airlineIataCode;
    public $flightNumber;
    public $depCode;
    public $depDate;
    /**
     * @var FlightSegment
     */
    public $data;

    public function __construct($airlineIataCode, $flightNumber, $depCode, $depDate, FlightSegment $data)
    {
        $this->airlineIataCode = $airlineIataCode;
        $this->flightNumber = $flightNumber;
        $this->depCode = $depCode;
        $this->depDate = $depDate;
        $this->data = $data;
    }
}
