<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Matcher\Helper;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;

/**
 * @NoDI
 */
class CurrencyHelper
{
    public static function isSameOrEmptyTotal(EntityItinerary $entityItinerary, ?SchemaItinerary $schemaItinerary, float $threshold = 0.01): bool
    {
        if (is_null($entityItinerary->getPricingInfo()->getTotal()) || is_null($schemaItinerary->pricingInfo->total ?? null)) {
            return true;
        }

        if (abs($entityItinerary->getPricingInfo()->getTotal() - $schemaItinerary->pricingInfo->total) <= $threshold) {
            return true;
        }

        return false;
    }
}
