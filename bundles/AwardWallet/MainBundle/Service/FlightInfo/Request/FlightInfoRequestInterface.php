<?php

namespace AwardWallet\MainBundle\Service\FlightInfo\Request;

interface FlightInfoRequestInterface extends RequestInterface
{
    /**
     * @return self
     */
    public function carrier($carrierCode);

    /**
     * @return self
     */
    public function flight($flightNumber);

    /**
     * @return self
     */
    public function date(\DateTime $date);

    /**
     * @return self
     */
    public function departure($airportCode);

    /**
     * @return self
     */
    public function arrival($airportCode);
}
