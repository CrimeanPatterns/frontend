<?php

namespace AwardWallet\MainBundle\Service\ItineraryComparator\Property;

use AwardWallet\MainBundle\Service\ItineraryComparator\Property;
use AwardWallet\MainBundle\Service\ItineraryComparator\Util;

class NumberProperty extends Property
{
    public function equals(string $value1, string $value2): bool
    {
        $result = parent::equals($value1, $value2);

        if ($result) {
            return true;
        }
        $value1 = strtolower($value1);
        $value2 = strtolower($value2);

        return Util::getNumber($value1, true) === Util::getNumber($value2, true);
    }
}
