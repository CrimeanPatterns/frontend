<?php

namespace AwardWallet\MainBundle\Service\FlightInfo\Engine;

use AwardWallet\MainBundle\Service\FlightInfo\Exceptions\HttpRequestException;

interface EngineInterface
{
    /**
     * @return HttpResponse
     * @throws HttpRequestException
     */
    public function send(HttpRequest $request);
}
