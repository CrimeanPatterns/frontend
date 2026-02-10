<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Loyalty\AccountSaving\SavingOptions;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;

interface ItinerarySchema2EntityConverterInterface
{
    public function convert(
        SchemaItinerary $schemaItinerary,
        ?EntityItinerary $entityItinerary,
        SavingOptions $options
    ): EntityItinerary;
}
