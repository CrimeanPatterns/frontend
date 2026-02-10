<?php

namespace AwardWallet\MainBundle\Service\MileValue;

use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Model\CacheItemReference;
use AwardWallet\MainBundle\Service\Cache\Tags;
use Clock\ClockInterface;
use Psr\Log\LoggerInterface;

use function Duration\milliseconds;
use function Duration\seconds;

class MileValueCache
{
    public const CACHE_KEY = 'MilePointValues_v15';
    private const CACHE_LIFETIME = 10800;

    private LoggerInterface $logger;
    private CacheManager $cacheManager;
    private ClockInterface $clock;

    public function __construct(
        LoggerInterface $logger,
        CacheManager $cacheManager,
        ClockInterface $clock
    ) {
        $this->logger = $logger;
        $this->cacheManager = $cacheManager;
        $this->clock = $clock;
    }

    public function get($cacheKey, $callable, $isForce = false)
    {
        $cacheRef = new CacheItemReference($cacheKey, [Tags::TAG_MILE_VALUE], function () use ($cacheKey, $callable) {
            $result = null;
            $elapsed = $this->clock->stopwatch(function () use (&$result, $callable) {
                $result = $callable();
            });
            $this->logger->info('MileValueCache get', [
                'cacheKey' => $cacheKey,
                'computeTimeMs' => $elapsed->getAsMillisInt(),
            ]);

            return $result;
        });

        $cacheRef
            ->setStampedeMitigationBeta(100.0)
            ->setLockTtl(seconds(45))
            ->setLockSleepInLoopInterval(milliseconds(100))
            ->setExpiration(self::CACHE_LIFETIME)
            ->setForce($isForce);

        $result = $this->cacheManager->load($cacheRef);
        $this->logger->info("MileValueCache get $cacheKey: null " . json_encode(is_null($result)));

        return $result;
    }

    public function clear(string $cacheKey)
    {
        // $this->cacheManager->invalidateTags([Tags::TAG_MILE_VALUE]);
    }
}
