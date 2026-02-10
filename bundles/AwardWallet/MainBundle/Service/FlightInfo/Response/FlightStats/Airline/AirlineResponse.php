<?php

namespace AwardWallet\MainBundle\Service\FlightInfo\Response\FlightStats\Airline;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\FlightInfo\Response\CommonResponse;
use AwardWallet\MainBundle\Service\FlightInfo\Response\ResponseInterface;

/**
 * @NoDI()
 */
class AirlineResponse extends CommonResponse implements ResponseInterface
{
    /** @var Airline[] */
    protected $airlines = [];

    /**
     * @return Airline[]
     */
    public function getAirports()
    {
        return $this->airlines;
    }

    /**
     * @return AirlineResponse
     */
    public function addAirline(Airline $airline)
    {
        $this->airlines[$airline->fs] = $airline;

        return $this;
    }
}
