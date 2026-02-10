<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Segments;

use AwardWallet\MainBundle\Entity\Tripsegment as EntityTrainSegment;
use AwardWallet\Schema\Itineraries\TrainSegment as SchemaTrainSegment;

class TrainRideSegmentMatcher extends AbstractSegmentMatcher
{
    public function match(EntityTrainSegment $entitySegment, $schemaSegment, string $scope): float
    {
        $confidence = parent::match($entitySegment, $schemaSegment, $scope);

        foreach ($this->getMatchers() as $match) {
            $confidence = max($confidence, $match($entitySegment, $schemaSegment, $scope));
        }

        return $confidence;
    }

    protected function getSupportedEntityClass(): string
    {
        return EntityTrainSegment::class;
    }

    protected function getSupportedSchemaClass(): string
    {
        return SchemaTrainSegment::class;
    }

    private function getMatchers(): array
    {
        $sameDepartureStationCode = function (EntityTrainSegment $entityTrainSegment, SchemaTrainSegment $schemaTrainSegment) {
            if (null === $entityTrainSegment->getDepcode()) {
                return false;
            }

            if (null === $schemaTrainSegment->departure || null === $schemaTrainSegment->departure->stationCode) {
                return false;
            }

            return strcasecmp($entityTrainSegment->getDepcode(), $schemaTrainSegment->departure->stationCode) === 0;
        };
        $sameArrivalStationCode = function (EntityTrainSegment $entityTrainSegment, SchemaTrainSegment $schemaTrainSegment) {
            if (null === $entityTrainSegment->getArrcode()) {
                return false;
            }

            if (null === $schemaTrainSegment->arrival || null === $schemaTrainSegment->arrival->stationCode) {
                return false;
            }

            return strcasecmp($entityTrainSegment->getArrcode(), $schemaTrainSegment->arrival->stationCode) === 0;
        };
        $sameDepartureName = function (EntityTrainSegment $entityTrainSegment, SchemaTrainSegment $schemaTrainSegment) {
            return strcasecmp($entityTrainSegment->getDepname(), $schemaTrainSegment->departure->name) === 0;
        };
        $sameArrivalName = function (EntityTrainSegment $entityTrainSegment, SchemaTrainSegment $schemaTrainSegment) {
            return strcasecmp($entityTrainSegment->getArrname(), $schemaTrainSegment->arrival->name) === 0;
        };
        $sameDepartureDate = function (EntityTrainSegment $entityTrainSegment, SchemaTrainSegment $schemaTrainSegment) {
            if ($schemaTrainSegment->departure === null || $schemaTrainSegment->departure->localDateTime === null) {
                return false;
            }
            $schemaDateTime = new \DateTime($schemaTrainSegment->departure->localDateTime);

            return $entityTrainSegment->getDepartureDate() == $schemaDateTime;
        };
        $sameArrivalDate = function (EntityTrainSegment $entityTrainSegment, SchemaTrainSegment $schemaTrainSegment) {
            if ($schemaTrainSegment->arrival === null || $schemaTrainSegment->arrival->localDateTime === null) {
                return false;
            }
            $schemaDateTime = new \DateTime($schemaTrainSegment->arrival->localDateTime);

            return $entityTrainSegment->getArrivalDate() == $schemaDateTime;
        };

        return [
            function (EntityTrainSegment $entityTrainSegment, SchemaTrainSegment $schemaTrainSegment) use (
                $sameDepartureStationCode,
                $sameArrivalStationCode,
                $sameDepartureDate,
                $sameArrivalDate
            ) {
                $match = $sameDepartureStationCode($entityTrainSegment, $schemaTrainSegment)
                    && $sameArrivalStationCode($entityTrainSegment, $schemaTrainSegment)
                    && $sameDepartureDate($entityTrainSegment, $schemaTrainSegment)
                    && $sameArrivalDate($entityTrainSegment, $schemaTrainSegment);

                return 0.97 * (int) $match;
            },
            function (EntityTrainSegment $entityTrainSegment, SchemaTrainSegment $schemaTrainSegment, string $scope) use (
                $sameDepartureStationCode,
                $sameArrivalStationCode
            ) {
                if (SegmentMatcherInterface::ANY === $scope) {
                    return .0;
                }
                $match = $sameDepartureStationCode($entityTrainSegment, $schemaTrainSegment)
                    && $sameArrivalStationCode($entityTrainSegment, $schemaTrainSegment);

                return 0.95 * (int) $match;
            },
            function (EntityTrainSegment $entityTrainSegment, SchemaTrainSegment $schemaTrainSegment, string $scope) use (
                $sameDepartureName,
                $sameArrivalName
            ) {
                if (SegmentMatcherInterface::ANY === $scope) {
                    return .0;
                }
                $match = $sameDepartureName($entityTrainSegment, $schemaTrainSegment)
                    && $sameArrivalName($entityTrainSegment, $schemaTrainSegment);

                return 0.94 * (int) $match;
            },
            function (EntityTrainSegment $entityTrainSegment, SchemaTrainSegment $schemaTrainSegment) use (
                $sameDepartureDate,
                $sameArrivalDate
            ) {
                $match =
                    $sameDepartureDate($entityTrainSegment, $schemaTrainSegment)
                    && $sameArrivalDate($entityTrainSegment, $schemaTrainSegment)
                    && $this->locationMatcher->match(
                        $entityTrainSegment->getDepgeotagid() ?? $entityTrainSegment->getDepname(),
                        $schemaTrainSegment->departure->address->text ?? null,
                        2
                    )
                    && $this->locationMatcher->match(
                        $entityTrainSegment->getArrgeotagid() ?? $entityTrainSegment->getArrname(),
                        $schemaTrainSegment->arrival->address->text ?? null,
                        2
                    );

                return 0.93 * (int) $match;
            },
        ];
    }
}
