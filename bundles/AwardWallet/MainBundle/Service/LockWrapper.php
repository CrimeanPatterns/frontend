<?php

namespace AwardWallet\MainBundle\Service;

use Duration\Duration;
use Symfony\Component\Lock\Exception\LockAcquiringException;
use Symfony\Component\Lock\Exception\LockConflictedException;
use Symfony\Component\Lock\Factory;

class LockWrapper
{
    private Factory $lockFactory;

    public function __construct(Factory $lockFactory)
    {
        $this->lockFactory = $lockFactory;
    }

    /**
     * @param $ttl int|Duration
     * @throws LockAcquiringException
     * @throws LockConflictedException
     */
    public function wrap(string $key, callable $callable, $ttl = 300)
    {
        if ($ttl instanceof Duration) {
            $ttl = $ttl->getAsSecondsInt();
        }

        $lock = $this->lockFactory->createLock($key, $ttl);

        if (!$lock->acquire()) {
            throw new LockConflictedException();
        }

        try {
            return $callable();
        } finally {
            $lock->release();
        }
    }
}
