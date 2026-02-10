<?php

namespace AwardWallet\MainBundle\Timeline\FilterCallback;

use AwardWallet\MainBundle\Timeline\Item\ItemInterface;

abstract class AbstractFilterCallback implements FilterCallbackInterface
{
    /**
     * @var callable
     */
    protected $callback;
    /**
     * @var string
     */
    protected $cache;

    public function __construct(callable $callback, string $cache)
    {
        $this->callback = $callback;
        $this->cache = $cache;
    }

    public function and(FilterCallbackInterface $filterCallback): FilterCallbackInterface
    {
        if ($filterCallback instanceof PassingFilterCallback) {
            return $this;
        }

        $firstCallback = $this->callback;
        $secondCallback = $filterCallback->getCallback();

        return new FilterCallback(
            static function (ItemInterface $item) use ($firstCallback, $secondCallback) {
                return $firstCallback($item) && $secondCallback($item);
            },
            "{$this->cache}_and_{$filterCallback->getCacheKey()}"
        );
    }

    public function getCacheKey(): string
    {
        return $this->cache;
    }

    public function getCallback(): callable
    {
        return $this->callback;
    }
}
