<?php

namespace AwardWallet\MainBundle\Service\ItineraryComparator\Property;

class CancellationPolicy extends ArrayProperty
{
    public string $separator = "|";

    public bool $isNumbers = false;
}
