<?php

namespace AwardWallet\MainBundle\Service\Cache;

use AwardWallet\MainBundle\FrameworkExtension\Error\SimpleLeveledErrorReporter;
use AwardWallet\MainBundle\Service\Cache\Exceptions\CacheDataRaceException;
use AwardWallet\MainBundle\Service\Cache\Exceptions\CacheException;
use AwardWallet\MainBundle\Service\Cache\Exceptions\CacheSerializationException;
use AwardWallet\MainBundle\Service\Cache\Exceptions\CacheUnserializationException;
use AwardWallet\MainBundle\Service\Cache\Exceptions\DoNotStoreException;
use AwardWallet\MainBundle\Service\Cache\Model\CacheItem;
use AwardWallet\MainBundle\Service\Cache\Model\CacheItemReference;
use Duration\Duration;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Lock\Exception\LockReleasingException;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function Duration\seconds;
use function iter\all;

/**
 * Class CacheManager.
 */
class CacheManager
{
    public const ITEM_EXPIRATION = SECONDS_PER_HOUR * 24 * 7;
    public const TAG_EXPIRATION = SECONDS_PER_HOUR * 24 * 7;

    public const ITEM_GET_MAX_RETRY = 4;
    public const ITEM_UPDATE_MAX_RETRY = 4;
    public const ITEM_INVALID_CACHE_MAX_RETRY = 4;
    public const MIN_LENGTH_TO_COMPRESS = 256;

    public const MEMCACHED_MAYBE_RESULT = [
        \Memcached::RES_SUCCESS,
        \Memcached::RES_NOTFOUND,
        \Memcached::RES_NOTSTORED,
    ];

    public static $_unserializeCallback;

    // we consider it to be equal 1 second as it cumbersome to calculate it with current multikey implementation
    private static Duration $TIME_TO_CREATE_ITEM;
    private \Memcached $memcached;
    private LoggerInterface $logger;
    private SimpleLeveledErrorReporter $errorReporter;
    private StampedeProtector $stampedeProtector;
    private LogHelper $logHelper;
    private bool $throwOnSerializationError;
    private bool $debugMode = false;

    public function __construct(
        \Memcached $memcached,
        LoggerInterface $logger,
        SimpleLeveledErrorReporter $errorReporter,
        StampedeProtector $stampedeProtector,
        bool $throwOnSerializationError = false
    ) {
        $this->memcached = $memcached;
        $this->logger = $logger;
        $this->logHelper = new LogHelper($this->logger);
        $this->errorReporter = $errorReporter;
        $this->throwOnSerializationError = $throwOnSerializationError;
        $this->stampedeProtector = $stampedeProtector;

        if (!isset(self::$TIME_TO_CREATE_ITEM)) {
            self::$TIME_TO_CREATE_ITEM = seconds(1);
        }
    }

    public function setDebugMode(bool $enabled): self
    {
        $this->debugMode = $enabled;

        return $this;
    }

    /**
     * Make some tags invalid
     * all entities associated with this tags will be marked as invalid while reading from cache (lazy invalidation).
     *
     * @param string[] $tags
     * @param bool $addPrefix whether to add tag prefix or not
     * @return array<tag, value>
     */
    public function invalidateTags(array $tags, $addPrefix = true, $initOnly = false, $expiration = self::TAG_EXPIRATION)
    {
        if ($addPrefix) {
            $tags = Tags::addTagPrefix($tags);
        }

        $res = [];

        foreach ($tags as $tag) {
            // retry loop
            foreach (range(0, self::ITEM_UPDATE_MAX_RETRY) as $_) {
                if ($initOnly) {
                    $data = $this->memcached->get($tag);

                    $result = $this->memcached->getResultCode();

                    if (\Memcached::RES_SUCCESS === $result) {
                        $this->memcached->touch($tag, $expiration);

                        if (\Memcached::RES_SUCCESS !== $this->memcached->getResultCode()) {
                            continue;
                        }

                        $res[$tag] = $data;

                        continue 2;
                    } elseif (\Memcached::RES_NOTFOUND !== $result) {
                        continue;
                    }
                }

                $inc = $this->memcached->increment($tag, 1, 0, $expiration);

                if (\Memcached::RES_SUCCESS === $this->memcached->getResultCode()) {
                    $res[$tag] = $inc;

                    continue 2;
                }
            }

            $this->logHelper->logFail($this->getMemcachedError($this->memcached->getResultCode(), $this->memcached->getResultMessage()), $tag, LogHelper::ERROR_STATE_INVALIDATION);
        }

        return $res;
    }

    public function invalidateGlobalTags(array $tags)
    {
        return $this->invalidateTags($tags, true, false, Tags::GLOBAL_TAGS_EXPIRATION);
    }

    /**
     * Load data from cache and update item if not validated.
     *
     * @throws \Exception
     */
    public function load(CacheItemReference $reference)
    {
        $data = new ParameterBag();

        try {
            $this->doLoad($reference, $data);

            return $this->returnData($data, $reference);
        } catch (DoNotStoreException $exception) {
            return $exception->getData();
        } catch (CacheUnserializationException $e) {
            if ($this->throwOnSerializationError) {
                throw $e;
            } else {
                $this->errorReporter->logThrowable($e, Logger::INFO);
            }

            foreach ($reference->getKeys() as $key) {
                $this->memcached->delete($key);
            }

            return $reference->loadData($reference->getKeys());
        } catch (CacheSerializationException $e) {
            if ($this->throwOnSerializationError) {
                throw $e;
            } else {
                $this->errorReporter->logThrowable($e, Logger::INFO);
            }

            return $this->returnData($data, $reference);
        } catch (CacheException $e) {
            $this->errorReporter->logThrowable($e, Logger::INFO);

            return $reference->loadData($reference->getKeys());
        }
    }

    private function returnData(ParameterBag $data, CacheItemReference $cacheItemReference)
    {
        $data = $data->all();

        if (
            (\count($cacheItemReference->getKeys()) > 1)
            || $cacheItemReference->hasOption(CacheItemReference::OPTION_RETURN_MAP)
        ) {
            return $data;
        }

        return \current($data);
    }

    /**
     * Load data from cache, try CAS several times for store.
     *
     * @throws CacheSerializationException
     * @throws CacheUnserializationException
     * @throws \UnexpectedValueException
     * @throws \Exception
     */
    private function doLoad(CacheItemReference $cacheItemReference, ParameterBag $loadedData, int $try = 1)
    {
        $items = $this->getItems($cacheItemReference->getKeys(), $cacheItemReference->getExpiration() ?? self::ITEM_EXPIRATION);
        /** @var CacheItem[] $validItems */
        /** @var CacheItem[] $invalidItems */
        [$validItems, $invalidItems] = $this->splitByValidity($items, $cacheItemReference);

        if ($validItems) {
            // handle errors during serialization
            self::$_unserializeCallback = \ini_get('unserialize_callback_func');
            \ini_set('unserialize_callback_func', __NAMESPACE__ . '\unserializeHelper');

            try {
                foreach ($validItems as $validItem) {
                    $data = $validItem->getData();

                    if ($validItem->hasFlag(CacheItem::FLAG_GZIP)) {
                        $compLength = \strlen($data);
                        $uncompData = @\gzdecode($data);

                        if (false === $uncompData) {
                            throw new \RuntimeException(sprintf('gzdecode() failed. Compressed data length: %d byte(s). base64(data[0..9]): "%s"', $compLength, base64_encode(substr($data, 0, 10))));
                        } else {
                            $data = $uncompData;
                        }
                    }

                    $data = \unserialize($data);

                    if (null !== ($deserializer = $cacheItemReference->getDeserializer())) {
                        $data = $deserializer($data);
                    }

                    $loadedData->set($validItem->getKey(), $data);
                }
            } catch (\Throwable $e) {
                if ($e instanceof CacheUnserializationException) {
                    throw $e;
                } else {
                    throw new CacheUnserializationException($e->getMessage(), $e->getCode(), $e);
                }
            } finally {
                \ini_set('unserialize_callback_func', self::$_unserializeCallback);
            }
        }

        if ($invalidItems) {
            if (null !== $cacheItemReference->getLockTtl() && ($try <= 1)) {
                $lockAcquirer = new LockAcquirer($this->memcached, $cacheItemReference);
                [$status, $lock] = $lockAcquirer->tryAcquire();
            } else {
                $status = LockAcquirer::NO_LOCK;
                $lock = null;
            }

            if (LockAcquirer::NEED_RELOAD === $status) {
                $this->doLoad($cacheItemReference, $loadedData, $try + 1);
            } else {
                try {
                    $this->cas($invalidItems, $cacheItemReference, $loadedData);
                } finally {
                    if ($lock) {
                        try {
                            $lock->release();
                        } catch (LockReleasingException $e) {
                            // ignore
                        }
                    }
                }
            }
        }
    }

    /**
     * Get an item or create a new one.
     *
     * @param string[] $keys
     * @return array<string, CacheItem> key to item map
     * @throws CacheDataRaceException
     * @throws CacheException
     * @throws \UnexpectedValueException
     */
    private function getItems(array $keys, $expiration): array
    {
        /** @var array<array<0 => key, 1 => value, 2 => cas>> $itemsDataPairs */
        $itemsMap = [];
        $multiResult = $this->memcached->getMulti($keys, \Memcached::GET_EXTENDED | \Memcached::GET_PRESERVE_ORDER);
        $this->expectMemcachedResult(self::MEMCACHED_MAYBE_RESULT, 'Failed to load item');
        $ctime = \microtime(true);

        if (\count($keys) !== \count($multiResult)) {
            foreach ($keys as $key) {
                if (!\array_key_exists($key, $multiResult)) {
                    $multiResult[$key] = null;
                }
            }
        }

        foreach ($multiResult as $key => $result) {
            if (\is_array($result)) {
                $itemsMap[$key] = new CacheItem($key, $result['value'], $result['cas'], $expiration);
            } else {
                $itemsMap[$key] = CacheItem::createEmptyItem($key, ['ctime' => $ctime], $expiration);
            }
        }

        return $itemsMap;
    }

    /**
     * Check item tags correctness.
     *
     * @param CacheItem[] $items
     * @return [0 => array<string, CacheItem>, 1 => array<string, CacheItem>] // valid and invalid items mapped by keys
     */
    private function splitByValidity(array $items, CacheItemReference $reference): array
    {
        $referenceTagsList = \array_unique($reference->getTags());
        $referenceTagsMap = \array_flip($referenceTagsList);
        $stampedeBeta = $reference->getStampedeMitigationBeta();

        if (null === $stampedeBeta || $reference->isForce()) {
            $canRecomputeEarly = static fn (CacheItem $item) => $reference->isForce();
        } else {
            $expiration = seconds($reference->getExpiration() ?? self::ITEM_EXPIRATION);
            $canRecomputeEarly = fn (CacheItem $item) => $this->stampedeProtector->canRecomputeEarlyByCreationAndExpiration(
                seconds($item->getCtime()),
                $expiration,
                self::$TIME_TO_CREATE_ITEM,
                $stampedeBeta
            );
        }

        // empty always
        if (
            (null === $stampedeBeta)
            && (\count($referenceTagsList) === 0)
            && it($items)->all(fn (CacheItem $item) =>
                !$item->hasFlag(CacheItem::FLAG_EMPTY)
                && !$canRecomputeEarly($item)
            )
        ) {
            return [/* valid */ $items, /* invalid */ []];
        }

        /*
         * get actual tags according to the reference
         * somehow getMulti returns "garbage" ints
         */
        $actualTagsMap = $this->memcached->getMulti($referenceTagsList);
        $this->expectMemcachedResult(self::MEMCACHED_MAYBE_RESULT, 'Actual tags multi-loading failed');
        $actualTagsMap = \array_map('\\intval', $actualTagsMap);
        $actualTagsList = \array_keys($actualTagsMap);
        $missingReferenceTagsList = \array_diff($referenceTagsList, $actualTagsList);
        $invalidatedMissingReferenceTagsMap = $this->invalidateTags($missingReferenceTagsList, false, false);

        if (\count($invalidatedMissingReferenceTagsMap) !== \count($missingReferenceTagsList)) {
            throw new CacheException('Failed to invalidate missing tags');
        }

        $validMap = [];
        $invalidMap = [];

        foreach ($items as $item) {
            // compute valid tags stored with item
            $cachedTagsMap = \array_intersect_key($item->getTags(), $referenceTagsMap);

            if (
                $canRecomputeEarly($item)
                || (
                    (null === $stampedeBeta)
                    && \count($actualTagsMap) === 0
                )
                || $item->hasFlag(CacheItem::FLAG_EMPTY)
            ) {
                $isValid = false;
            } else {
                $isValid = (\count($missingReferenceTagsList) === 0) && ($actualTagsMap == $cachedTagsMap);
            }

            $item->setTags(\array_merge($actualTagsMap, $invalidatedMissingReferenceTagsMap));

            if ($isValid) {
                $validMap[$item->getKey()] = $item;
            } else {
                $invalidMap[$item->getKey()] = $item;
            }
        }

        return [$validMap, $invalidMap];
    }

    /**
     * Prepare data and perform cache CAS.
     *
     * @param CacheItem[] $items
     * @return array<string, CacheItem> key to item map
     * @throws CacheSerializationException
     * @throws \PDOException
     * @throws \Exception
     */
    private function cas(array $items, CacheItemReference $reference, ParameterBag $loadedData): array
    {
        $serializer = $reference->getSerializer();
        $data = $reference->loadData(it($items)->map(function (CacheItem $item) { return $item->getKey(); })->toArray());
        $referenceKeys = $reference->getKeys();

        if (
            (\count($referenceKeys) === 1)
            && !$reference->hasOption(CacheItemReference::OPTION_RETURN_MAP)
        ) {
            $data = [\current($referenceKeys) => $data];
        }

        if (
            !\is_array($data)
            && !(
                \is_object($data)
                && ($data instanceof \ArrayAccess)
            )
        ) {
            $this->throwInconsistentDataException();
        }

        $storeFailedItemsMap = [];
        $lastSerializationException = null;

        foreach ($items as $key => $item) {
            if (!\array_key_exists($key, $data)) {
                $this->throwInconsistentDataException();
            }

            $datum = $data[$key];
            $loadedData->set($key, $datum);
        }

        foreach ($items as $key => $item) {
            if ($this->debugMode) {
                $this->logHelper->log(
                    'serializing item',
                    $key,
                    'cas',
                    null,
                    [
                        'memory_bytes_float' => \memory_get_usage(true),
                    ]
                );
            }

            $datum = $data[$key];

            try {
                if (null !== $serializer) {
                    $datum = $serializer($datum);
                }

                $datum = \serialize($datum);
                $uncompLength = \strlen($datum);

                if (
                    $reference->hasOption(CacheItemReference::OPTION_GZIP)
                    || ($reference->hasOption(CacheItemReference::OPTION_GZIP_AUTO) && \strlen($datum) >= self::MIN_LENGTH_TO_COMPRESS)
                ) {
                    $datum = \gzencode($datum);

                    if (false === $datum) {
                        throw new \RuntimeException(sprintf('gzencode() failed. Uncompressed data length: %d byte(s)', $uncompLength));
                    }

                    $item->setFlag(CacheItem::FLAG_GZIP);
                } else {
                    $item->unsetFlag(CacheItem::FLAG_GZIP);
                }
            } catch (\Throwable $e) {
                throw new CacheSerializationException('Cache serialization failed for key: "' . $key . '"', 0, $e);
            }

            if ($this->debugMode) {
                $this->logHelper->log(
                    'item serialized',
                    $key,
                    'cas',
                    null,
                    [
                        'memory_bytes_float' => \memory_get_usage(true),
                    ]
                );
            }

            $item->setData($datum);
            $expiration = $item->getExpiration() ?? self::ITEM_EXPIRATION;

            if ($item->hasFlag(CacheItem::FLAG_EMPTY)) {
                $item->unsetFlag(CacheItem::FLAG_EMPTY);

                $this->memcached->add(
                    $item->getKey(),
                    $item->getInternalValue(),
                    $expiration
                );

                if (!\in_array($this->memcached->getResultCode(), [\Memcached::RES_STORED, \Memcached::RES_SUCCESS])) {
                    $storeFailedItemsMap[$item->getKey()] = $item;
                }
            } else {
                $this->memcached->cas(
                    $item->getCasToken(),
                    $item->getKey(),
                    \array_merge(
                        $item->getInternalValue(),
                        ['ctime' => \microtime(true)],
                    ),
                    $expiration
                );

                if (!\in_array($this->memcached->getResultCode(), [\Memcached::RES_STORED, \Memcached::RES_SUCCESS])) {
                    $storeFailedItemsMap[$item->getKey()] = $item;
                }
            }
        }

        return $storeFailedItemsMap;
    }

    /**
     * @param int|int[] $expectedCode expected memcached result code
     * @param string $errorMessage
     * @return int actual result code
     * @throws CacheException
     */
    private function expectMemcachedResult($expectedCode, $errorMessage = '')
    {
        if (!is_array($expectedCode)) {
            $expectedCode = (array) $expectedCode;
        }

        $actualCode = $this->memcached->getResultCode();

        if (!in_array($actualCode, $expectedCode, true)) {
            $memcachedErrorMessage = $this->memcached->getResultMessage();

            throw $this->createCacheException($actualCode, '' === $errorMessage ? $memcachedErrorMessage : sprintf('%s. %s', $errorMessage, $memcachedErrorMessage));
        }

        return $actualCode;
    }

    /**
     * @param int $code
     * @param string $message
     * @return CacheException
     */
    private function createCacheException($code = null, $message = null)
    {
        if (null === $code || null === $message) {
            $code = $this->memcached->getResultCode();
            $message = $this->memcached->getResultMessage();
        }

        return new CacheException($this->getMemcachedError($code, $message));
    }

    /**
     * @param int $code
     * @param string $message
     * @return string
     */
    private function getMemcachedError($code, $message)
    {
        return sprintf("Unexpected result code \"%d\", message: \"%s\"", $code, $message);
    }

    private function throwInconsistentDataException()
    {
        throw new \RuntimeException('Inconsistent data returned from provider.');
    }
}

function unserializeHelper($className)
{
    ini_set('unserialize_callback_func', CacheManager::$_unserializeCallback);

    throw new CacheUnserializationException(sprintf('Cache unserialization: class "%s" is not found', $className));
}
