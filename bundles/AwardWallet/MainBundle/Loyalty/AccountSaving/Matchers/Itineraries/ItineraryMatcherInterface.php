<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Itineraries;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;

interface ItineraryMatcherInterface
{
    /**
     * @return float Confidence level (0 - certainly not, 1 - certainly yes, 0.5 - 50/50)
     */
    public function match(EntityItinerary $entityItinerary, SchemaItinerary $schemaItinerary): float;
}
