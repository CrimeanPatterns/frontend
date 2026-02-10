<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Matcher\Helper;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Entity\Tripsegment as EntitySegment;
use AwardWallet\Schema\Itineraries\FlightSegment as SchemaFlightSegment;

/**
 * @NoDI
 */
class FlightNumberHelper
{
    public static function isSameFlightNumber(EntitySegment $entitySegment, SchemaFlightSegment $schemaSegment): bool
    {
        $entityFlightNumbers = static::filterFlightNumbers([
            $entitySegment->getFlightNumber(),
            $entitySegment->getOperatingAirlineFlightNumber(),
        ]);
        $schemaFlightNumbers = static::filterFlightNumbers([
            $schemaSegment->marketingCarrier->flightNumber ?? null,
            $schemaSegment->operatingCarrier->flightNumber ?? null,
        ]);

        if (empty($entityFlightNumbers) || empty($schemaFlightNumbers)) {
            return false;
        }

        return !empty(array_intersect($entityFlightNumbers, $schemaFlightNumbers));
    }

    public static function filterFlightNumber(?string $number): ?string
    {
        if (empty($number)) {
            return null;
        }

        $number = strtolower(trim($number));

        return empty($number) ? null : $number;
    }

    /**
     * @param string[]|null[] $numbers
     */
    public static function filterFlightNumbers(array $numbers): array
    {
        return array_unique(array_filter(array_map(function (?string $number) {
            return static::filterFlightNumber($number);
        }, $numbers)));
    }
}
