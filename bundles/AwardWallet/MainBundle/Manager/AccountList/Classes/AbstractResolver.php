<?php

namespace AwardWallet\MainBundle\Manager\AccountList\Classes;

abstract class AbstractResolver implements ResolverInterface
{
    /**
     * @var ConverterInterface[]
     */
    protected $items = [];

    public function add(ConverterInterface $item)
    {
        $this->items[] = $item;
    }

    public function set(array $items)
    {
        foreach ($items as $item) {
            $this->add($item);
        }
    }

    public function resolve()
    {
        $this->items = [];
    }

    public function isEmpty()
    {
        return count($this->items) == 0;
    }
}
