<?php

namespace AwardWallet\MainBundle\Service\FlightInfo\Engine;

use Psr\Log\LoggerInterface;

class MemcacheLocker extends DummyLocker
{
    protected $locker_key;

    protected \Memcached $memcache;

    protected LoggerInterface $logger;

    public function __construct($failures_to_lockout, $lockout_timeout, $reset_timeout, $locker_key, \Memcached $memcache, LoggerInterface $logger)
    {
        parent::__construct($failures_to_lockout, $lockout_timeout, $reset_timeout);

        $this->locker_key = $locker_key;
        $this->memcache = $memcache;
        $this->logger = $logger;
    }

    public function failure()
    {
        $failures = $this->memcache->increment($this->locker_key . '_failures', 1, 1, $this->reset_timeout);

        if ($failures > $this->failures_to_lockout) {
            $this->logger->error("[FlightInfo] " . $this->locker_key . " requests lockout");
            $this->memcache->increment($this->locker_key . '_lockout', 1, 1, $this->lockout_timeout);
        }
    }

    public function locked()
    {
        return $this->memcache->get($this->locker_key . '_lockout');
    }

    public function reset()
    {
        $this->memcache->delete($this->locker_key . '_failures');
        $this->memcache->delete($this->locker_key . '_lockout');
    }
}
