<?php

namespace AwardWallet\MainBundle\Globals;

class CollectionUtils
{
    /**
     * @return \Generator
     */
    public static function &takeWhile(iterable $items, callable $predicate)
    {
        foreach ($items as &$item) {
            if ($predicate($item)) {
                yield $item;
            } else {
                break;
            }
        }
    }

    /**
     * @return bool|mixed
     */
    public static function indexWhere(iterable $items, callable $predicate)
    {
        foreach ($items as $key => &$item) {
            if ($predicate($item)) {
                return $key;
            }
        }

        return false;
    }

    /**
     * @param int $count
     * @return array
     */
    public static function take(iterable $items, $count)
    {
        if (is_array($items)) {
            return array_slice($items, 0, $count);
        } else {
            return self::takeWhile(
                $items,
                function () use (&$count) {
                    return $count-- > 0;
                }
            );
        }
    }

    /**
     * @return \Generator
     */
    public static function &dropWhile(iterable $items, callable $predicate)
    {
        $stopped = false;

        foreach ($items as &$item) {
            if ($stopped) {
                yield $item;
            } elseif (!$predicate($item)) {
                yield $item;
                $stopped = true;
            }
        }
    }

    /**
     * @return \Generator
     */
    public static function &filter(iterable $items, callable $predicate)
    {
        foreach ($items as &$item) {
            if ($predicate($item)) {
                yield $item;
            }
        }
    }

    /**
     * @param array $items
     * @return \Generator
     */
    public static function &reverse($items)
    {
        for ($i = count($items) - 1; $i >= 0; $i--) {
            $link = &$items[$i];

            yield $link;
        }
    }

    /**
     * @return array
     */
    public static function partition(iterable $items, callable $predicate)
    {
        $first = [];
        $second = [];

        foreach ($items as $item) {
            if ($predicate($item)) {
                $first[] = $item;
            } else {
                $second[] = $item;
            }
        }

        return [$first, $second];
    }

    /**
     * @return bool
     */
    public static function any(iterable $items, callable $predicate)
    {
        return false !== self::indexWhere($items, $predicate);
    }

    /**
     * @return array
     */
    public static function toArray(iterable $items)
    {
        if (is_array($items)) {
            return $items;
        }

        if ($items instanceof \Traversable) {
            return iterator_to_array($items);
        }

        throw new \InvalidArgumentException(sprintf('Expected "array" or "\\Traversable" but "%s" was given', gettype($items)));
    }
}
