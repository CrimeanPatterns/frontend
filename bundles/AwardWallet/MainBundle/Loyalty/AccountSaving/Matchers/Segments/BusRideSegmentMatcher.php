<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Segments;

use AwardWallet\MainBundle\Entity\Tripsegment as EntityBusSegment;
use AwardWallet\Schema\Itineraries\BusSegment as SchemaBusSegment;

class BusRideSegmentMatcher extends AbstractSegmentMatcher
{
    public function match(EntityBusSegment $entitySegment, $schemaSegment, string $scope): float
    {
        $confidence = parent::match($entitySegment, $schemaSegment, $scope);

        foreach ($this->getMatchers() as $match) {
            $confidence = max($confidence, $match($entitySegment, $schemaSegment, $scope));
        }

        return $confidence;
    }

    protected function getSupportedEntityClass(): string
    {
        return EntityBusSegment::class;
    }

    protected function getSupportedSchemaClass(): string
    {
        return SchemaBusSegment::class;
    }

    private function getMatchers(): array
    {
        $sameDepartureStationCode = function (EntityBusSegment $entityBusSegment, SchemaBusSegment $schemaBusSegment) {
            if (null === $entityBusSegment->getDepcode()) {
                return false;
            }

            if (null === $schemaBusSegment->departure->stationCode) {
                return false;
            }

            return strcasecmp($entityBusSegment->getDepcode(), $schemaBusSegment->departure->stationCode) === 0;
        };
        $sameArrivalStationCode = function (EntityBusSegment $entityBusSegment, SchemaBusSegment $schemaBusSegment) {
            if (null === $entityBusSegment->getArrcode()) {
                return false;
            }

            if (null === $schemaBusSegment->arrival->stationCode) {
                return false;
            }

            return $entityBusSegment->getArrcode() === $schemaBusSegment->arrival->stationCode;
        };
        $sameDepartureName = function (EntityBusSegment $entityBusSegment, SchemaBusSegment $schemaBusSegment) {
            return strcasecmp($entityBusSegment->getDepname(), $schemaBusSegment->departure->name) === 0;
        };
        $sameArrivalName = function (EntityBusSegment $entityBusSegment, SchemaBusSegment $schemaBusSegment) {
            return strcasecmp($entityBusSegment->getArrname(), $schemaBusSegment->arrival->name) === 0;
        };
        $sameDepartureDate = function (EntityBusSegment $entityBusSegment, SchemaBusSegment $schemaBusSegment) {
            $schemaDateTime = new \DateTime($schemaBusSegment->departure->localDateTime);

            return $entityBusSegment->getDepartureDate() == $schemaDateTime;
        };
        $sameArrivalDate = function (EntityBusSegment $entityBusSegment, SchemaBusSegment $schemaBusSegment) {
            $schemaDateTime = new \DateTime($schemaBusSegment->arrival->localDateTime);

            return $entityBusSegment->getArrivalDate() == $schemaDateTime;
        };

        return [
            function (EntityBusSegment $entityBusSegment, SchemaBusSegment $schemaBusSegment) use (
                $sameDepartureStationCode,
                $sameArrivalStationCode,
                $sameDepartureDate,
                $sameArrivalDate
            ) {
                $match = $sameDepartureStationCode($entityBusSegment, $schemaBusSegment)
                    && $sameArrivalStationCode($entityBusSegment, $schemaBusSegment)
                    && $sameDepartureDate($entityBusSegment, $schemaBusSegment)
                    && $sameArrivalDate($entityBusSegment, $schemaBusSegment);

                return 0.97 * (int) $match;
            },
            function (EntityBusSegment $entityBusSegment, SchemaBusSegment $schemaBusSegment, string $scope) use (
                $sameDepartureStationCode,
                $sameArrivalStationCode
            ) {
                if (SegmentMatcherInterface::ANY === $scope) {
                    return .0;
                }
                $match = $sameDepartureStationCode($entityBusSegment, $schemaBusSegment)
                    && $sameArrivalStationCode($entityBusSegment, $schemaBusSegment);

                return 0.94 * (int) $match;
            },
            function (EntityBusSegment $entityBusSegment, SchemaBusSegment $schemaBusSegment, string $scope) use (
                $sameDepartureName,
                $sameArrivalName
            ) {
                if (SegmentMatcherInterface::ANY === $scope) {
                    return .0;
                }
                $match = $sameDepartureName($entityBusSegment, $schemaBusSegment)
                    && $sameArrivalName($entityBusSegment, $schemaBusSegment);

                return 0.93 * (int) $match;
            },
        ];
    }
}
