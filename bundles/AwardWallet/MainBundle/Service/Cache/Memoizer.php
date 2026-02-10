<?php

namespace AwardWallet\MainBundle\Service\Cache;

use AwardWallet\MainBundle\Service\Cache\Model\CacheItemReference;
use Duration\Duration;
use Psr\Log\LoggerInterface;

/**
 * Memoize function calls by storing return values in cache with key derived from arguments.
 *
 * Please note that function arguments should be serializable via build-in \serialize
 *
 * Examples:
 *
 *     $arg1 = someArgumentComputation();
 *     $result = $memoizer->memoize("some_heavy_copmutation_id_1", function ($arg1) {
 *             return someHeavyComputation($arg1);
 *         },
 *         $arg1
 *     );
 */
class Memoizer
{
    protected CacheManager $cacheManager;

    protected LoggerInterface $logger;

    public function __construct(CacheManager $cacheManager, LoggerInterface $statLogger)
    {
        $this->cacheManager = $cacheManager;
        $this->logger = $statLogger;
    }

    /**
     * @param int|Duration $ttl
     */
    public function memoize(string $callerId, $ttl, callable $dataProvider, ...$dataProviderArgs)
    {
        return $this->doMemoize(
            $this->prepareReference(
                $callerId,
                \is_int($ttl) ? $ttl : $ttl->getAsSecondsInt(),
                $dataProvider,
                ...$dataProviderArgs
            )
        );
    }

    /**
     * @param int|Duration $ttl
     */
    public function memoizeWithLog(string $callerId, $ttl, callable $dataProvider, ...$dataProviderArgs)
    {
        return $this->doMemoize(
            $this->prepareReference(
                $callerId,
                \is_int($ttl) ? $ttl : $ttl->getAsSecondsInt(),
                $dataProvider,
                ...$dataProviderArgs
            )
            ->setDeserializer(function ($data) use ($callerId) {
                $this->logger->info("Memoizer hit: {$callerId}", [
                    'module' => 'memoizer',
                    'callerId' => $callerId,
                    'hit' => true,
                ]);

                return $data;
            })
            ->setSerializer(function ($data) use ($callerId) {
                $this->logger->info("Memoizer miss: {$callerId}", [
                    'module' => 'memoizer',
                    'callerId' => $callerId,
                    'miss' => true,
                ]);

                return $data;
            })
        );
    }

    protected function doMemoize(CacheItemReference $cacheItemReference)
    {
        return $this->cacheManager->load($cacheItemReference);
    }

    protected function prepareReference(string $callerId, int $ttl, callable $dataProvider, ...$dataProviderArgs): CacheItemReference
    {
        return (new CacheItemReference(
            sprintf(
                "%s_%d_%s",
                $callerId,
                $ttl,
                hash('sha512', @serialize($dataProviderArgs))
            ),
            [],
            function () use ($dataProvider, $dataProviderArgs) {
                return $dataProvider(...$dataProviderArgs);
            }
        ))
            ->setExpiration($ttl)
            ->setOptions(CacheItemReference::OPTION_GZIP_AUTO);
    }
}
