<?php

namespace AwardWallet\MainBundle\Updater;

class ExtensionV3SessionMap
{
    private \Memcached $memcached;

    public function __construct(\Memcached $memcached)
    {
        $this->memcached = $memcached;
    }

    public function getAccountId(string $sessionId): ?int
    {
        $accountId = $this->memcached->get(self::getSessionToAccountCacheKey($sessionId));

        if ($accountId === false) {
            return null;
        }

        return $accountId;
    }

    public function setAccountId(string $sessionId, int $accountId): void
    {
        $this->memcached->set(self::getSessionToAccountCacheKey($sessionId), $accountId, 3600);
    }

    private static function getSessionToAccountCacheKey(string $sessionId): string
    {
        return 'v3session_to_acc_' . $sessionId;
    }
}
