<?php

namespace AwardWallet\MainBundle\Service\ItineraryComparator;

class Property
{
    public function equals(string $value1, string $value2): bool
    {
        return strcasecmp($value1, $value2) === 0;
    }
}
