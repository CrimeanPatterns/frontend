<?php

namespace AwardWallet\MainBundle\Globals;

use AwardWallet\MainBundle\Globals\BinarySearchResult as BSR;

class ArrayUtils
{
    public static function sort(array $array, ?int $sortFlags = null): array
    {
        sort($array, $sortFlags);

        return $array;
    }

    public static function padToNextPowOf(array $input, int $base, $filler = null): array
    {
        return \array_pad(
            $input,
            $base ** ((int) \ceil(\log(\count($input), $base))),
            $filler
        );
    }

    public static function padToNextPowOf2(array $input, $filler = null): array
    {
        return self::padToNextPowOf($input, 2, $filler);
    }

    /**
     * @param array[] $inputs
     * @return array[]
     */
    public static function multiPadToNextPowOf(array $inputs, int $base, $filler = null): array
    {
        $maxLength = 0;

        foreach ($inputs as $input) {
            $maxLength = \max($maxLength, \count($input));
        }

        $newLength = $base ** ((int) \ceil(\log($maxLength, $base)));

        foreach ($inputs as $i => $input) {
            $inputs[$i] = \array_pad($input, $newLength, $filler);
        }

        return $inputs;
    }

    /**
     * @param array[] $inputs
     * @return array[]
     */
    public static function multiPadToNextPowOf2(array $inputs, $filler = null): array
    {
        return self::multiPadToNextPowOf($inputs, 2, $filler);
    }

    /**
     * Efficiently (in log2(N) comparisons) searches for the leftmost position of the needle in a sorted list
     * Returns instance of BSR\BinarySearchResultInterface:
     *     BSR\GreaterThan if all elements in the list are less than the needle
     *     BSR\LessThan if all elements in the list are greater than the needle
     *     BSR\Exact if the needle is found in the list, contains the leftmost index of the needle
     *     BSR\Between if the needle should be located between two elements in the list.
     *
     * @template T
     * @param list<T> $list
     * @param T $needle
     */
    public static function binarySearchLeftmostRange(array $list, $needle, ?int $low = null, ?int $high = null): BSR\BinarySearchResultInterface
    {
        if (!$list) {
            throw new \LogicException('Cannot search in an empty list');
        }

        $minLow = $low ?? 0;
        $low = $minLow;
        $maxHigh = $high ?? (\count($list) - 1);
        $high = $maxHigh;

        if ($high === $low) {
            $el = $list[$high];

            if ($el < $needle) {
                return new BSR\GreaterThan(1);
            } elseif ($el > $needle) {
                return new BSR\LessThan(1);
            } else {
                return new BSR\Exact(0, 0, 1);
            }
        }

        if ($list[$minLow] > $needle) {
            return new BSR\LessThan($maxHigh - $minLow + 1);
        }

        if ($list[$maxHigh] < $needle) {
            return new BSR\GreaterThan($maxHigh - $minLow + 1);
        }

        $leftmost = -1;

        while ($low <= $high) {
            $mid = (int) (($low + $high) / 2);

            if ($list[$mid] < $needle) {
                $low = $mid + 1;
            } elseif ($list[$mid] > $needle) {
                $high = $mid - 1;
            } else {
                $leftmost = $mid;
                $high = $mid - 1;
            }
        }

        return (-1 === $leftmost) ?
            new BSR\Between(
                $high,
                $low,
                $low - $minLow,
                $maxHigh - $low + 1
            ) :
            new BSR\Exact(
                $leftmost,
                $leftmost - $minLow,
                $maxHigh - $leftmost + 1
            );
    }

    /**
     * Efficiently (in log2(N) comparisons) searches for the leftmost position of the needle in a sorted list
     * Returns instance of BSR\BinarySearchResultInterface:
     *     BSR\GreaterThan if all elements in the list are less than the needle
     *     BSR\LessThan if all elements in the list are greater than the needle
     *     BSR\Exact if the needle is found in the list, contains the leftmost index of the needle
     *     BSR\Between if the needle should be located between two elements in the list.
     *
     * @template T
     * @param list<T> $list
     * @param callable(T): int $comparator returns value less than 0 if element is less than the needle, greater than 0 if element is greater than the needle, and 0 if they are equal
     */
    public static function binarySearchLeftmostRangeWithComparator(array $list, callable $comparator, ?int $low = null, ?int $high = null): BSR\BinarySearchResultInterface
    {
        if (!$list) {
            throw new \LogicException('Cannot search in an empty list');
        }

        $minLow = $low ?? 0;
        $low = $minLow;
        $maxHigh = $high ?? (\count($list) - 1);
        $high = $maxHigh;

        if ($high === $low) {
            $cmp = $comparator($list[$low]);

            if ($cmp < 0) {
                return new BSR\GreaterThan(1);
            } elseif ($cmp > 0) {
                return new BSR\LessThan(1);
            } else {
                return new BSR\Exact(0, 0, 1);
            }
        }

        if ($comparator($list[$minLow]) > 0) {
            return new BSR\LessThan($maxHigh - $minLow + 1);
        }

        if ($comparator($list[$maxHigh]) < 0) {
            return new BSR\GreaterThan($maxHigh - $minLow + 1);
        }

        $leftmost = -1;

        while ($low <= $high) {
            $mid = (int) (($low + $high) / 2);
            $cmp = $comparator($list[$mid]);

            if ($cmp < 0) {
                $low = $mid + 1;
            } elseif ($cmp > 0) {
                $high = $mid - 1;
            } else {
                $leftmost = $mid;
                $high = $mid - 1;
            }
        }

        return (-1 === $leftmost) ?
            new BSR\Between(
                $high,
                $low,
                $low - $minLow,
                $maxHigh - $low + 1
            ) :
            new BSR\Exact(
                $leftmost,
                $leftmost - $minLow,
                $maxHigh - $leftmost + 1
            );
    }
}
