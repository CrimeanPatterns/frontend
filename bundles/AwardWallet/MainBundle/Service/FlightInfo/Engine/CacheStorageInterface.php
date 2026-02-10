<?php

namespace AwardWallet\MainBundle\Service\FlightInfo\Engine;

interface CacheStorageInterface
{
    /**
     * @return \DateTime
     */
    public function getCreateDate();

    /**
     * @return string
     */
    public function getRequest();

    /**
     * @param string $Request
     */
    public function setRequest($Request);

    /**
     * @return []|null
     */
    public function getResponse();

    /**
     * @return HttpResponse
     */
    public function getHttpResponse();

    /**
     * @param HttpResponse|[]|null $Response
     */
    public function setResponse($Response);

    public function setHttpResponse(HttpResponse $Response);

    /**
     * @return int
     */
    public function getState();

    /**
     * @param int $State
     */
    public function setState($State);

    /**
     * @return string
     */
    public function getService();

    /**
     * @param string $Service
     */
    public function setService($Service);

    /**
     * @return bool
     */
    public function isChanged();

    /**
     * @param bool $Changed
     */
    public function setChanged($Changed);

    /**
     * @return \DateTime
     */
    public function getExpireDate();

    /**
     * @param \DateTime $ExpireDate
     */
    public function setExpireDate($ExpireDate);

    /**
     * @return bool
     */
    public function isExpired();

    /**
     * @return mixed|void
     */
    public function getJson();
}
