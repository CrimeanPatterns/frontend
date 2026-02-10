<?php

namespace AwardWallet\MainBundle\Globals\Utils;

use AwardWallet\Common\Memcached\Noop;
use AwardWallet\Common\Memcached\Util as MemcachedUtil;
use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use Duration\Duration;

/**
 * @NoDI
 */
class ConcurrentArray implements \ArrayAccess, \IteratorAggregate, \Countable
{
    private MemcachedUtil $memcachedUtil;
    private string $cacheKey;
    private ?array $data = null;
    private \Memcached $memcached;
    private Duration $cacheTtl;

    public function __construct(MemcachedUtil $memcachedUtil, \Memcached $memcached, string $cacheKey, Duration $cacheTtl)
    {
        $this->memcachedUtil = $memcachedUtil;
        $this->cacheKey = $cacheKey;
        $this->memcached = $memcached;
        $this->cacheTtl = $cacheTtl;
    }

    public function getIterator()
    {
        $this->load();

        return new \ArrayIterator($this->data);
    }

    public function offsetExists($offset)
    {
        $this->load();

        return \array_key_exists($offset, $this->data);
    }

    public function offsetGet($offset)
    {
        $this->load();

        return $this->data[$offset];
    }

    /**
     * @param callable(array): array|Noop $updater
     */
    public function update(callable $updater): bool
    {
        return $this->memcachedUtil->update(
            $this->cacheKey,
            function (?array $data) use ($updater) {
                if (!\is_array($data)) {
                    $data = [];
                }

                return $updater($data);
            },
            $this->cacheTtl->getAsSecondsInt(),
            100
        );
    }

    public function offsetSet($offset, $value)
    {
        $this->update(function (array $data) use ($offset, $value) {
            if (null === $offset) {
                $data[] = $value;
            } else {
                $data[$offset] = $value;
            }

            return $data;
        });
    }

    public function offsetUnset($offset)
    {
        $this->update(function (array $data) use ($offset) {
            unset($data[$offset]);

            return $data;
        });
    }

    public function count()
    {
        $this->load();

        return \count($this->data);
    }

    public function all(): array
    {
        $this->load();

        return $this->data;
    }

    public function load(): void
    {
        $data = $this->memcached->get($this->cacheKey);

        if (\Memcached::RES_SUCCESS === $this->memcached->getResultCode()) {
            $this->data = $data;
        } else {
            $this->data = [];
        }
    }
}
