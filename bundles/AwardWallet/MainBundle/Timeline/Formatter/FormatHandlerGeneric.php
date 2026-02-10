<?php

namespace AwardWallet\MainBundle\Timeline\Formatter;

use AwardWallet\MainBundle\Timeline\Item;
use AwardWallet\MainBundle\Timeline\QueryOptions;

class FormatHandlerGeneric implements FormatHandlerInterface
{
    /**
     * @var ItemFormatterInterface[]
     */
    private $itemFormatters;

    public function handle(array $items, QueryOptions $options): array
    {
        return array_map(
            function (Item\ItemInterface $item) use ($options) {
                if (!isset($this->itemFormatters[$type = strtolower($item->getType())])) {
                    throw new \InvalidArgumentException("Unknown item formatter type '{$type}'");
                }

                return $this->itemFormatters[$type]->format($item, $options);
            },
            $items
        );
    }

    public function addItemFormatter(string $type, ItemFormatterInterface $formatter)
    {
        if (isset($this->itemFormatters[$type])) {
            throw new \InvalidArgumentException("Item formatter of type '{$type}' already exists!");
        }

        $this->itemFormatters[$type] = $formatter;

        return $this;
    }
}
