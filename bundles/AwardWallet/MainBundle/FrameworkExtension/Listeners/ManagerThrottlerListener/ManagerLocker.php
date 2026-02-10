<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Listeners\ManagerThrottlerListener;

use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;

use function Duration\hours;

class ManagerLocker
{
    private \Memcached $memcached;
    private AntiBruteforceLockerService $throttlerTier1;
    private AntiBruteforceLockerService $throttlerTier2;
    private AntiBruteforceLockerService $throttlerTier3;
    private AntiBruteforceLockerService $throttlerTier4;

    public function __construct(
        \Memcached $memcached,
        AntiBruteforceLockerService $throttlerTier1,
        AntiBruteforceLockerService $throttlerTier2,
        AntiBruteforceLockerService $throttlerTier3,
        AntiBruteforceLockerService $throttlerTier4
    ) {
        $this->memcached = $memcached;
        $this->throttlerTier1 = $throttlerTier1;
        $this->throttlerTier2 = $throttlerTier2;
        $this->throttlerTier3 = $throttlerTier3;
        $this->throttlerTier4 = $throttlerTier4;
    }

    public function lock(int $userId): void
    {
        $this->memcached->set(
            self::generateCacheKey($userId),
            true,
            hours(48)->getAsSecondsInt()
        );
    }

    public function isLocked(int $userId): bool
    {
        $result = $this->memcached->get(self::generateCacheKey($userId));

        if ($this->memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
            return false;
        }

        return $result;
    }

    public function unlock(int $userId): void
    {
        $this->memcached->delete(self::generateCacheKey($userId));

        foreach (
            [
                $this->throttlerTier1,
                $this->throttlerTier2,
                $this->throttlerTier3,
                $this->throttlerTier4,
            ] as $throttler
        ) {
            $throttler->unlock((string) $userId);
        }
    }

    private static function generateCacheKey(int $userId): string
    {
        return 'manager_locker_v2_' . $userId;
    }
}
