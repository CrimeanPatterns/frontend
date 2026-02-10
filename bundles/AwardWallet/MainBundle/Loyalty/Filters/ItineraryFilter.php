<?php

namespace AwardWallet\MainBundle\Loyalty\Filters;

use AwardWallet\Schema\Itineraries as It;
use AwardWallet\Schema\Itineraries\Itinerary;

class ItineraryFilter
{
    public function filter(Itinerary $it): bool
    {
        if ($it instanceof It\Flight) {
            if (!$it->cancelled && count($it->segments) > 0) {
                $filtered = [];

                foreach ($it->segments as $seg) {
                    /** @var It\FlightSegment $seg */
                    if (!empty($seg->departure) && !empty($seg->departure->airportCode) && !empty($seg->departure->localDateTime)
                        && !empty($seg->arrival) && !empty($seg->arrival->airportCode) && !empty($seg->arrival->localDateTime)) {
                        $filtered[] = $seg;
                    }
                }
                $it->segments = $filtered;

                return count($it->segments) > 0;
            }
        }

        return true;
    }
}
