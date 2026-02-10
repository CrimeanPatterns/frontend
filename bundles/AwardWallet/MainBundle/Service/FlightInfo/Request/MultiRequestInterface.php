<?php

namespace AwardWallet\MainBundle\Service\FlightInfo\Request;

use AwardWallet\MainBundle\Service\FlightInfo\Engine\HttpRequest;

interface MultiRequestInterface extends RequestInterface
{
    /**
     * @return HttpRequest[]
     */
    public function getHttpRequestCollection();
}
