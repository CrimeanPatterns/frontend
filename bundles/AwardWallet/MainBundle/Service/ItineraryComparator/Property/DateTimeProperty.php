<?php

namespace AwardWallet\MainBundle\Service\ItineraryComparator\Property;

use AwardWallet\MainBundle\Service\ItineraryComparator\Property;

class DateTimeProperty extends Property
{
    public array $parts = ['year', 'mon', 'mday', 'hours', 'minutes'];

    public function equals(string $value1, string $value2): bool
    {
        $result = parent::equals($value1, $value2);

        if ($result) {
            return true;
        }
        $value1 = strtolower($value1);
        $value2 = strtolower($value2);

        $info1 = array_intersect_key(getdate($value1), array_flip($this->parts));
        $info2 = array_intersect_key(getdate($value2), array_flip($this->parts));

        foreach ($info1 as $name => $val) {
            if ($val !== $info2[$name]) {
                return false;
            }
        }

        return true;
    }
}
