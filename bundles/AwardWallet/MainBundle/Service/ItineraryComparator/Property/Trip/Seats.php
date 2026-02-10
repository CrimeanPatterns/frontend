<?php

namespace AwardWallet\MainBundle\Service\ItineraryComparator\Property\Trip;

use AwardWallet\MainBundle\Service\ItineraryComparator\Property;

class Seats extends Property
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
        $seats = [[], []];
        $emptyValues = ['-', '--', '---'];

        foreach ($data as $k => $val) {
            $val = trim($val);
            $seats[$k] = explode(",", $val);

            foreach ($seats[$k] as $i => $v) {
                $seats[$k][$i] = preg_replace("/^0+/ims", "", strtolower(trim($v)));

                if (empty($seats[$k][$i]) || in_array($seats[$k][$i], $emptyValues)) {
                    unset($seats[$k][$i]);
                }
            }
        }
        $seats[0] = array_unique($seats[0]);
        $seats[1] = array_unique($seats[1]);

        if (!\count($seats[0]) && !\count($seats[1])) {
            return true;
        }

        return !\count(array_diff($seats[0], $seats[1])) && !\count(array_diff($seats[1], $seats[0]));
    }
}
