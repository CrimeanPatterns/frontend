<?php

namespace AwardWallet\MainBundle\Globals\Utils;

use Duration\Duration;

/**
 * Copy of \Codeception\Util\ArrayContainsComparator.
 *
 * @see \Codeception\Util\ArrayContainsComparator
 */
class ArrayContainsComparator
{
    public static function containsArray(array $needle, $haystack): bool
    {
        return self::arrayIntersectRecursive($needle, $haystack) == $needle;
    }

    /**
     * @author nleippe@integr8ted.com
     * @author tiger.seo@gmail.com
     * @see http://www.php.net/manual/en/function.array-intersect-assoc.php#39822
     * @return array|bool
     */
    private static function arrayIntersectRecursive($arr1, $arr2)
    {
        if (!\is_array($arr1) || !\is_array($arr2)) {
            return false;
        }

        // if it is not an associative array we do not compare keys
        if (self::arrayIsSequential($arr1) && self::arrayIsSequential($arr2)) {
            return self::sequentialArrayIntersect($arr1, $arr2);
        }

        return self::associativeArrayIntersect($arr1, $arr2);
    }

    /**
     * This array has sequential keys?
     */
    private static function arrayIsSequential(array $array): bool
    {
        if ($count = \count($array)) {
            return
                (isset($array[0]) || \array_key_exists(0, $array))
                && (\array_keys($array) === \range(0, $count - 1));
        }

        return true;
    }

    private static function sequentialArrayIntersect(array $arr1, array $arr2): array
    {
        $ret = [];

        // Do not match the same item of $arr2 against multiple items of $arr1
        $matchedKeys = [];

        foreach ($arr1 as $key1 => $value1) {
            foreach ($arr2 as $key2 => $value2) {
                if (isset($matchedKeys[$key2])) {
                    continue;
                }

                $return = self::arrayIntersectRecursive($value1, $value2);

                if ($return !== false && $return == $value1) {
                    $ret[$key1] = $return;
                    $matchedKeys[$key2] = true;

                    break;
                }

                if (self::isEqualValue($value1, $value2)) {
                    $ret[$key1] = $value1;
                    $matchedKeys[$key2] = true;

                    break;
                }
            }
        }

        return $ret;
    }

    /**
     * @return array|bool|null
     */
    private static function associativeArrayIntersect(array $arr1, array $arr2)
    {
        $commonKeys = \array_intersect(\array_keys($arr1), \array_keys($arr2));

        $ret = [];

        foreach ($commonKeys as $key) {
            $return = self::arrayIntersectRecursive($arr1[$key], $arr2[$key]);

            if ($return) {
                $ret[$key] = $return;

                continue;
            }

            if (self::isEqualValue($arr1[$key], $arr2[$key])) {
                $ret[$key] = $arr1[$key];
            }
        }

        if (empty($commonKeys)) {
            foreach ($arr2 as $arr) {
                $return = self::arrayIntersectRecursive($arr1, $arr);

                if ($return && $return == $arr1) {
                    return $return;
                }
            }
        }

        if (\count($ret) < \min(\count($arr1), \count($arr2))) {
            return null;
        }

        return $ret;
    }

    private static function isEqualValue($val1, $val2): bool
    {
        if (\is_numeric($val1)) {
            $val1 = (string) $val1;
        }

        if (\is_numeric($val2)) {
            $val2 = (string) $val2;
        }

        // TODO: implement Comparable-like interface for Duration
        if (
            $val1 instanceof Duration
            && $val2 instanceof Duration
        ) {
            return $val1->eq($val2);
        }

        return $val1 === $val2;
    }
}
