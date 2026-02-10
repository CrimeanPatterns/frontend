<?php

namespace AwardWallet\MainBundle\Service\ItineraryComparator\Property;

use AwardWallet\MainBundle\Service\ItineraryComparator\Property;

class AccountNumbers extends Property
{
    public function equals(string $value1, string $value2): bool
    {
        $result = parent::equals($value1, $value2);

        if ($result) {
            return true;
        }
        $value1 = strtolower($value1);
        $value2 = strtolower($value2);

        $data = [$value1, $value2];
        $numbers = [[], []];
        $emptyValues = ['-', '--', '---'];

        foreach ($data as $k => $val) {
            $val = trim($val);
            $numbers[$k] = explode(",", $val);

            foreach ($numbers[$k] as $i => $v) {
                $numbers[$k][$i] = preg_replace("/^0+/ims", "", strtolower(trim($v)));

                if (empty($numbers[$k][$i]) || in_array($numbers[$k][$i], $emptyValues)) {
                    unset($numbers[$k][$i]);
                }
            }
        }
        $numbers[0] = array_unique($numbers[0]);
        $numbers[1] = array_unique($numbers[1]);

        if (!\count($numbers[0]) && !\count($numbers[1])) {
            return true;
        }

        return !\count(array_diff($numbers[0], $numbers[1])) && !\count(array_diff($numbers[1], $numbers[0]));
    }
}
