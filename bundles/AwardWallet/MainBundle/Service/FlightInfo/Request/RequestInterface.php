<?php

namespace AwardWallet\MainBundle\Service\FlightInfo\Request;

use AwardWallet\MainBundle\Service\FlightInfo\Engine\EngineInterface;
use AwardWallet\MainBundle\Service\FlightInfo\Engine\HttpRequest;
use AwardWallet\MainBundle\Service\FlightInfo\Engine\HttpResponse;
use AwardWallet\MainBundle\Service\FlightInfo\Response\ResponseInterface;

interface RequestInterface
{
    /**
     * @return HttpRequest
     */
    public function getHttpRequest();

    /**
     * @return ResponseInterface
     */
    public function fetch();

    /**
     * @param \DateTime|null $createDate
     * @return ResponseInterface
     */
    public function resolveHttpResponse(HttpResponse $response, $createDate = null);

    /**
     * @return bool
     */
    public function isValid();

    /**
     * @return array
     */
    public function export();

    /**
     * @return $this
     */
    public function setEngine(EngineInterface $engine);

    /**
     * @param array $config
     * @return $this
     */
    public function setConfig($config);
}
