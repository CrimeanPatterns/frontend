<?php

namespace AwardWallet\MainBundle\Timeline\Formatter;

use AwardWallet\MainBundle\Timeline\Item\ItemInterface;
use AwardWallet\MainBundle\Timeline\QueryOptions;

interface FormatHandlerInterface
{
    /**
     * @param ItemInterface[] $items
     */
    public function handle(array $items, QueryOptions $options): array;

    public function addItemFormatter(string $type, ItemFormatterInterface $formatter);
}
