<?php

namespace AwardWallet\MainBundle\Globals\PropertyAccess;

class SafeCallPropertyPathIterator extends \ArrayIterator implements SafeCallPropertyPathIteratorInterface
{
    /**
     * @var SafeCallPropertyPathInterface
     */
    protected $path;

    /**
     * @param SafeCallPropertyPathInterface $path The property path to traverse
     */
    public function __construct(SafeCallPropertyPathInterface $path)
    {
        parent::__construct($path->getElements());

        $this->path = $path;
    }

    public function isIndex()
    {
        return $this->path->isIndex($this->key());
    }

    public function isProperty()
    {
        return $this->path->isProperty($this->key());
    }

    public function isSafeCall()
    {
        return $this->path->isSafeCall($this->key());
    }
}
