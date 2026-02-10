<?php

namespace AwardWallet\MainBundle\Timeline\Util;

class ResolvedFlightName
{
    /**
     * @var string
     */
    public $airlineName;
    /**
     * @var string
     */
    public $iataCode;
    /**
     * @var string
     */
    public $flightNumber;

    public function getAirlineName()
    {
        return $this->airlineName;
    }

    public function setAirlineName($airlineName)
    {
        $this->airlineName = $airlineName;
    }

    public function getIataCode()
    {
        return $this->iataCode;
    }

    public function setIataCode($iataCode)
    {
        $this->iataCode = $iataCode;
    }

    public function getFlightNumber()
    {
        return $this->flightNumber;
    }

    public function setFlightNumber($flightNumber)
    {
        $this->flightNumber = $flightNumber;
    }
}
