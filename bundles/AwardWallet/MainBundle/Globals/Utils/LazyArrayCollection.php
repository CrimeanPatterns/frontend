<?php

namespace AwardWallet\MainBundle\Globals\Utils;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\Criteria;

use function AwardWallet\MainBundle\Globals\Utils\iter\toIterInternal;

/**
 * @method ArrayCollectionExtended filter(\Closure $p)
 * @method ArrayCollectionExtended map(\Closure $func)
 * @method ArrayCollectionExtended partition(\Closure $p)
 * @method ArrayCollectionExtended matching(Criteria $criteria)
 */
class LazyArrayCollection extends AbstractLazyCollection
{
    /**
     * @var ArrayCollectionExtended
     */
    protected $collection;
    /**
     * @var iterable
     */
    protected $iterable;
    /**
     * @var bool
     */
    protected $withKeys;

    public function __construct(iterable $iterable, bool $withKeys = true)
    {
        $this->iterable = $iterable;
        $this->withKeys = $withKeys;
    }

    public function getIterator()
    {
        return toIterInternal($this->iterable);
    }

    public function unique(int $sortFlags = \SORT_STRING): ArrayCollectionExtended
    {
        $this->initialize();

        return $this->collection->unique($sortFlags);
    }

    public function sort(?int $sortFlags = null): ArrayCollectionExtended
    {
        $this->initialize();

        return $this->collection->sort($sortFlags);
    }

    public function rsort(?int $sortFlags = null): ArrayCollectionExtended
    {
        $this->initialize();

        return $this->collection->rsort($sortFlags);
    }

    public function asort(?int $sortFlags = null): ArrayCollectionExtended
    {
        $this->initialize();

        return $this->collection->asort($sortFlags);
    }

    public function arsort(?int $sortFlags = null): ArrayCollectionExtended
    {
        $this->initialize();

        return $this->collection->arsort($sortFlags);
    }

    public function ksort(?int $sortFlags = null): ArrayCollectionExtended
    {
        $this->initialize();

        return $this->collection->ksort($sortFlags);
    }

    public function krsort(?int $sortFlags = null): ArrayCollectionExtended
    {
        $this->initialize();

        return $this->collection->krsort($sortFlags);
    }

    public function usort(callable $comparator): ArrayCollectionExtended
    {
        $this->initialize();

        return $this->collection->usort($comparator);
    }

    public function uksort(callable $comparator): ArrayCollectionExtended
    {
        $this->initialize();

        return $this->collection->uksort($comparator);
    }

    public function uasort(callable $comparator): ArrayCollectionExtended
    {
        $this->initialize();

        return $this->collection->uasort($comparator);
    }

    public function shuffle(): ArrayCollectionExtended
    {
        $this->initialize();

        return $this->collection->shuffle();
    }

    public function join(string $glue = ''): string
    {
        $this->initialize();

        return $this->collection->join($glue);
    }

    public function toFluent(): IteratorFluent
    {
        return new IteratorFluent($this->initialized ? $this->collection->toArray() : $this->iterable);
    }

    public function it(): IteratorFluent
    {
        return new IteratorFluent($this->initialized ? $this->collection->toArray() : $this->iterable);
    }

    protected function doInitialize()
    {
        $this->collection = new ArrayCollectionExtended(
            \is_array($this->iterable) ?
                $this->iterable :
                (
                    $this->withKeys ?
                        \iter\toArrayWithKeys($this->iterable) :
                        \iter\toArray($this->iterable)
                )
        );
    }
}
