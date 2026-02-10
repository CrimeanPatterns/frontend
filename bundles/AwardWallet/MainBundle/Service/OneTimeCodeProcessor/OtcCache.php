<?php

namespace AwardWallet\MainBundle\Service\OneTimeCodeProcessor;

use AwardWallet\Common\OneTimeCode\CommonProvider;

class OtcCache
{
    public const DATA_TTL = 15 * 60;
    public const CHECK_TTL = 15 * 60;
    private const TEMP_ACCOUNT_PASSWORD_MASK = 'otc_temp_password_account_%d';
    private const PROVIDER_OTC_MASK = 'otc_prov_code_%d_%s';
    private const OTC_COLLISION_MASK = 'otc_code_colli_%d_%s';
    private const AUTO_MASK = 'otc_check_auto_%d';
    private const AUTO_REQUEST_ID_MASK = 'otc_check_auto_request_id_%s';
    private const NEXT_REQUEST_ID_MASK = 'otc_next_request_id_%s';
    private const CHECK_MASK = 'otc_check_%d';
    private const UPDATE_MASK = 'otc_update_%d';
    private const STOP_MASK = 'otc_stop_%d_%s';

    private \Memcached $cache;

    public function __construct(\Memcached $cache)
    {
        $this->cache = $cache;
    }

    // storing otc from email
    public function setProviderOtc(int $userId, string $provider, string $code): void
    {
        $key = sprintf(self::PROVIDER_OTC_MASK, $userId, $provider);
        $this->cache->set($key, $code, self::DATA_TTL);
    }

    public function getProviderOtc(int $userId, string $provider): ?string
    {
        foreach (CommonProvider::getCodesList($provider) as $providerCode) {
            $key = sprintf(self::PROVIDER_OTC_MASK, $userId, $providerCode);

            if ($code = $this->cache->get($key)) {
                return $code;
            }
        }

        return null;
    }

    public function dropProviderOtc(int $userId, string $provider): void
    {
        foreach (CommonProvider::getCodesList($provider) as $providerCode) {
            $key = sprintf(self::PROVIDER_OTC_MASK, $userId, $providerCode);
            $this->cache->delete($key);
        }
    }

    // received two or more codes from emails
    public function setCodeCollision(int $userId, string $provider): void
    {
        $key = sprintf(self::OTC_COLLISION_MASK, $userId, $provider);
        $this->cache->set($key, 1, self::DATA_TTL);
    }

    public function hasCodeCollision(int $userId, string $provider): bool
    {
        foreach (CommonProvider::getCodesList($provider) as $providerCode) {
            $key = sprintf(self::OTC_COLLISION_MASK, $userId, $providerCode);

            if ($this->cache->get($key)) {
                return true;
            }
        }

        return false;
    }

    public function setStop(int $userId, string $provider): void
    {
        $key = sprintf(self::STOP_MASK, $userId, $provider);
        $this->cache->set($key, 1, self::CHECK_TTL);
    }

    public function hasStop(int $userId, string $provider): bool
    {
        foreach (CommonProvider::getCodesList($provider) as $providerCode) {
            $key = sprintf(self::STOP_MASK, $userId, $providerCode);

            if ($this->cache->get($key)) {
                return true;
            }
        }

        return false;
    }

    public function clearStop(int $userId, string $providerId): void
    {
        $key = sprintf(self::STOP_MASK, $userId, $providerId);
        $this->cache->delete($key);
    }

    // auto checked with received otc
    public function setAutoCheck(int $accountId): void
    {
        $key = sprintf(self::AUTO_MASK, $accountId);
        $this->cache->set($key, time(), self::CHECK_TTL);
    }

    public function getAutoCheck(int $accountId): ?int
    {
        $key = sprintf(self::AUTO_MASK, $accountId);
        $ts = $this->cache->get($key);

        return $ts ?: null;
    }

    public function setAutoCheckRequestId(string $requestId): void
    {
        $key = sprintf(self::AUTO_REQUEST_ID_MASK, $requestId);
        $this->cache->set($key, time(), self::CHECK_TTL);
    }

    public function getAutoCheckRequestId(string $requestId): ?int
    {
        $key = sprintf(self::AUTO_REQUEST_ID_MASK, $requestId);
        $ts = $this->cache->get($key);

        return $ts ?: null;
    }

    public function setNextRequestId(string $oldRequestId, string $newRequestId): void
    {
        $key = sprintf(self::NEXT_REQUEST_ID_MASK, $oldRequestId);
        $this->cache->set($key, $newRequestId, self::CHECK_TTL);
    }

    public function getNextRequestId(string $oldRequestId): ?string
    {
        $key = sprintf(self::NEXT_REQUEST_ID_MASK, $oldRequestId);
        $requestId = $this->cache->get($key);

        return $requestId ?: null;
    }

    // any check
    public function setCheck(int $accountId): void
    {
        $key = sprintf(self::CHECK_MASK, $accountId);
        $this->cache->set($key, time(), self::CHECK_TTL);
    }

    public function getCheck(int $accountId): ?int
    {
        $key = sprintf(self::CHECK_MASK, $accountId);
        $ts = $this->cache->get($key);

        return $ts ?: null;
    }

    // account was updated with otc question
    public function setUpdate(int $accountId): void
    {
        $key = sprintf(self::UPDATE_MASK, $accountId);
        $this->cache->set($key, time(), self::CHECK_TTL);
    }

    public function getUpdate(int $accountId): ?int
    {
        $key = sprintf(self::UPDATE_MASK, $accountId);
        $ts = $this->cache->get($key);

        return $ts ?: null;
    }

    public function setTempLocalPassword(int $accountId, string $password): self
    {
        $key = sprintf(self::TEMP_ACCOUNT_PASSWORD_MASK, $accountId);
        $this->cache->set($key, $password, self::CHECK_TTL);

        return $this;
    }

    public function getTempLocalPassword(int $accountId)
    {
        $key = sprintf(self::TEMP_ACCOUNT_PASSWORD_MASK, $accountId);

        return $this->cache->get($key);
    }
}
