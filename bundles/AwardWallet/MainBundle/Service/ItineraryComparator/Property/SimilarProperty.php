<?php

namespace AwardWallet\MainBundle\Service\ItineraryComparator\Property;

use AwardWallet\MainBundle\Service\ItineraryComparator\Property;

class SimilarProperty extends Property
{
    public float $minPercent = 70;

    public function equals(string $value1, string $value2): bool
    {
        $result = parent::equals($value1, $value2);

        if ($result) {
            return true;
        }

        similar_text($value1, $value2, $percent);

        if ($percent < $this->minPercent) {
            return false;
        }

        return true;
    }
}
