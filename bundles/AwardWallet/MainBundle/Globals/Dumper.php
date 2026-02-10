<?php

namespace AwardWallet\MainBundle\Globals;

class Dumper
{
    public static function filterEmpty($value)
    {
        if (!is_array($value) && !is_object($value)) {
            return $value;
        }

        $result = [];

        foreach ($value as $idx => $val) {
            if (!empty($val)) {
                $filtered = self::filterEmpty($val);

                if (!empty($filtered)) {
                    $result[$idx] = $filtered;
                }
            }
        }

        return $result;
    }
}
