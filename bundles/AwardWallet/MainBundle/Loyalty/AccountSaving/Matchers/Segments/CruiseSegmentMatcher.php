<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Segments;

use AwardWallet\MainBundle\Entity\Tripsegment as EntityCruiseSegment;
use AwardWallet\Schema\Itineraries\CruiseSegment as SchemaCruiseSegment;

class CruiseSegmentMatcher extends AbstractSegmentMatcher
{
    public function match(EntityCruiseSegment $entitySegment, $schemaSegment, string $scope): float
    {
        $confidence = parent::match($entitySegment, $schemaSegment, $scope);

        foreach ($this->getMatchers() as $match) {
            $confidence = max($confidence, $match($entitySegment, $schemaSegment, $scope));
        }

        return $confidence;
    }

    protected function getSupportedEntityClass(): string
    {
        return EntityCruiseSegment::class;
    }

    protected function getSupportedSchemaClass(): string
    {
        return SchemaCruiseSegment::class;
    }

    private function getMatchers(): array
    {
        $sameDepartureStationCode = function (EntityCruiseSegment $entityCruiseSegment, SchemaCruiseSegment $schemaCruiseSegment) {
            if (null === $entityCruiseSegment->getDepcode()) {
                return false;
            }

            if (null === $schemaCruiseSegment->departure->stationCode) {
                return false;
            }

            return strcasecmp($entityCruiseSegment->getDepcode(), $schemaCruiseSegment->departure->stationCode) === 0;
        };
        $sameArrivalStationCode = function (EntityCruiseSegment $entityCruiseSegment, SchemaCruiseSegment $schemaCruiseSegment) {
            if (null === $entityCruiseSegment->getArrcode()) {
                return false;
            }

            if (null === $schemaCruiseSegment->arrival->stationCode) {
                return false;
            }

            return $entityCruiseSegment->getArrcode() === $schemaCruiseSegment->arrival->stationCode;
        };
        $sameDepartureName = function (EntityCruiseSegment $entityCruiseSegment, SchemaCruiseSegment $schemaCruiseSegment) {
            return $entityCruiseSegment->getDepname() === $schemaCruiseSegment->departure->name;
        };
        $sameArrivalName = function (EntityCruiseSegment $entityCruiseSegment, SchemaCruiseSegment $schemaCruiseSegment) {
            return $entityCruiseSegment->getArrname() === $schemaCruiseSegment->arrival->name;
        };
        $sameDepartureDate = function (EntityCruiseSegment $entityCruiseSegment, SchemaCruiseSegment $schemaCruiseSegment) {
            $schemaDateTime = new \DateTime($schemaCruiseSegment->departure->localDateTime);

            return $entityCruiseSegment->getDepartureDate() == $schemaDateTime;
        };
        $sameArrivalDate = function (EntityCruiseSegment $entityCruiseSegment, SchemaCruiseSegment $schemaCruiseSegment) {
            $schemaDateTime = new \DateTime($schemaCruiseSegment->arrival->localDateTime);

            return $entityCruiseSegment->getArrivalDate() == $schemaDateTime;
        };

        return [
            function (EntityCruiseSegment $entityCruiseSegment, SchemaCruiseSegment $schemaCruiseSegment) use (
                $sameDepartureStationCode,
                $sameArrivalStationCode,
                $sameDepartureDate,
                $sameArrivalDate
            ) {
                $match = $sameDepartureStationCode($entityCruiseSegment, $schemaCruiseSegment)
                    && $sameArrivalStationCode($entityCruiseSegment, $schemaCruiseSegment)
                    && $sameDepartureDate($entityCruiseSegment, $schemaCruiseSegment)
                    && $sameArrivalDate($entityCruiseSegment, $schemaCruiseSegment);

                return 0.98 * (int) $match;
            },
            function (EntityCruiseSegment $entityCruiseSegment, SchemaCruiseSegment $schemaCruiseSegment) use (
                $sameDepartureName,
                $sameArrivalName,
                $sameDepartureDate,
                $sameArrivalDate
            ) {
                $match = $sameDepartureName($entityCruiseSegment, $schemaCruiseSegment)
                    && $sameArrivalName($entityCruiseSegment, $schemaCruiseSegment)
                    && $sameDepartureDate($entityCruiseSegment, $schemaCruiseSegment)
                    && $sameArrivalDate($entityCruiseSegment, $schemaCruiseSegment);

                return 0.97 * (int) $match;
            },
            function (EntityCruiseSegment $entityCruiseSegment, SchemaCruiseSegment $schemaCruiseSegment, string $scope) use (
                $sameDepartureStationCode,
                $sameArrivalStationCode
            ) {
                if (SegmentMatcherInterface::ANY === $scope) {
                    return .0;
                }
                $match = $sameDepartureStationCode($entityCruiseSegment, $schemaCruiseSegment)
                    && $sameArrivalStationCode($entityCruiseSegment, $schemaCruiseSegment);

                return 0.96 * (int) $match;
            },
            function (EntityCruiseSegment $entityCruiseSegment, SchemaCruiseSegment $schemaCruiseSegment, string $scope) use (
                $sameDepartureName,
                $sameArrivalName
            ) {
                if (SegmentMatcherInterface::ANY === $scope) {
                    return .0;
                }
                $match = $sameDepartureName($entityCruiseSegment, $schemaCruiseSegment)
                    && $sameArrivalName($entityCruiseSegment, $schemaCruiseSegment);

                return 0.95 * (int) $match;
            },
        ];
    }
}
