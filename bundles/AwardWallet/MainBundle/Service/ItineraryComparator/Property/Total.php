<?php

namespace AwardWallet\MainBundle\Service\ItineraryComparator\Property;

class Total extends ArrayProperty
{
    public string $separator = "|";

    public bool $isNumbers = true;

    public float $thresholdPercent = 2;
}
