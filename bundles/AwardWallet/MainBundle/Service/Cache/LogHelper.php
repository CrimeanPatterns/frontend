<?php

namespace AwardWallet\MainBundle\Service\Cache;

use AwardWallet\MainBundle\Service\Cache\Model\CacheItem;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class LogHelper
{
    /**
     * General Memcached error, server malfunction, network partition etc.
     */
    public const ERROR_STATE_CACHE = 'cache';
    /**
     * Error occured during deserialization.
     */
    public const ERROR_STATE_UNSERIALIZATION = 'unserialization';
    /**
     * Error occured during serialization.
     */
    public const ERROR_STATE_SERIALIZATION = 'serialization';
    /**
     * More than one process trying to write to cache.
     */
    public const ERROR_STATE_DATARACE = 'datarace';
    /**
     * Can not invalidate entry.
     */
    public const ERROR_STATE_INVALIDATION = 'invalidation';

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function logMiss($key, $time, array $itemStack, array $mixin = [])
    {
        $this->log('Cache miss', $key, 'miss', $time, $mixin, $itemStack, Logger::DEBUG);
    }

    public function logHit($key, $time, array $itemStack, array $mixin = [])
    {
        $this->log('Cache hit', $key, 'hit', $time, $mixin, $itemStack, Logger::DEBUG);
    }

    public function log($text, $key, $state, $time = null, array $mixin = [], array $itemStack = [], $logLevel = Logger::INFO)
    {
        $this->logger->log($logLevel,
            $text,
            array_merge(
                $mixin,
                [
                    'key' => $key,
                    'state' => $state,
                    'module' => 'cache',
                ],
                null !== $time ? [
                    'time' => (int) $time, // milliseconds
                ] : [],
                $itemStack ? ['keyStack' => $this->getItemStackKeys($itemStack)] : []
            )
        );
    }

    public function logFail($text, $key, $state, array $itemStack = [], array $mixin = [])
    {
        $this->log($text, $key, $state, null, array_merge($mixin, ['fail' => true]), $itemStack, Logger::WARNING);
    }

    /**
     * @param CacheItem[] $stack
     * @return string[]
     */
    protected function getItemStackKeys(array $stack)
    {
        return array_map(function (CacheItem $item) {
            return $item->getKey();
        }, $stack);
    }
}
