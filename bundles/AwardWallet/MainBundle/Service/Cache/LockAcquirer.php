<?php

namespace AwardWallet\MainBundle\Service\Cache;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\Cache\Model\CacheItemReference;
use Symfony\Component\Lock\Exception\LockAcquiringException;
use Symfony\Component\Lock\Exception\LockConflictedException;
use Symfony\Component\Lock\Exception\LockReleasingException;
use Symfony\Component\Lock\Key;
use Symfony\Component\Lock\Lock;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Lock\Store\RetryTillSaveStore;

/**
 * @NoDI()
 */
class LockAcquirer
{
    public const NEED_RELOAD = 0;
    public const LOCK_ACQUIRED = 1;
    public const NO_LOCK = 2;

    private const LOCK_SLEEP_MS = 5;

    private \Memcached $memcached;
    private CacheItemReference $cacheItemReference;

    public function __construct(\Memcached $memcached, CacheItemReference $cacheItemReference)
    {
        $this->memcached = $memcached;
        $this->cacheItemReference = $cacheItemReference;
    }

    /**
     * @return array{0: int, 1: ?LockInterface}
     */
    public function tryAcquire(): array
    {
        $lockTtl = $this->cacheItemReference->getLockTtl();
        $lockTtlSeconds = $lockTtl->getAsSecondsInt();
        $sleep = $this->cacheItemReference->getLockSleepInLoopInterval();
        $sleepMs = $sleep ? $sleep->getAsMillisecondsInt() : self::LOCK_SLEEP_MS;
        $maxRetries = $lockTtlSeconds * 1000 / $sleepMs;
        $store = new RetryTillSaveStore(
            $memcachedStore = new AwaitTrackedMemcachedStore(
                $this->memcached,
                \max(1, $lockTtlSeconds / 2)
            ),
            $sleepMs,
            $maxRetries
        );
        $keys = $this->cacheItemReference->getKeys();
        \sort($keys);
        $keysStr = \implode('_|_', $keys);
        $lockKey = 'cache_lock_' . \substr($keysStr, 10) . '_' . \hash('sha256', $keysStr);
        $lock = new Lock(new Key($lockKey), $store, $lockTtlSeconds, true);

        try {
            $lock->acquire(true);

            if ($memcachedStore->wasAwaited()) {
                try {
                    $lock->release();
                } catch (LockReleasingException $e) {
                    // ignore
                }

                return [self::NEED_RELOAD, null];
            }
        } catch (LockConflictedException|LockAcquiringException $e) {
            return [self::NO_LOCK, null];
        }

        return [self::LOCK_ACQUIRED, $lock];
    }
}
