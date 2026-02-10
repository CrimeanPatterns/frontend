<?php

namespace AwardWallet\MainBundle\Timeline\Formatter;

use AwardWallet\MainBundle\Timeline\Item\ItemInterface;
use AwardWallet\MainBundle\Timeline\QueryOptions;

interface ItemFormatterInterface
{
    public const DESKTOP = 'desktop';
    public const MOBILE = 'mobile';

    public function format(ItemInterface $item, QueryOptions $options);
}
