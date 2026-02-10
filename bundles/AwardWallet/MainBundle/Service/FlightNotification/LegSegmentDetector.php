<?php

namespace AwardWallet\MainBundle\Service\FlightNotification;

use AwardWallet\Common\Entity\Geotag;
use AwardWallet\MainBundle\Entity\Tripsegment;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class LegSegmentDetector
{
    /**
     * check that no trip interleaves with interval (DepDate - offset; DepDate)
     * returns true if this is first flight, or first flight after pause in $legOffset hours.
     * refs #13705.
     */
    public function isLegSegment(Tripsegment $tripsegment, float $legOffset): bool
    {
        $utcDepDate = Geotag::getLocalDateTimeByGeoTag($tripsegment->getDepartureDate(), $tripsegment->getDepgeotagid());
        $utcDepDate->setTimezone(new \DateTimeZone('UTC'));
        $startDate = (clone $utcDepDate)->modify("-$legOffset hours");
        $endDate = (clone $utcDepDate);

        return !it($tripsegment->getTripid()->getSegments())
            ->any(function (Tripsegment $segment) use ($startDate, $endDate) {
                $dep = Geotag::getLocalDateTimeByGeoTag($segment->getDepartureDate(), $segment->getDepgeotagid());
                $arr = Geotag::getLocalDateTimeByGeoTag($segment->getArrivalDate(), $segment->getArrgeotagid());

                return
                    ($dep > $startDate && $dep < $endDate)
                    || ($arr > $startDate && $arr < $endDate)
                ;
            });
    }
}
