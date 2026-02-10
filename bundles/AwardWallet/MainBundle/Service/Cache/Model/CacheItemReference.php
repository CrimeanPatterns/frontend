<?php

namespace AwardWallet\MainBundle\Service\Cache\Model;

use AwardWallet\MainBundle\Service\Cache\DataProviderInterface;
use Duration\Duration;

class CacheItemReference
{
    public const OPTION_NO_OPTIONS = 0;
    public const OPTION_GZIP = 1 << 0;
    public const OPTION_SERIALIZE_PHP = 1 << 1;
    public const OPTION_SERIALIZE_BINARY = 1 << 2;
    public const OPTION_GZIP_AUTO = 1 << 3;
    public const OPTION_RETURN_MAP = 1 << 4;

    public const OPTION_DEFAULT = self::OPTION_SERIALIZE_PHP;
    /**
     * @var string
     */
    private $keys;
    /**
     * @var array
     */
    private $tags;

    private $dataProvider;
    /**
     * @var callable
     */
    private $serializer;
    /**
     * @var callable
     */
    private $deserializer;
    /**
     * @var int
     */
    private $options;
    /**
     * @var int seconds
     */
    private $expiration;
    private ?float $stampedeMitigationBeta = null;
    private ?Duration $lockTtl = null;
    private ?Duration $lockSleepInLoopInterval = null;
    private bool $isForce = false;

    /**
     * @param string|string[] $key
     */
    public function __construct($key, array $tags, $dataProvider, ?callable $serializer = null, ?callable $deserializer = null, $options = self::OPTION_NO_OPTIONS)
    {
        $this->keys = (array) $key;

        if (!$this->keys) {
            throw new \InvalidArgumentException('Keys must not be empty');
        }

        $this->tags = $tags;
        $this->dataProvider = $dataProvider;
        $this->deserializer = $deserializer;
        $this->options = $options;
        $this->serializer = $serializer;
    }

    public function getDataProvider()
    {
        return $this->dataProvider;
    }

    public function loadData(array $missingKeysList)
    {
        return
            $this->dataProvider instanceof DataProviderInterface ?
                $this->dataProvider->getData($missingKeysList) :
                (\is_callable($this->dataProvider) ?
                    ($this->dataProvider)($missingKeysList) :
                    $this->dataProvider
                );
    }

    /**
     * @return string[]
     */
    public function getKeys()
    {
        return $this->keys;
    }

    /**
     * @return string[]
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @return bool
     */
    public function hasOption($option)
    {
        return (bool) ($option & $this->options);
    }

    /**
     * @return int
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param int $options
     * @return CacheItemReference
     */
    public function setOptions($options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @return callable
     */
    public function getDeserializer()
    {
        return $this->deserializer;
    }

    /**
     * @param callable $deserializer
     * @return CacheItemReference
     */
    public function setDeserializer($deserializer)
    {
        $this->deserializer = $deserializer;

        return $this;
    }

    /**
     * @return callable
     */
    public function getSerializer()
    {
        return $this->serializer;
    }

    /**
     * @param callable $serializer
     * @return CacheItemReference
     */
    public function setSerializer($serializer)
    {
        $this->serializer = $serializer;

        return $this;
    }

    /**
     * @return int
     */
    public function getExpiration()
    {
        return $this->expiration;
    }

    /**
     * in seconds.
     */
    public function setExpiration($expiration)
    {
        $this->expiration = $expiration;

        return $this;
    }

    /**
     * probability of cache recalculation, higher is more probability, range 0 ... inf.
     */
    public function setStampedeMitigationBeta(?float $beta): self
    {
        $this->stampedeMitigationBeta = $beta;

        return $this;
    }

    public function getStampedeMitigationBeta(): ?float
    {
        return $this->stampedeMitigationBeta;
    }

    public function getLockTtl(): ?Duration
    {
        return $this->lockTtl;
    }

    /**
     * https://symfony.com/doc/4.x/components/cache.html#stampede-prevention
     * set not non-zero value to enable locking, single-threaded value caclulation
     * how much time we expect cache value to be calculated, we guarantee that no other thread will calculate this value for this time.
     */
    public function setLockTtl(Duration $lockTtl): self
    {
        $this->lockTtl = $lockTtl;

        return $this;
    }

    public function getLockSleepInLoopInterval(): ?Duration
    {
        return $this->lockSleepInLoopInterval;
    }

    /**
     * used when setLockTtl is not zero, with what interval we will try to acquire lock.
     */
    public function setLockSleepInLoopInterval(?Duration $lockSleepInLoopInterval): self
    {
        $this->lockSleepInLoopInterval = $lockSleepInLoopInterval;

        return $this;
    }

    public function isForce(): bool
    {
        return $this->isForce;
    }

    public function setForce(bool $isForce): self
    {
        $this->isForce = $isForce;

        return $this;
    }
}
