<?php

namespace AwardWallet\MainBundle\Service\FlightInfo\Engine;

interface CacherInterface
{
    public const STATE_NEW = 0;
    public const STATE_OK = 1;
    public const STATE_API_ERROR = 2;
    public const STATE_AUTH_ERROR = 3;

    /**
     * @return CacheStorageInterface|false
     */
    public function get(HttpRequest $request);

    /**
     * @return CacheStorageInterface[]|false
     */
    public function getAll(HttpRequest $request);

    /**
     * @return CacheStorageInterface|false
     */
    public function cache(HttpRequest $request, HttpResponse $response);

    /**
     * @param int $state
     * @return CacheStorageInterface
     */
    public function setState($cache, $state);

    /**
     * @param \DateTime|string|null $date
     * @return CacheStorageInterface
     */
    public function setExpire($cache, $date);
}
