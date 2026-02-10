<?php

namespace AwardWallet\MainBundle\Globals;

class ThrottlerCounter
{
    public const DEFAULT_LIMIT_SUCCESS = 2;
    public const DEFAULT_LIMIT_FAIL = 1;
    public const PREFIX_KEY = 'throttlerCnt_';

    private \Memcached $cache;
    private int $expirationTime;
    private int $limit;
    private int $failAllowLimit;

    private ?array $conditions = [];

    public function __construct(
        \Memcached $cache,
        int $expirationTime = 86400,
        ?int $limit = null,
        ?int $failAllowLimit = null
    ) {
        $this->cache = $cache;
        $this->expirationTime = $expirationTime;

        $this->limit = $limit ?? self::DEFAULT_LIMIT_SUCCESS;
        $this->failAllowLimit = $failAllowLimit ?? self::DEFAULT_LIMIT_FAIL;
    }

    public function throttle(string $key): bool
    {
        $counter = $this->getCounters($key);

        if ($counter['counter'] >= $this->limit) {
            if (!empty($this->conditions)) {
                foreach ($this->conditions as $condition) {
                    $success = (int) ($condition['success'] ?? 0);
                    $failure = (int) ($condition['failure'] ?? 0);

                    // ['success' => 1, 'failure' => 5],
                    if ($success && $counter['success'] >= $success && $failure && $counter['failure'] >= $failure) {
                        return true;
                    }

                    // ['success' => 2],
                    if ($success && $counter['success'] >= $success && 0 === $failure) {
                        return true;
                    }

                    // ['failure' => 100],
                    if (0 === $success && $failure && $counter['failure'] >= $failure) {
                        return true;
                    }
                }

                return false;
            } elseif ($this->failAllowLimit && $counter['failure']
                && $this->failAllowLimit >= $counter['failure']
                && ($counter['counter'] - $this->failAllowLimit < $this->limit)
            ) {
                return false;
            }

            return true;
        }

        if ($this->limit > $counter['counter']) {
            return false;
        }

        return true;
    }

    public function getCounters(string $key): array
    {
        $result = [
            'counter' => 0,
            'success' => 0,
            'failure' => 0,
        ];
        $data = $this->fetchData($key);

        foreach ($data as $value) {
            if (true === $value) {
                $result['success']++;
                $result['counter']++;
            } elseif (false === $value) {
                $result['failure']++;
                $result['counter']++;
            } elseif (\is_int($value) && $value > 0) {
                $result['counter'] += $value;
            }
        }

        return $result;
    }

    public function getDelay(string $key): int
    {
        if (!$this->throttle($key)) {
            return 0;
        }
        $data = $this->fetchData($key);
        reset($data);

        return (int) key($data) - time();
    }

    public function get(string $key): array
    {
        return $this->fetchData($key);
    }

    public function set(string $key, array $data): array
    {
        $this->cache->set(self::PREFIX_KEY . $key, $data, $this->expirationTime);

        return $data;
    }

    public function clear(string $key): bool
    {
        return $this->cache->delete($key);
    }

    public function success(string $key): array
    {
        $data = $this->fetchData($key);
        $data[(string) (microtime(true) + $this->expirationTime)] = true;

        return $this->set($key, $data);
    }

    public function failure(string $key): array
    {
        $data = $this->fetchData($key);
        $data[(string) (microtime(true) + $this->expirationTime)] = false;

        return $this->set($key, $data);
    }

    public function increment(string $key, int $increase = 1): array
    {
        $data = $this->fetchData($key);
        $data[(string) (microtime(true) + $this->expirationTime)] = $increase;

        return $this->set($key, $data);
    }

    public function setConditions(array $conditions): self
    {
        $this->conditions = $conditions;

        return $this;
    }

    public function getConditions(): array
    {
        return $this->conditions;
    }

    private function expired(string $key, array $data): array
    {
        $clear = false;

        foreach ($data as $expired => $item) {
            if (time() >= (float) $expired) {
                unset($data[$expired]);
                $clear = true;
            }
        }

        if ($clear) {
            $this->set($key, $data);
        }

        return $data;
    }

    private function fetchData(string $key): array
    {
        $data = $this->cache->get(self::PREFIX_KEY . $key);

        if (empty($data) || !is_array($data)) {
            return [];
        }

        return $this->expired($key, $data);
    }
}
