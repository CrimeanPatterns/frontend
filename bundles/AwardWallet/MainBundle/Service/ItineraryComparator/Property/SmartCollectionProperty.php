<?php

namespace AwardWallet\MainBundle\Service\ItineraryComparator\Property;

use AwardWallet\MainBundle\Service\ItineraryComparator\Property;

abstract class SmartCollectionProperty extends Property
{
    public function equals(string $value1, string $value2): bool
    {
        $result = parent::equals($value1, $value2);

        if ($result) {
            return true;
        }

        $array1 = $this->split($value1);
        $array2 = $this->split($value2);

        foreach ($array1 as $k1 => $item1) {
            foreach ($array2 as $k2 => $item2) {
                if ($this->match($item1, $item2)) {
                    unset($array1[$k1]);
                    unset($array2[$k2]);

                    continue 2;
                }
            }

            return false;
        }

        foreach ($array2 as $item2) {
            foreach ($array1 as $item1) {
                if ($this->match($item1, $item2)) {
                    continue 2;
                }
            }

            return false;
        }

        return true;
    }

    abstract protected function split(string $value): array;

    abstract protected function match(string $value1, string $value2): bool;
}
