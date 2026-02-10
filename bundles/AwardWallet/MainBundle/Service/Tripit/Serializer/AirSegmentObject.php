<?php

namespace AwardWallet\MainBundle\Service\Tripit\Serializer;

use JMS\Serializer\Annotation\Type;
use Symfony\Component\Validator\Constraints as Assert;

class AirSegmentObject
{
    /**
     * @var DateTimeObject
     * @Assert\NotBlank()
     * @Assert\Valid()
     * @Assert\Type(DateTimeObject::class)
     * @Type("AwardWallet\MainBundle\Service\Tripit\Serializer\DateTimeObject")
     */
    private $StartDateTime;
    /**
     * @var DateTimeObject
     * @Assert\NotBlank()
     * @Assert\Valid()
     * @Assert\Type(DateTimeObject::class)
     * @Type("AwardWallet\MainBundle\Service\Tripit\Serializer\DateTimeObject")
     */
    private $EndDateTime;
    /**
     * @var string
     * @Assert\NotBlank()
     * @Type("string")
     */
    private $start_airport_code;
    /**
     * @var string
     * @Type("string")
     */
    private $start_airport_latitude;
    /**
     * @var string
     * @Type("string")
     */
    private $start_airport_longitude;
    /**
     * @var string
     * @Type("string")
     */
    private $start_city_name;
    /**
     * @var string
     * @Type("string")
     */
    private $start_country_code;
    /**
     * @var string
     * @Type("string")
     */
    private $start_terminal;
    /**
     * @var string
     * @Assert\NotBlank()
     * @Type("string")
     */
    private $end_airport_code;
    /**
     * @var string
     * @Type("string")
     */
    private $end_airport_latitude;
    /**
     * @var string
     * @Type("string")
     */
    private $end_airport_longitude;
    /**
     * @var string
     * @Type("string")
     */
    private $end_city_name;
    /**
     * @var string
     * @Type("string")
     */
    private $end_country_code;
    /**
     * @var string
     * @Type("string")
     */
    private $end_terminal;
    /**
     * @var string
     * @Type("string")
     */
    private $marketing_airline;
    /**
     * @var string
     * @Type("string")
     */
    private $marketing_airline_code;
    /**
     * @var string
     * @Type("string")
     */
    private $marketing_flight_number;
    /**
     * @var string
     * @Type("string")
     */
    private $aircraft;
    /**
     * @var string
     * @Type("string")
     */
    private $aircraft_display_name;
    /**
     * @var string
     * @Type("string")
     */
    private $distance;
    /**
     * @var string
     * @Type("string")
     */
    private $duration;

    public function getStartDateTime(): DateTimeObject
    {
        return $this->StartDateTime;
    }

    public function getEndDateTime(): DateTimeObject
    {
        return $this->EndDateTime;
    }

    public function getStartAirportCode()
    {
        return $this->start_airport_code;
    }

    public function getStartAirportLatitude()
    {
        return $this->start_airport_latitude;
    }

    public function getStartAirportLongitude()
    {
        return $this->start_airport_longitude;
    }

    public function getStartCityName()
    {
        return $this->start_city_name;
    }

    public function getStartCountryCode()
    {
        return $this->start_country_code;
    }

    public function getStartTerminal()
    {
        return $this->start_terminal;
    }

    public function getEndAirportCode()
    {
        return $this->end_airport_code;
    }

    public function getEndAirportLatitude()
    {
        return $this->end_airport_latitude;
    }

    public function getEndAirportLongitude()
    {
        return $this->end_airport_longitude;
    }

    public function getEndCityName()
    {
        return $this->end_city_name;
    }

    public function getEndCountryCode()
    {
        return $this->end_country_code;
    }

    public function getEndTerminal()
    {
        return $this->end_terminal;
    }

    public function getMarketingAirline()
    {
        return $this->marketing_airline;
    }

    public function getMarketingAirlineCode()
    {
        return $this->marketing_airline_code;
    }

    public function getMarketingFlightNumber()
    {
        return $this->marketing_flight_number;
    }

    public function getAircraft()
    {
        return $this->aircraft;
    }

    public function getAircraftDisplayName()
    {
        return $this->aircraft_display_name;
    }

    public function getDistance()
    {
        return $this->distance;
    }

    public function getDuration()
    {
        return $this->duration;
    }
}
