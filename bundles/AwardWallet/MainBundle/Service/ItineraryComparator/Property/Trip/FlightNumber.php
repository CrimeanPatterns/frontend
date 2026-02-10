<?php

namespace AwardWallet\MainBundle\Service\ItineraryComparator\Property\Trip;

use AwardWallet\MainBundle\Service\ItineraryComparator\Property;

class FlightNumber extends Property
{
    public function equals(string $value1, string $value2): bool
    {
        $result = parent::equals($value1, $value2);

        if ($result) {
            return true;
        }

        $value1 = preg_replace("/\s+/ims", "", strtolower($value1));
        $value2 = preg_replace("/\s+/ims", "", strtolower($value2));

        if (preg_match("/^[a-z]{0,}(\d+)$/i", $value1, $matches1) && preg_match("/^[a-z]{0,}(\d+)$/i", $value2, $matches2)) {
            return $matches1[1] == $matches2[1];
        }

        return $value1 == $value2;
    }
}
