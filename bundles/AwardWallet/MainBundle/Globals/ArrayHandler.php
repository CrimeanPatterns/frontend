<?php

namespace AwardWallet\MainBundle\Globals;

class ArrayHandler
{
    /**
     * @param array $array example array('elem1'=>1, 'elem2'=>2, 'elem3'=>3)
     * @param array $group example array('elem3', 'elem1')
     * @param bool $after
     * @return array example array('elem2'=>2, 'elem3'=>3, 'elem1'=>1)
     */
    public static function smartSortArray($array, $group, $after = true)
    {
        $group = array_flip($group);
        $diff = array_diff_key($array, $group);
        $intersect = array_intersect_key($group, $array);

        foreach ($intersect as $i => $v) {
            $intersect[$i] = $array[$i];
        }

        // Insert
        if (!sizeof($diff)) {
            return $intersect;
        }
        $keys = array_keys($diff);
        $values = array_values($diff);
        $index = ($after) ? sizeof($keys) : 0;
        array_splice($keys, $index, 0, array_keys($intersect));
        array_splice($values, $index, 0, array_values($intersect));

        return array_combine($keys, $values);
    }

    public static function smartSortTwoDimArray($array, $keyField, $group, $after = true)
    {
        $temp = [];

        foreach ($array as $k => $v) {
            $temp[$v[$keyField]] = $k;
        }
        $temp = self::smartSortArray($temp, $group, $after);
        $result = [];

        foreach ($temp as $v) {
            $result[] = $array[$v];
        }

        return $result;
    }

    /**
     * Builds a map (key-value pairs) from a multidimensional array or an array of objects.
     *
     * For example,
     * ```php
     * $array = [
     *     ['city' => 'Amsterdam', 'country' => 'Netherlands', 'countryCode' => 'NL'],
     *     ['city' => 'Geneva', 'country' => 'Switzerland', 'countryCode' => 'CH'],
     *     ['city' => 'London', 'country' => 'United Kingdom', 'countryCode' => 'GB']
     * ];
     *
     * $result = ArrayHandler::map($array, 'countryCode', 'country');
     * // the result is:
     * // [
     * //     'NL' => 'Netherlands',
     * //     'CH' => 'Switzerland',
     * //     'GB' => 'United Kingdom'
     * // ]
     * ```
     */
    public static function map($array, $from, $to): array
    {
        $result = [];

        foreach ($array as $item) {
            $key = static::getValue($item, $from);
            $value = static::getValue($item, $to);
            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * Retrieves the value of an array element or object property with the given key or property name.
     *
     * @param array|object $array array or object to extract value from
     * @param string $key key name of the array element or property name of the object
     */
    public static function getValue($array, $key)
    {
        if (is_object($array) && property_exists($array, $key)) {
            return $array->{$key};
        }

        if (is_array($array) && array_key_exists($key, $array)) {
            return $array[$key];
        }

        return null;
    }
}
