<?php

namespace AwardWallet\MainBundle\Security;

use AwardWallet\MainBundle\Globals\StringUtils;
use Psr\Log\LoggerInterface;

class AntiBruteforceLockerService
{
    public $errorMessage;

    public $prefix;

    /**
     * @var \Throttler;
     */
    private $throttler;
    /**
     * @var \Memcached
     */
    private $memcached;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        \Memcached $memcached,
        $prefix,
        $periodSeconds,
        $periods,
        $maxAttempts,
        $errorMessage,
        ?LoggerInterface $logger = null
    ) {
        $this->errorMessage = $errorMessage;
        $this->memcached = $memcached;
        $this->logger = $logger;
        $this->init($prefix, $periodSeconds, $periods, $maxAttempts);
    }

    /**
     * this function made public for tests.
     */
    public function init($prefix, $periodSeconds, $periods, $maxAttempts)
    {
        $this->prefix = $prefix;
        $this->throttler = new \Throttler($this->memcached, $periodSeconds, $periods, $maxAttempts);
    }

    /**
     * check for lockout, returns null or error message.
     *
     * @param bool $readOnly
     * @return string|null
     */
    public function checkForLockout($key, $readOnly = false, $increment = 1)
    {
        if (!is_string($key) || StringUtils::isEmpty($key)) {
            throw new \InvalidArgumentException("key should be string");
        }

        if ($this->throttler->getDelay($this->prefix . $key, $readOnly, $increment) > 0) {
            if (isset($this->logger)) {
                $this->logger->warning('detected lockout', [
                    'prefix' => $this->prefix,
                    'key' => $key,
                ]);
            }

            return $this->errorMessage;
        } else {
            return null;
        }
    }

    /**
     * reset lockout.
     *
     * @param $key @see $this->checkForLockout $key
     */
    public function unlock($key)
    {
        if (empty($key) || !is_string($key)) {
            throw new \InvalidArgumentException("key should be string");
        }
        $this->throttler->clear($this->prefix . $key);
    }
}
