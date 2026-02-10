<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter;

use AwardWallet\MainBundle\Entity\Trip as EntityTrip;
use AwardWallet\MainBundle\Entity\Tripsegment as EntitySegment;
use AwardWallet\MainBundle\Loyalty\AccountSaving\SavingOptions;
use AwardWallet\Schema\Itineraries\BusSegment;
use AwardWallet\Schema\Itineraries\CruiseSegment;
use AwardWallet\Schema\Itineraries\FerrySegment;
use AwardWallet\Schema\Itineraries\FlightSegment;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;
use AwardWallet\Schema\Itineraries\TrainSegment;
use AwardWallet\Schema\Itineraries\TransferSegment;

interface ItinerarySegmentSchema2EntityConverterInterface
{
    /**
     * @param BusSegment|CruiseSegment|FerrySegment|FlightSegment|TrainSegment|TransferSegment $schemaSegment
     */
    public function convertSegment(
        SchemaItinerary $schemaItinerary,
        $schemaSegment,
        EntityTrip $entityTrip,
        ?EntitySegment $entitySegment,
        SavingOptions $options
    ): EntitySegment;
}
