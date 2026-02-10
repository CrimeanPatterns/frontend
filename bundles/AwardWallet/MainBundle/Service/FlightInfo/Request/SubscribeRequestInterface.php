<?php

namespace AwardWallet\MainBundle\Service\FlightInfo\Request;

interface SubscribeRequestInterface extends RequestInterface
{
    /**
     * @return self
     */
    public function subscribe($url);
}
