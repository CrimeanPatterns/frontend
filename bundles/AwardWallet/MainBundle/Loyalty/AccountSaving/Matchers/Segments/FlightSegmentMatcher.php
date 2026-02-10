<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Segments;

use AwardWallet\MainBundle\Entity\Tripsegment as EntityFlightSegment;
use AwardWallet\Schema\Itineraries\FlightSegment as SchemaFlightSegment;

class FlightSegmentMatcher extends AbstractSegmentMatcher
{
    public function match(EntityFlightSegment $entitySegment, $schemaSegment, string $scope): float
    {
        $confidence = parent::match($entitySegment, $schemaSegment, $scope);

        foreach ($this->getMatchers() as $match) {
            $confidence = max($confidence, $match($entitySegment, $schemaSegment, $scope));
        }

        return $confidence;
    }

    protected function getSupportedEntityClass(): string
    {
        return EntityFlightSegment::class;
    }

    protected function getSupportedSchemaClass(): string
    {
        return SchemaFlightSegment::class;
    }

    private function getMatchers(): array
    {
        $sameConfirmationNumbers = function (?string $firstNumber, ?string $secondNumber) {
            if (null === $firstNumber || null === $secondNumber) {
                return false;
            }

            return strcasecmp($firstNumber, $secondNumber) === 0;
        };
        $sameMarketingConfirmationNumbers = fn (
            EntityFlightSegment $entityFlightSegment,
            SchemaFlightSegment $schemaFlightSegment
        ) => $sameConfirmationNumbers(
            $schemaFlightSegment->marketingCarrier->confirmationNumber,
            $entityFlightSegment->getMarketingAirlineConfirmationNumber()
        );
        $sameConfirmationNumber = function (
            EntityFlightSegment $entityFlightSegment,
            SchemaFlightSegment $schemaFlightSegment
        ) use ($sameConfirmationNumbers, $sameMarketingConfirmationNumbers) {
            $sameMarketingConfNo = $sameMarketingConfirmationNumbers($entityFlightSegment, $schemaFlightSegment);
            $sameOperatingConfirmationNumbers = false;

            if (null !== $schemaFlightSegment->operatingCarrier) {
                $sameOperatingConfirmationNumbers = $sameConfirmationNumbers(
                    $schemaFlightSegment->operatingCarrier->confirmationNumber,
                    $entityFlightSegment->getOperatingAirlineConfirmationNumber()
                );
            }

            if ($sameMarketingConfNo || $sameOperatingConfirmationNumbers) {
                return true;
            }

            return false;
        };
        $sameFlightNumber = function (
            EntityFlightSegment $entityFlightSegment,
            SchemaFlightSegment $schemaFlightSegment
        ) {
            $entityFlightNumbers = array_filter([
                $entityFlightSegment->getFlightNumber(),
                $entityFlightSegment->getOperatingAirlineFlightNumber(),
            ]);
            $schemaFlightNumbers = array_filter([
                $schemaFlightSegment->marketingCarrier->flightNumber,
                $schemaFlightSegment->operatingCarrier ? $schemaFlightSegment->operatingCarrier->flightNumber : null,
            ]);

            if (empty($entityFlightNumbers) || empty($schemaFlightNumbers)) {
                return false;
            }

            return !empty(array_intersect($entityFlightNumbers, $schemaFlightNumbers));
        };
        $sameDepartureAirportCode = function (
            EntityFlightSegment $entityFlightSegment,
            SchemaFlightSegment $schemaFlightSegment
        ) {
            if (null === $entityFlightSegment->getDepcode()) {
                return false;
            }

            if (null === $schemaFlightSegment->departure) {
                return false;
            }

            if (null === $schemaFlightSegment->departure->airportCode) {
                return false;
            }

            return strcasecmp($entityFlightSegment->getDepcode(), $schemaFlightSegment->departure->airportCode) === 0;
        };
        $sameArrivalAirportCode = function (
            EntityFlightSegment $entityFlightSegment,
            SchemaFlightSegment $schemaFlightSegment
        ) {
            if (null === $entityFlightSegment->getArrcode()) {
                return false;
            }

            if (null === $schemaFlightSegment->arrival) {
                return false;
            }

            if (null === $schemaFlightSegment->arrival->airportCode) {
                return false;
            }

            return strcasecmp($entityFlightSegment->getArrcode(), $schemaFlightSegment->arrival->airportCode) === 0;
        };
        $sameDepartureName = function (
            EntityFlightSegment $entityFlightSegment,
            SchemaFlightSegment $schemaFlightSegment
        ) {
            return
                $schemaFlightSegment->departure !== null
                && $schemaFlightSegment->departure->name !== null
                && strcasecmp($entityFlightSegment->getDepname(), $schemaFlightSegment->departure->name) === 0
            ;
        };
        $sameArrivalName = function (
            EntityFlightSegment $entityFlightSegment,
            SchemaFlightSegment $schemaFlightSegment
        ) {
            return $schemaFlightSegment->arrival !== null && strcasecmp($entityFlightSegment->getArrname(), $schemaFlightSegment->arrival->name) === 0;
        };
        $sameDepartureDate = function (
            EntityFlightSegment $entityFlightSegment,
            SchemaFlightSegment $schemaFlightSegment
        ) {
            if ($schemaFlightSegment->departure === null || $schemaFlightSegment->departure->localDateTime === null) {
                return false;
            }
            $schemaDateTime = new \DateTime($schemaFlightSegment->departure->localDateTime);

            return $entityFlightSegment->getDepartureDate() == $schemaDateTime;
        };
        $sameArrivalDate = function (
            EntityFlightSegment $entityFlightSegment,
            SchemaFlightSegment $schemaFlightSegment
        ) {
            if ($schemaFlightSegment->arrival === null || $schemaFlightSegment->arrival->localDateTime === null) {
                return false;
            }
            $schemaDateTime = new \DateTime($schemaFlightSegment->arrival->localDateTime);

            return $entityFlightSegment->getArrivalDate() == $schemaDateTime;
        };

        return [
            function (EntityFlightSegment $entityFlightSegment, SchemaFlightSegment $schemaFlightSegment) use (
                $sameConfirmationNumber,
                $sameFlightNumber,
                $sameDepartureAirportCode
            ) {
                $match = $sameConfirmationNumber($entityFlightSegment, $schemaFlightSegment)
                    && $sameFlightNumber($entityFlightSegment, $schemaFlightSegment)
                    && $sameDepartureAirportCode($entityFlightSegment, $schemaFlightSegment);

                return 0.99 * (int) $match;
            },
            function (EntityFlightSegment $entityFlightSegment, SchemaFlightSegment $schemaFlightSegment) use (
                $sameDepartureAirportCode,
                $sameArrivalAirportCode,
                $sameDepartureDate,
                $sameArrivalDate,
                $sameFlightNumber,
                $sameMarketingConfirmationNumbers
            ) {
                $match = $sameDepartureAirportCode($entityFlightSegment, $schemaFlightSegment)
                    && $sameArrivalAirportCode($entityFlightSegment, $schemaFlightSegment)
                    && $sameDepartureDate($entityFlightSegment, $schemaFlightSegment)
                    && $sameArrivalDate($entityFlightSegment, $schemaFlightSegment)
                    && $sameFlightNumber($entityFlightSegment, $schemaFlightSegment)
                    && !$sameMarketingConfirmationNumbers($entityFlightSegment, $schemaFlightSegment);

                return 0.98 * (int) $match;
            },
            function (EntityFlightSegment $entityFlightSegment, SchemaFlightSegment $schemaFlightSegment, string $scope) use (
                $sameDepartureAirportCode,
                $sameArrivalAirportCode,
                $sameFlightNumber
            ) {
                if (self::ANY === $scope) {
                    return .0;
                }
                $match = $sameDepartureAirportCode($entityFlightSegment, $schemaFlightSegment)
                    && $sameArrivalAirportCode($entityFlightSegment, $schemaFlightSegment)
                    && $sameFlightNumber($entityFlightSegment, $schemaFlightSegment);

                return 0.95 * (int) $match;
            },
            function (EntityFlightSegment $entityFlightSegment, SchemaFlightSegment $schemaFlightSegment, string $scope) use (
                $sameDepartureAirportCode,
                $sameArrivalAirportCode
            ) {
                if (self::ANY === $scope) {
                    return .0;
                }
                $match = $sameDepartureAirportCode($entityFlightSegment, $schemaFlightSegment)
                    && $sameArrivalAirportCode($entityFlightSegment, $schemaFlightSegment);

                return 0.9 * (int) $match;
            },
            function (EntityFlightSegment $entityFlightSegment, SchemaFlightSegment $schemaFlightSegment, string $scope) use (
                $sameDepartureName,
                $sameArrivalName,
                $sameFlightNumber
            ) {
                if (self::ANY === $scope) {
                    return .0;
                }
                $match = $sameDepartureName($entityFlightSegment, $schemaFlightSegment)
                    && $sameArrivalName($entityFlightSegment, $schemaFlightSegment)
                    && $sameFlightNumber($entityFlightSegment, $schemaFlightSegment);

                return 0.85 * (int) $match;
            },
            function (EntityFlightSegment $entityFlightSegment, SchemaFlightSegment $schemaFlightSegment, string $scope) use (
                $sameDepartureName,
                $sameArrivalName
            ) {
                if (self::ANY === $scope) {
                    return .0;
                }
                $match = $sameDepartureName($entityFlightSegment, $schemaFlightSegment)
                    && $sameArrivalName($entityFlightSegment, $schemaFlightSegment);

                return 0.8 * (int) $match;
            },
        ];
    }
}
