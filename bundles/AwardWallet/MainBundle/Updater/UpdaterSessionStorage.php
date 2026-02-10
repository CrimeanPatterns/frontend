<?php

namespace AwardWallet\MainBundle\Updater;

use AwardWallet\Common\Memcached;
use AwardWallet\Common\Memcached\Noop;
use Clock\ClockInterface;
use Duration\Duration;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function Duration\seconds;

class UpdaterSessionStorage
{
    private const CACHE_TTL_SECONDS = 12 * 60 * 60; // 12 hours

    private Memcached\Util $memcachedUtil;
    private ClockInterface $clock;
    private Duration $UPDATER_SESSION_TTL;
    private \Memcached $memcached;

    public function __construct(
        Memcached\Util $memcachedUtil,
        \Memcached $memcached,
        ClockInterface $clock
    ) {
        $this->UPDATER_SESSION_TTL = seconds(UpdaterSession::UPDATER_SESSION_TTL_SECONDS);
        $this->memcachedUtil = $memcachedUtil;
        $this->clock = $clock;
        $this->memcached = $memcached;
    }

    /**
     * @param list<int>|list<AddAccount> $accountIds
     */
    public function linkUpdaterSessionToAccounts(array $accountIds, string $sessionKey): void
    {
        if (\count($accountIds) > UpdaterSessionManager::MAX_ADDED_ACCOUNTS_COUNT) {
            throw new \RuntimeException('Too much accounts added!');
        }

        foreach ($accountIds as $accountId) {
            if ($accountId instanceof AddAccount) {
                $accountId = $accountId->getAccountId();
            }

            $this->linkUpdaterSessionToCacheKey(self::createSessionsMapByAccountCacheKey($accountId), $sessionKey);
        }
    }

    public function linkUpdaterSessionToUser(int $userId, string $sessionKey): void
    {
        $this->linkUpdaterSessionToCacheKey(self::createSessionsMapByUserCacheKey($userId), $sessionKey);
    }

    public function loadSessionsMapByAccount(int $accountId): array
    {
        return $this->loadSessionsMapByCacheKey(self::createSessionsMapByAccountCacheKey($accountId));
    }

    public function loadSessionMapByUser(int $userId): array
    {
        return $this->loadSessionsMapByCacheKey(self::createSessionsMapByUserCacheKey($userId));
    }

    public function removeSession(string $sessionKey): void
    {
        $this->memcached->delete(self::createSessionCacheKey($sessionKey));
    }

    public function loadSessionData(string $sessionKey): ?SessionData
    {
        $metadata = $this->memcached->get(self::createSessionCacheKey($sessionKey));

        if ($this->memcached->getResultCode() === \Memcached::RES_SUCCESS) {
            return $metadata;
        }

        return null;
    }

    public function updateSessionData(string $sessionKey, SessionData $sessionData): void
    {
        $this->memcached->set(
            self::createSessionCacheKey($sessionKey),
            $sessionData,
            self::CACHE_TTL_SECONDS
        );
    }

    /**
     * @param int[] $accountIds
     */
    public function unlinkUpdaterSessionFromAccounts(array $accountIds, string $sessionKey): void
    {
        foreach ($accountIds as $accountId) {
            $this->memcachedUtil->update(
                self::createSessionsMapByAccountCacheKey($accountId),
                /**
                 * @param array|null $sessionsMap [session key => session expiration date] map
                 * @return array|Noop
                 */
                function (?array $sessionsMap) use ($sessionKey) {
                    if (!\is_array($sessionsMap) || !isset($sessionsMap[$sessionKey])) {
                        return Noop::getInstance();
                    }

                    unset($sessionsMap[$sessionKey]);

                    return $sessionsMap;
                },
                self::CACHE_TTL_SECONDS
            );
        }
    }

    protected function linkUpdaterSessionToCacheKey(string $cacheKey, string $sessionKey): void
    {
        $this->memcachedUtil->update(
            $cacheKey,
            /**
             * @param array<string, Duration>|null $sessionsMap [session key => session expiration date] map
             * @return array<string, Duration>|Noop
             */
            function (?array $sessionsMap) use ($sessionKey) {
                if (!\is_array($sessionsMap)) {
                    $sessionsMap = [];
                }

                $sessionExpirationDate = $this->clock->current()->add($this->UPDATER_SESSION_TTL);

                // last tick time must be increased only
                if (
                    !isset($sessionsMap[$sessionKey])
                    || $sessionsMap[$sessionKey]->lessThan($sessionExpirationDate)
                ) {
                    $sessionsMap[$sessionKey] = $sessionExpirationDate;
                } else {
                    return Noop::getInstance();
                }

                return $sessionsMap;
            },
            self::CACHE_TTL_SECONDS
        );
    }

    protected function loadSessionsMapByCacheKey(string $cacheKey): array
    {
        $sessionsMap = $this->memcached->get($cacheKey);

        if (!\is_array($sessionsMap)) {
            return [];
        }

        $aliveSessionsMap = $this->filterAlive($sessionsMap);

        if (\count($sessionsMap) !== \count($aliveSessionsMap)) {
            $this->memcachedUtil->update(
                $cacheKey,
                fn (?array $sessionsMap) => \is_array($sessionsMap) ?
                    $this->filterAlive($sessionsMap) :
                    Noop::getInstance(),
                self::CACHE_TTL_SECONDS,
                5 // invalidate retries
            );
        }

        return $aliveSessionsMap;
    }

    private static function createSessionsMapByAccountCacheKey(int $accountId): string
    {
        return 'updater_sessions_for_account_v1_' . $accountId;
    }

    private static function createSessionsMapByUserCacheKey(int $userId): string
    {
        return 'updater_sessions_for_user_v1_' . $userId;
    }

    private static function createSessionCacheKey(string $sessionId): string
    {
        return 'updater_session_V1_' . $sessionId;
    }

    private function filterAlive(array $sessionsMap): array
    {
        $currentTime = $this->clock->current();

        return
            it($sessionsMap)
            ->filter(fn (Duration $expDate) => $expDate->greaterThan($currentTime))
            ->toArrayWithKeys();
    }
}
