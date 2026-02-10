<?php

namespace AwardWallet\MainBundle\Service\FlightInfo\Request;

interface RequestFactoryInterface
{
    /**
     * @param string $class
     * @return RequestInterface
     */
    public function create($class);

    /**
     * @return array
     */
    public function getSupported();
}
