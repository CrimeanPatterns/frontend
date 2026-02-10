<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Matcher\Helper;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Tripsegment as EntitySegment;
use AwardWallet\Schema\Itineraries\ConfNo;
use AwardWallet\Schema\Itineraries\Flight as SchemaFlight;
use AwardWallet\Schema\Itineraries\FlightSegment as SchemaFlightSegment;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;

/**
 * @NoDI
 */
class ConfirmationNumberHelper
{
    public static function isSame(?string $number1, ?string $number2, bool $strict = false, bool $filter = true): bool
    {
        if ($filter) {
            $number1 = static::filterConfirmationNumber($number1);
            $number2 = static::filterConfirmationNumber($number2);
        }

        if (empty($number1) || empty($number2)) {
            return false;
        }

        $result = strcasecmp($number1, $number2) === 0;

        if ($strict || $result) {
            return $result;
        }

        if (
            (
                \strlen($number1) >= 10
                && strpos($number2, $number1) === 0
            )
            || (
                \strlen($number2) >= 10
                && strpos($number1, $number2) === 0
            )
        ) {
            return true;
        }

        return false;
    }

    public static function isSameAny(array $numbers1, array $numbers2, bool $strict = false, bool $filter = true): bool
    {
        foreach ($numbers1 as $number1) {
            foreach ($numbers2 as $number2) {
                if (static::isSame($number1, $number2, $strict, $filter)) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function isSamePrimaryConfirmationNumber(
        EntityItinerary $entityItinerary,
        SchemaItinerary $schemaItinerary,
        bool $strict = false
    ): bool {
        $schemaConfirmationNumber = static::extractSchemaPrimaryConfirmationNumber($schemaItinerary);
        $entityConfirmationNumber = $entityItinerary->getConfirmationNumber();

        return static::isSame($entityConfirmationNumber, $schemaConfirmationNumber, $strict);
    }

    public static function isSameTravelAgencyConfirmationNumber(
        EntityItinerary $entityItinerary,
        SchemaItinerary $schemaItinerary,
        bool $strict = false
    ): bool {
        $schemaTravelAgencyNumbers = static::filterConfirmationNumbers(array_map(function (ConfNo $number) {
            return $number->number;
        }, $schemaItinerary->travelAgency->confirmationNumbers ?? []));
        $entityTravelAgencyNumbers = static::filterConfirmationNumbers($entityItinerary->getTravelAgencyConfirmationNumbers());

        foreach ($entityTravelAgencyNumbers as $entityTravelAgencyNumber) {
            foreach ($schemaTravelAgencyNumbers as $schemaTravelAgencyNumber) {
                if (static::isSame($entityTravelAgencyNumber, $schemaTravelAgencyNumber, $strict, false)) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function isSameAllConfirmationNumbers(
        EntityItinerary $entityItinerary,
        SchemaItinerary $schemaItinerary,
        bool $strict = false
    ): bool {
        $schemaConfirmationNumbers = static::filterConfirmationNumbers(array_map(function (ConfNo $number) {
            return $number->number;
        }, array_merge(
            $schemaItinerary->confirmationNumbers ?? [],
            $schemaItinerary->travelAgency->confirmationNumbers ?? []
        )));

        $entityConfirmationNumbers = static::filterConfirmationNumbers(array_merge(
            [$entityItinerary->getConfirmationNumber()],
            $entityItinerary->getTravelAgencyConfirmationNumbers()
        ));

        if (\count($schemaConfirmationNumbers) !== \count($entityConfirmationNumbers) || empty($schemaConfirmationNumbers)) {
            return false;
        }

        // match all numbers
        foreach ($entityConfirmationNumbers as $entityConfirmationNumber) {
            $found = false;

            foreach ($schemaConfirmationNumbers as $k => $schemaConfirmationNumber) {
                if (static::isSame($entityConfirmationNumber, $schemaConfirmationNumber, $strict, false)) {
                    $found = true;
                    unset($schemaConfirmationNumbers[$k]);

                    break;
                }
            }

            if (!$found) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param EntityItinerary|Trip $entityItinerary
     */
    public static function isSameIssuingConfirmationNumber(
        EntityItinerary $entityItinerary,
        SchemaFlight $schemaFlight,
        bool $strict = false
    ): bool {
        return static::isSame(
            $entityItinerary->getIssuingAirlineConfirmationNumber(),
            $schemaFlight->issuingCarrier->confirmationNumber ?? null,
            $strict
        );
    }

    public static function isSameMarketingConfirmationNumber(
        EntitySegment $entitySegment,
        SchemaFlightSegment $schemaSegment,
        bool $strict = false
    ): bool {
        return static::isSame(
            $entitySegment->getMarketingAirlineConfirmationNumber(),
            $schemaSegment->marketingCarrier->confirmationNumber ?? null,
            $strict
        );
    }

    /**
     * @param EntityItinerary|Trip $entityItinerary
     */
    public static function isSameAnyMarketingConfirmationNumber(
        EntityItinerary $entityItinerary,
        SchemaFlight $schemaFlight,
        bool $strict = false
    ): bool {
        $schemaMarketingNumbers = static::filterConfirmationNumbers(array_map(function (SchemaFlightSegment $segment) {
            return $segment->marketingCarrier->confirmationNumber ?? null;
        }, $schemaFlight->segments));
        $entityMarketingNumbers = static::filterConfirmationNumbers(array_map(function (EntitySegment $entitySegment) {
            return $entitySegment->getMarketingAirlineConfirmationNumber();
        }, $entityItinerary->getSegments()));

        foreach ($entityMarketingNumbers as $entityMarketingNumber) {
            foreach ($schemaMarketingNumbers as $schemaMarketingNumber) {
                if (static::isSame($entityMarketingNumber, $schemaMarketingNumber, $strict, false)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param EntityItinerary|Trip $entityItinerary
     */
    public static function isSameAnyFlightConfirmationNumber(
        EntityItinerary $entityItinerary,
        SchemaFlight $schemaFlight,
        bool $strict = false
    ): bool {
        $schemaConfirmationNumbers = static::filterConfirmationNumbers(array_merge(
            [$schemaFlight->issuingCarrier->confirmationNumber ?? null],
            array_map(fn (ConfNo $number) => $number->number, $schemaFlight->travelAgency->confirmationNumbers ?? []),
            array_map(fn (SchemaFlightSegment $segment) => $segment->marketingCarrier->confirmationNumber ?? null, $schemaFlight->segments)
        ));
        $entityConfirmationNumbers = static::filterConfirmationNumbers(array_merge(
            [$entityItinerary->getIssuingAirlineConfirmationNumber()],
            $entityItinerary->getTravelAgencyConfirmationNumbers(),
            array_map(fn (EntitySegment $entitySegment) => $entitySegment->getMarketingAirlineConfirmationNumber(), $entityItinerary->getSegments())
        ));

        foreach ($entityConfirmationNumbers as $entityConfirmationNumber) {
            foreach ($schemaConfirmationNumbers as $schemaConfirmationNumber) {
                if (static::isSame($entityConfirmationNumber, $schemaConfirmationNumber, $strict, false)) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function isSameOperatingConfirmationNumber(EntitySegment $entitySegment, SchemaFlightSegment $schemaSegment): bool
    {
        return static::isSame(
            $entitySegment->getOperatingAirlineConfirmationNumber(),
            $schemaSegment->operatingCarrier->confirmationNumber ?? null
        );
    }

    /**
     * Check if the provider confirmation number is the same but the confirmation number is different.
     * Check also the travel agency confirmation number.
     */
    public static function isSameProviderButDifferentConfirmationNumber(
        EntityItinerary $entityItinerary,
        SchemaItinerary $schemaItinerary,
        bool $strict = false
    ): bool {
        // compare provider confirmation numbers
        $schemaConfirmationNumbers = static::filterConfirmationNumbers(
            array_map(
                fn (ConfNo $number) => $number->number,
                $schemaItinerary->confirmationNumbers ?? []
            )
        );
        $entityConfirmationNumbers = static::filterConfirmationNumbers([$entityItinerary->getConfirmationNumber()]);
        $schemaProvider = $schemaItinerary->providerInfo->code ?? null;
        $entityProvider = $entityItinerary->getRealProvider() ? $entityItinerary->getRealProvider()->getCode() : null;

        if (
            !empty($schemaProvider)
            && $schemaProvider === $entityProvider
            && !empty($schemaConfirmationNumbers)
            && !empty($entityConfirmationNumbers)
            && !static::isSameAny($entityConfirmationNumbers, $schemaConfirmationNumbers, $strict, false)
        ) {
            return true;
        }

        // compare travel agency confirmation numbers
        $schemaTravelAgencyNumbers = static::filterConfirmationNumbers(
            array_map(
                fn (ConfNo $number) => $number->number,
                $schemaItinerary->travelAgency->confirmationNumbers ?? []
            )
        );
        $entityTravelAgencyNumbers = static::filterConfirmationNumbers($entityItinerary->getTravelAgencyConfirmationNumbers());
        $schemaProvider = $schemaItinerary->travelAgency->providerInfo->code ?? null;
        $entityProvider = $entityItinerary->getTravelAgency() ? $entityItinerary->getTravelAgency()->getCode() : null;

        if (
            !empty($schemaProvider)
            && $schemaProvider === $entityProvider
            && !empty($schemaTravelAgencyNumbers)
            && !empty($entityTravelAgencyNumbers)
            && !static::isSameAny($entityTravelAgencyNumbers, $schemaTravelAgencyNumbers, $strict, false)
        ) {
            return true;
        }

        return false;
    }

    public static function filterConfirmationNumber(?string $number): ?string
    {
        if (empty($number)) {
            return null;
        }

        $number = strtolower(trim(str_replace('-', '', $number)));

        return empty($number) ? null : $number;
    }

    /**
     * @param string[]|null[] $numbers
     */
    public static function filterConfirmationNumbers(array $numbers): array
    {
        return array_unique(array_filter(array_map(function (?string $number) {
            return static::filterConfirmationNumber($number);
        }, $numbers)));
    }

    public static function extractSchemaPrimaryConfirmationNumber(SchemaItinerary $itinerary): ?string
    {
        $confirmationNumbers = array_merge(
            $itinerary->confirmationNumbers ?? [],
            $itinerary->travelAgency->confirmationNumbers ?? []
        );
        $firstNumber = null;

        foreach ($confirmationNumbers as $confirmationNumber) {
            if (is_null($firstNumber)) {
                $firstNumber = $confirmationNumber->number;
            }

            if ($confirmationNumber->isPrimary) {
                return $confirmationNumber->number;
            }
        }

        return $firstNumber;
    }
}
