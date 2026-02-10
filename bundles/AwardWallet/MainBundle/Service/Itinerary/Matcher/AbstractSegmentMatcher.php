<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Matcher;

use AwardWallet\Common\Entity\Geotag;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Service\GeoLocationMatcher;
use AwardWallet\MainBundle\Service\Itinerary\Matcher\Helper\DateHelper;
use AwardWallet\MainBundle\Service\Itinerary\Matcher\Helper\LocationHelper;

abstract class AbstractSegmentMatcher implements SegmentMatcherInterface
{
    protected GeoLocationMatcher $locationMatcher;

    public function __construct(GeoLocationMatcher $locationMatcher)
    {
        $this->locationMatcher = $locationMatcher;
    }

    /**
     * @param string|Geotag $entityLocation
     */
    protected function isSameLocation($entityLocation, ?string $schemaLocation, float $maxDistance = 2): bool
    {
        return $this->locationMatcher->match($entityLocation, $schemaLocation, $maxDistance, false);
    }

    protected function baseMatch(
        Tripsegment $entitySegment,
        ?string $schemaDepartureCode,
        ?string $schemaArrivalCode,
        ?string $schemaDepartureName,
        ?string $schemaArrivalName,
        ?string $schemaDepartureLocation,
        ?string $schemaArrivalLocation,
        ?string $schemaDepartureDate,
        ?string $schemaArrivalDate,
        float $maxDistance = 2
    ): MatchResult {
        $sameDepartureCode = LocationHelper::isSameLocationCode($entitySegment->getDepcode(), $schemaDepartureCode);
        $sameArrivalCode = LocationHelper::isSameLocationCode($entitySegment->getArrcode(), $schemaArrivalCode);
        $sameDepartureName = LocationHelper::isSameName($entitySegment->getDepname(), $schemaDepartureName);
        $sameArrivalName = LocationHelper::isSameName($entitySegment->getArrname(), $schemaArrivalName);
        $sameDepartureLocation = $this->isSameLocation(
            $entitySegment->getDepgeotagid() ?? $entitySegment->getDepname(),
            $schemaDepartureLocation,
            $maxDistance
        );
        $sameArrivalLocation = $this->isSameLocation(
            $entitySegment->getArrgeotagid() ?? $entitySegment->getArrname(),
            $schemaArrivalLocation,
            $maxDistance
        );
        $sameDepartureDate = DateHelper::isSameEntityDateWithSchemaDate($entitySegment->getScheduledDepDate(), $schemaDepartureDate);
        $sameArrivalDate = DateHelper::isSameEntityDateWithSchemaDate($entitySegment->getScheduledArrDate(), $schemaArrivalDate);

        return MatchResult::create()
            ->addResult(
                'segment.sameDepartureCode+sameArrivalCode+sameDepartureDate+sameArrivalDate',
                $sameDepartureCode && $sameArrivalCode && $sameDepartureDate && $sameArrivalDate,
                0.98
            )
            ->addResult(
                'segment.sameDepartureLocation+sameArrivalLocation+sameDepartureDate+sameArrivalDate',
                $sameDepartureLocation && $sameArrivalLocation && $sameDepartureDate && $sameArrivalDate,
                0.96
            )
            ->addResult(
                'segment.sameDepartureName+sameArrivalName+sameDepartureDate+sameArrivalDate',
                $sameDepartureName && $sameArrivalName && $sameDepartureDate && $sameArrivalDate,
                0.94
            )
            ->addResult(
                'segment.sameDepartureCode+sameArrivalCode',
                $sameDepartureCode && $sameArrivalCode,
                0.5
            )
            ->addResult(
                'segment.sameDepartureName+sameArrivalName',
                $sameDepartureName && $sameArrivalName,
                0.4
            );
    }
}
