<?php

namespace AwardWallet\MainBundle\Globals\Utils;

class MemoizableIterator implements \Iterator
{
    /**
     * @var \Iterator
     */
    protected $iterator;
    /**
     * @var \Iterator
     */
    protected $origIterator;
    /**
     * @var bool
     */
    protected $origIteratorInitialized = false;
    /**
     * @var int
     */
    protected $currentPosition = 0;
    /**
     * @var [][]
     */
    protected $cachedPairs = [];
    /**
     * @var int
     */
    protected $cachedPairsCount = 0;

    public function __construct(\Iterator $iterator)
    {
        $this->iterator = $iterator;
        $this->origIterator = $iterator;
    }

    public function current()
    {
        return $this->iterator->current();
    }

    public function next()
    {
        $this->iterator->next();
        $this->currentPosition++;

        if ($this->currentPosition > $this->cachedPairsCount - 1) {
            $this->storeOrigPairInCache();
        }
    }

    public function key()
    {
        return $this->iterator->key();
    }

    public function valid()
    {
        return $this->iterator->valid();
    }

    public function rewind()
    {
        if ($this->origIteratorInitialized) {
            if ($this->origIterator->valid()) {
                $this->iterator = \iter\chain(
                    \iter\fromPairs($this->cachedPairs),
                    \iter\drop(1, \AwardWallet\MainBundle\Globals\Utils\iter\makeUnrewindable($this->origIterator))
                );
            } else {
                $this->iterator = \iter\fromPairs($this->cachedPairs);
            }

            $this->cachedPairsCount = \count($this->cachedPairs);
            $this->currentPosition = 0;
        } else {
            $this->origIterator->rewind();
            $this->origIteratorInitialized = true;
            $this->storeOrigPairInCache();
        }
    }

    protected function storeOrigPairInCache()
    {
        if ($this->origIterator->valid()) {
            $this->cachedPairs[] = [
                $this->origIterator->key(),
                $this->origIterator->current(),
            ];
        }
    }
}
