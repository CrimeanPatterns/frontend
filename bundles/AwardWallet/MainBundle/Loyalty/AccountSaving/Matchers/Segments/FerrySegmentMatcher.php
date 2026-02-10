<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Segments;

use AwardWallet\MainBundle\Entity\Tripsegment as EntityFerrySegment;
use AwardWallet\Schema\Itineraries\FerrySegment as SchemaFerrySegment;

class FerrySegmentMatcher extends AbstractSegmentMatcher
{
    public function match(EntityFerrySegment $entitySegment, $schemaSegment, string $scope): float
    {
        $confidence = parent::match($entitySegment, $schemaSegment, $scope);

        foreach ($this->getMatchers() as $match) {
            $confidence = max($confidence, $match($entitySegment, $schemaSegment, $scope));
        }

        return $confidence;
    }

    protected function getSupportedEntityClass(): string
    {
        return EntityFerrySegment::class;
    }

    protected function getSupportedSchemaClass(): string
    {
        return SchemaFerrySegment::class;
    }

    private function getMatchers(): array
    {
        $sameDepartureStationCode = function (EntityFerrySegment $entityFerrySegment, SchemaFerrySegment $schemaFerrySegment) {
            if (null === $entityFerrySegment->getDepcode()) {
                return false;
            }

            if (null === $schemaFerrySegment->departure->stationCode) {
                return false;
            }

            return strcasecmp($entityFerrySegment->getDepcode(), $schemaFerrySegment->departure->stationCode) === 0;
        };
        $sameArrivalStationCode = function (EntityFerrySegment $entityFerrySegment, SchemaFerrySegment $schemaFerrySegment) {
            if (null === $entityFerrySegment->getArrcode()) {
                return false;
            }

            if (null === $schemaFerrySegment->arrival->stationCode) {
                return false;
            }

            return strcasecmp($entityFerrySegment->getArrcode(), $schemaFerrySegment->arrival->stationCode) === 0;
        };
        $sameDepartureName = function (EntityFerrySegment $entityFerrySegment, SchemaFerrySegment $schemaFerrySegment) {
            return $entityFerrySegment->getDepname() === $schemaFerrySegment->departure->name;
        };
        $sameArrivalName = function (EntityFerrySegment $entityFerrySegment, SchemaFerrySegment $schemaFerrySegment) {
            return $entityFerrySegment->getArrname() === $schemaFerrySegment->arrival->name;
        };
        $sameDepartureDate = function (EntityFerrySegment $entityFerrySegment, SchemaFerrySegment $schemaFerrySegment) {
            $schemaDateTime = new \DateTime($schemaFerrySegment->departure->localDateTime);

            return $entityFerrySegment->getDepartureDate() == $schemaDateTime;
        };
        $sameArrivalDate = function (EntityFerrySegment $entityFerrySegment, SchemaFerrySegment $schemaFerrySegment) {
            $schemaDateTime = new \DateTime($schemaFerrySegment->arrival->localDateTime);

            return $entityFerrySegment->getArrivalDate() == $schemaDateTime;
        };

        return [
            function (EntityFerrySegment $entityFerrySegment, SchemaFerrySegment $schemaFerrySegment) use (
                $sameDepartureStationCode,
                $sameArrivalStationCode,
                $sameDepartureDate,
                $sameArrivalDate
            ) {
                $match = $sameDepartureStationCode($entityFerrySegment, $schemaFerrySegment)
                    && $sameArrivalStationCode($entityFerrySegment, $schemaFerrySegment)
                    && $sameDepartureDate($entityFerrySegment, $schemaFerrySegment)
                    && $sameArrivalDate($entityFerrySegment, $schemaFerrySegment);

                return 0.98 * (int) $match;
            },
            function (EntityFerrySegment $entityFerrySegment, SchemaFerrySegment $schemaFerrySegment) use (
                $sameDepartureName,
                $sameArrivalName,
                $sameDepartureDate,
                $sameArrivalDate
            ) {
                $match = $sameDepartureName($entityFerrySegment, $schemaFerrySegment)
                    && $sameArrivalName($entityFerrySegment, $schemaFerrySegment)
                    && $sameDepartureDate($entityFerrySegment, $schemaFerrySegment)
                    && $sameArrivalDate($entityFerrySegment, $schemaFerrySegment);

                return 0.97 * (int) $match;
            },
            function (EntityFerrySegment $entityFerrySegment, SchemaFerrySegment $schemaFerrySegment, string $scope) use (
                $sameDepartureStationCode,
                $sameArrivalStationCode
            ) {
                if (SegmentMatcherInterface::ANY === $scope) {
                    return .0;
                }
                $match = $sameDepartureStationCode($entityFerrySegment, $schemaFerrySegment)
                    && $sameArrivalStationCode($entityFerrySegment, $schemaFerrySegment);

                return 0.96 * (int) $match;
            },
            function (EntityFerrySegment $entityFerrySegment, SchemaFerrySegment $schemaFerrySegment, string $scope) use (
                $sameDepartureName,
                $sameArrivalName
            ) {
                if (SegmentMatcherInterface::ANY === $scope) {
                    return .0;
                }
                $match = $sameDepartureName($entityFerrySegment, $schemaFerrySegment)
                    && $sameArrivalName($entityFerrySegment, $schemaFerrySegment);

                return 0.95 * (int) $match;
            },
        ];
    }
}
