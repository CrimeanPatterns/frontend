<?php

namespace AwardWallet\MainBundle\Service\FlightInfo\Response;

use AwardWallet\MainBundle\Service\FlightInfo\Response\Common\FlightStatus;

interface FlightInfoResponseInterface extends ResponseInterface
{
    /**
     * @return FlightStatus[]
     */
    public function getFlightIndex();
}
