<?php

namespace AwardWallet\MainBundle\Service\ItineraryComparator\Property\Trip;

use AwardWallet\MainBundle\Service\ItineraryComparator\Property;

class Stops extends Property
{
    public function equals(string $value1, string $value2): bool
    {
        $result = parent::equals($value1, $value2);

        if ($result) {
            return true;
        }
        $value1 = $this->prepare($value1);
        $value2 = $this->prepare($value2);

        return $value1 == $value2;
    }

    private function prepare(string $value): string
    {
        $value = trim(strtolower($value));

        if (preg_match("/^non?(\s|-)stops?$/ims", $value)) {
            $value = 0;
        }

        return $value;
    }
}
