<?php

namespace AwardWallet\MainBundle\Globals\Utils;

use AwardWallet\Common\Memcached\Util as MemcachedUtil;
use Duration\Duration;

class ConcurrentArrayFactory
{
    private MemcachedUtil $memcachedUtil;
    private \Memcached $memcached;

    public function __construct(MemcachedUtil $memcachedUtil, \Memcached $memcached)
    {
        $this->memcachedUtil = $memcachedUtil;
        $this->memcached = $memcached;
    }

    public function create(string $cacheKey, Duration $cacheTtl): ConcurrentArray
    {
        return new ConcurrentArray($this->memcachedUtil, $this->memcached, $cacheKey, $cacheTtl);
    }
}
