<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Matcher;

use AwardWallet\MainBundle\Entity\Tripsegment as EntitySegment;
use AwardWallet\MainBundle\Service\Itinerary\Matcher\Helper\ConfirmationNumberHelper;
use AwardWallet\MainBundle\Service\Itinerary\Matcher\Helper\DateHelper;
use AwardWallet\MainBundle\Service\Itinerary\Matcher\Helper\FlightNumberHelper;
use AwardWallet\MainBundle\Service\Itinerary\Matcher\Helper\LocationHelper;
use AwardWallet\Schema\Itineraries\FlightSegment as SchemaFlightSegment;

class FlightSegmentMatcher extends AbstractSegmentMatcher
{
    /**
     * @param SchemaFlightSegment $schemaSegment
     */
    public function match(EntitySegment $entitySegment, $schemaSegment): float
    {
        if (!$schemaSegment instanceof SchemaFlightSegment) {
            throw new \InvalidArgumentException(sprintf('Expected %s, got %s', SchemaFlightSegment::class, get_class($schemaSegment)));
        }

        $sameMarketingConfirmationNumber = ConfirmationNumberHelper::isSameMarketingConfirmationNumber($entitySegment, $schemaSegment);
        $sameOperatingConfirmationNumber = ConfirmationNumberHelper::isSameOperatingConfirmationNumber($entitySegment, $schemaSegment);
        $sameConfirmationNumber = $sameMarketingConfirmationNumber || $sameOperatingConfirmationNumber;
        $sameFlightNumber = FlightNumberHelper::isSameFlightNumber($entitySegment, $schemaSegment);
        $sameDepartureCode = LocationHelper::isSameLocationCode($entitySegment->getDepcode(), $schemaSegment->departure->airportCode ?? null);
        $sameArrivalCode = LocationHelper::isSameLocationCode($entitySegment->getArrcode(), $schemaSegment->arrival->airportCode ?? null);
        $sameDepartureDate = DateHelper::isSameEntityDateWithSchemaDate($entitySegment->getDepartureDate(), $schemaSegment->departure->localDateTime ?? null);
        $sameArrivalDate = DateHelper::isSameEntityDateWithSchemaDate($entitySegment->getArrivalDate(), $schemaSegment->arrival->localDateTime ?? null);
        $sameDepartureName = LocationHelper::isSameName($entitySegment->getDepname(), $schemaSegment->departure->name);
        $sameArrivalName = LocationHelper::isSameName($entitySegment->getArrname(), $schemaSegment->arrival->name);

        return MatchResult::create()
            ->merge(
                $this->baseMatch(
                    $entitySegment,
                    $schemaSegment->departure->airportCode ?? null,
                    $schemaSegment->arrival->airportCode ?? null,
                    $schemaSegment->departure->name ?? null,
                    $schemaSegment->arrival->name ?? null,
                    $schemaSegment->departure->address->text ?? $schemaSegment->departure->name ?? null,
                    $schemaSegment->arrival->address->text ?? $schemaSegment->arrival->name ?? null,
                    $schemaSegment->departure->localDateTime ?? null,
                    $schemaSegment->arrival->localDateTime ?? null
                )
            )
            ->addResult(
                'flightSegment.sameConfirmationNumber+sameFlightNumber+sameDepartureCode',
                $sameConfirmationNumber && $sameFlightNumber && $sameDepartureCode,
                0.985
            )
            ->addResult(
                'flightSegment.sameDepCode+sameArrCode+sameDepDate+sameArrDate+sameFlightNumber+!sameMarketingConfirmationNumber',
                $sameDepartureCode
                    && $sameArrivalCode
                    && $sameDepartureDate
                    && $sameArrivalDate
                    && $sameFlightNumber
                    && !$sameMarketingConfirmationNumber,
                0.98
            )
            ->addResult(
                'flightSegment.sameDepCode+sameArrCode+sameFlightNumber',
                $sameDepartureCode && $sameArrivalCode && $sameFlightNumber,
                0.5
            )
            ->addResult(
                'flightSegment.sameDepName+sameArrName+sameFlightNumber',
                $sameDepartureName && $sameArrivalName && $sameFlightNumber,
                0.4
            )->maxConfidence();
    }
}
