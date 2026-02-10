<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Matcher;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;

interface ItineraryMatcherInterface
{
    /**
     * @return float Confidence level of similarity. Between 0 and 1. 1 - matches exactly, 0 - does not match exactly.
     */
    public function match(EntityItinerary $entityItinerary, SchemaItinerary $schemaItinerary): float;
}
