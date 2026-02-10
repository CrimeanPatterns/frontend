<?php

namespace AwardWallet\MainBundle\Service;

class Locker
{
    private \Memcached $memcached;

    private string $myId;

    public function __construct(\Memcached $memcached, ?string $prefix = null, ?int $pid = null)
    {
        $this->memcached = $memcached;

        if ($prefix === null) {
            $prefix = gethostname();
        }

        if ($pid === null) {
            $pid = getmypid();
        }
        $this->myId = $prefix . "_" . $pid;
    }

    public function acquire(string $lockName, int $ttl, int $waitTime = 0): bool
    {
        $startTime = microtime(true);

        do {
            if ($this->memcached->add($lockName, $this->myId, $ttl)) {
                return true;
            }

            if ($waitTime > 0) {
                usleep(random_int(300000, 1500000));
            }
        } while ((microtime(true) - $startTime) < $waitTime);

        return false;
    }

    public function getLastError(): ?string
    {
        return $this->memcached->getLastErrorMessage();
    }

    public function isLocked(string $lockName): bool
    {
        $value = $this->memcached->get($lockName);

        return $value !== false && $value !== "deleted"; // sometimes memcached returns expired items
    }

    public function release(string $lockName)
    {
        $this->keep($lockName, time() - 3600);
    }

    public function keep(string $lockName, int $ttl)
    {
        $info = $this->memcached->get($lockName, null, \Memcached::GET_EXTENDED);

        if (empty($info) || $info['value'] !== $this->myId) {
            return false;
        }

        if ($ttl > 60 * 60 * 24 * 30 && $ttl < (time() - 100)) {
            $value = "deleted";
        } else {
            $value = $info['value'];
        }

        return $this->memcached->cas($info['cas'], $lockName, $value, $ttl);
    }
}
