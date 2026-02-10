<?php

namespace AwardWallet\MainBundle\Manager\AccountList\Provider;

use AwardWallet\MainBundle\Manager\AccountList\Classes\AbstractResolver;
use AwardWallet\MainBundle\Manager\AccountList\Classes\ConverterInterface;

/**
 * Class StatsResolver.
 *
 * @property Converter[] $items
 */
class StatsResolver extends AbstractResolver
{
    public function __construct()
    {
    }

    public function add(ConverterInterface $item)
    {
        if ($item instanceof Converter) {
            $this->items[] = $item;
        }
    }

    public function resolve()
    {
        foreach ($this->items as $item) {
            $item->setStats(new StatsProperty($item->getEntity()));
        }
        $this->items = [];
    }
}
