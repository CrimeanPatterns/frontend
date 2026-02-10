<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Segments;

use AwardWallet\MainBundle\Entity\Tripsegment as EntityTransferSegment;
use AwardWallet\Schema\Itineraries\TransferSegment as SchemaTransferSegment;

class TransferSegmentMatcher extends AbstractSegmentMatcher
{
    public function match(EntityTransferSegment $entitySegment, $schemaSegment, string $scope): float
    {
        $confidence = parent::match($entitySegment, $schemaSegment, $scope);

        foreach ($this->getMatchers() as $match) {
            $confidence = max($confidence, $match($entitySegment, $schemaSegment, $scope));
        }

        return $confidence;
    }

    protected function getSupportedEntityClass(): string
    {
        return EntityTransferSegment::class;
    }

    protected function getSupportedSchemaClass(): string
    {
        return SchemaTransferSegment::class;
    }

    private function getMatchers(): array
    {
        $sameDepartureStationCode = function (EntityTransferSegment $entityTransferSegment, SchemaTransferSegment $schemaTransferSegment) {
            if (null === $entityTransferSegment->getDepcode()) {
                return false;
            }

            if (null === $schemaTransferSegment->departure->airportCode) {
                return false;
            }

            return strcasecmp($entityTransferSegment->getDepcode(), $schemaTransferSegment->departure->airportCode) === 0;
        };
        $sameArrivalStationCode = function (EntityTransferSegment $entityTransferSegment, SchemaTransferSegment $schemaTransferSegment) {
            if (null === $entityTransferSegment->getArrcode()) {
                return false;
            }

            if (null === $schemaTransferSegment->arrival->airportCode) {
                return false;
            }

            return strcasecmp($entityTransferSegment->getArrcode(), $schemaTransferSegment->arrival->airportCode) === 0;
        };
        $sameDepartureName = function (EntityTransferSegment $entityTransferSegment, SchemaTransferSegment $schemaTransferSegment) {
            return strcasecmp($entityTransferSegment->getDepname(), $schemaTransferSegment->departure->name) === 0;
        };
        $sameArrivalName = function (EntityTransferSegment $entityTransferSegment, SchemaTransferSegment $schemaTransferSegment) {
            return strcasecmp($entityTransferSegment->getArrname(), $schemaTransferSegment->arrival->name) === 0;
        };
        $sameDepartureDate = function (EntityTransferSegment $entityTransferSegment, SchemaTransferSegment $schemaTransferSegment) {
            $schemaDateTime = new \DateTime($schemaTransferSegment->departure->localDateTime);

            return $entityTransferSegment->getDepartureDate() == $schemaDateTime;
        };
        $sameArrivalDate = function (EntityTransferSegment $entityTransferSegment, SchemaTransferSegment $schemaTransferSegment) {
            $schemaDateTime = new \DateTime($schemaTransferSegment->arrival->localDateTime);

            return $entityTransferSegment->getArrivalDate() == $schemaDateTime;
        };

        return [
            function (EntityTransferSegment $entityTransferSegment, SchemaTransferSegment $schemaTransferSegment) use (
                $sameDepartureStationCode,
                $sameArrivalStationCode,
                $sameDepartureDate,
                $sameArrivalDate
            ) {
                $match = $sameDepartureStationCode($entityTransferSegment, $schemaTransferSegment)
                    && $sameArrivalStationCode($entityTransferSegment, $schemaTransferSegment)
                    && $sameDepartureDate($entityTransferSegment, $schemaTransferSegment)
                    && $sameArrivalDate($entityTransferSegment, $schemaTransferSegment);

                return 0.98 * (int) $match;
            },
            function (EntityTransferSegment $entityTransferSegment, SchemaTransferSegment $schemaTransferSegment, string $scope) use (
                $sameDepartureStationCode,
                $sameArrivalStationCode
            ) {
                if (SegmentMatcherInterface::ANY === $scope) {
                    return .0;
                }
                $match = $sameDepartureStationCode($entityTransferSegment, $schemaTransferSegment)
                    && $sameArrivalStationCode($entityTransferSegment, $schemaTransferSegment);

                return 0.96 * (int) $match;
            },
            function (EntityTransferSegment $entityTransferSegment, SchemaTransferSegment $schemaTransferSegment, string $scope) use (
                $sameDepartureName,
                $sameArrivalName
            ) {
                if (SegmentMatcherInterface::ANY === $scope) {
                    return .0;
                }
                $match = $sameDepartureName($entityTransferSegment, $schemaTransferSegment)
                    && $sameArrivalName($entityTransferSegment, $schemaTransferSegment);

                return 0.95 * (int) $match;
            },
        ];
    }
}
