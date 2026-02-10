<?php

namespace AwardWallet\MainBundle\Timeline\TripInfo;

use AwardWallet\MainBundle\Entity\Airline;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Globals\Utils\None;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function AwardWallet\MainBundle\Globals\Utils\none;

class TripInfo
{
    public ?TripNumberInfo $primaryTripNumberInfo = null;

    public ?ConfirmationNumberInfo $primaryConfirmationNumberInfo = null;

    public ?TripNumberInfo $secondaryTripNumberInfo = null;

    public ?string $secondaryConfirmationNumber = null;

    public static function createFromTripSegment(TripSegment $tripSegment): self
    {
        $tripInfo = new self();
        $tripInfo->primaryTripNumberInfo = self::createPrimaryTripNumberInfo($tripSegment);
        $tripInfo->primaryConfirmationNumberInfo = self::createPrimaryConfrimationNumberInfo($tripSegment);
        self::populateIssuingInfo($tripSegment, $tripInfo);

        return $tripInfo;
    }

    protected static function createPrimaryTripNumberInfo(Tripsegment $tripSegment): ?TripNumberInfo
    {
        $dataGen = function () use ($tripSegment) {
            $operatingAirline = $tripSegment->getOperatingAirline() ?? $tripSegment->getOperatingAirlineName();
            $marketingAirline = $tripSegment->getMarketingAirline() ?? $tripSegment->getMarketingAirlineName();
            $marketingAirlineIsOperatingAirline =
                (
                    \is_string($operatingAirline)
                    && \is_string($marketingAirline)
                    && StringUtils::isAllNotEmpty($operatingAirline, $marketingAirline)
                    && ($operatingAirline === $marketingAirline)
                )
                || (
                    ($operatingAirline instanceof Airline)
                    && ($marketingAirline instanceof Airline)
                    && ($marketingAirline->getAirlineid() === $operatingAirline->getAirlineid())
                );

            if ($marketingAirlineIsOperatingAirline) {
                yield from it([
                    $marketingAirline,
                    $operatingAirline,
                ])
                    ->product([
                        $tripSegment->getMarketingFlightNumber(),
                        $tripSegment->getOperatingAirlineFlightNumber(),
                    ]);

                yield [
                    $marketingAirline,
                    none(),
                ];
            } else {
                yield [
                    $operatingAirline,
                    $tripSegment->getOperatingAirlineFlightNumber(),
                ];

                yield [
                    $operatingAirline,
                    none(),
                ];

                yield [
                    $marketingAirline,
                    $tripSegment->getMarketingFlightNumber(),
                ];

                yield [
                    $marketingAirline,
                    none(),
                ];
            }

            $trip = $tripSegment->getTripid();

            yield [
                $trip->getIssuingAirline() ?? $trip->getIssuingAirlineName(),
                none(),
            ];

            $provider = $trip ? $trip->getProvider() : null;

            yield [
                $provider,
                $tripSegment->getFlightNumber(),
            ];

            yield [
                $provider,
                none(),
            ];
        };

        $firstValidTuple = self::getFirstValidTuple($dataGen());

        if ($firstValidTuple) {
            [$airline, $tripNumber] = $firstValidTuple;

            return new TripNumberInfo(
                $tripNumber,
                $airline
            );
        }

        return null;
    }

    protected static function createPrimaryConfrimationNumberInfo(Tripsegment $tripSegment): ?ConfirmationNumberInfo
    {
        $dataGen = function () use ($tripSegment) {
            yield [
                $tripSegment->getOperatingAirline() ?? $tripSegment->getOperatingAirlineName(),
                $tripSegment->getOperatingAirlineConfirmationNumber(),
            ];

            $trip = $tripSegment->getTripid();

            yield [
                $trip->getIssuingAirline() ?? $trip->getIssuingAirlineName(),
                $trip->getIssuingAirlineConfirmationNumber(),
            ];

            yield [
                $tripSegment->getMarketingAirline() ?? $tripSegment->getMarketingAirlineName(),
                $tripSegment->getMarketingAirlineConfirmationNumber(),
            ];

            yield [
                none(),
                $tripSegment->getOperatingAirlineConfirmationNumber(),
            ];

            yield [
                none(),
                $trip->getIssuingAirlineConfirmationNumber(),
            ];

            yield [
                none(),
                $tripSegment->getMarketingAirlineConfirmationNumber(),
            ];
        };

        $firstValidTuple = self::getFirstValidTuple($dataGen());

        if ($firstValidTuple) {
            [$airline, $confirmationNumber] = $firstValidTuple;

            return new ConfirmationNumberInfo(
                $confirmationNumber,
                $airline
            );
        }

        return null;
    }

    protected static function populateIssuingInfo(Tripsegment $tripSegment, self $tripInfo): void
    {
        $secondaryTuple =
            it(self::createSecondaryInfoGenerator($tripSegment))
            ->filter(function (array $tuple3) { return StringUtils::isAllNotEmpty(...$tuple3); })
            ->map(function (array $tuple3): array { return self::normalizeTupleValues($tuple3); })
            ->filter(function (array $tuple3) use ($tripInfo) {
                [$companyInfo] = $tuple3;

                return
                    isset($tripInfo->primaryTripNumberInfo->companyInfo)
                    && !$companyInfo->equals($tripInfo->primaryTripNumberInfo->companyInfo);
            })
            ->map(function (array $tuple3) use ($tripInfo) {
                /** @var CompanyInfo $companyInfo */
                [$companyInfo, $flightNumber, $confirmationNumber] = $tuple3;

                return [
                    $companyInfo,
                    $flightNumber,
                    (
                        isset($tripInfo->primaryConfirmationNumberInfo->airlineInfo)
                        && !$companyInfo->equals($tripInfo->primaryConfirmationNumberInfo->airlineInfo)
                    ) ?
                        $confirmationNumber :
                        null,
                ];
            })
            ->first();

        if ($secondaryTuple) {
            [$companyInfo, $flightNumber, $confirmationNumber] = $secondaryTuple;

            $tripInfo->secondaryTripNumberInfo = new TripNumberInfo(
                $flightNumber,
                $companyInfo
            );

            if (StringUtils::isNotEmpty($confirmationNumber)) {
                $tripInfo->secondaryConfirmationNumber = $confirmationNumber;
            }
        }
    }

    protected static function createSecondaryInfoGenerator(Tripsegment $tripSegment): \Generator
    {
        return (function () use ($tripSegment) {
            $marketingAirline = $tripSegment->getMarketingAirline() ?? $tripSegment->getMarketingAirlineName();

            yield [
                $marketingAirline,
                $tripSegment->getMarketingFlightNumber(),
                $tripSegment->getMarketingAirlineConfirmationNumber(),
            ];

            yield [
                $marketingAirline,
                $tripSegment->getMarketingFlightNumber(),
                none(),
            ];

            $trip = $tripSegment->getTripid();

            yield [
                $trip->getIssuingAirline() ?? $trip->getIssuingAirlineName(),
                none(),
                $trip->getIssuingAirlineConfirmationNumber(),
            ];
        })();
    }

    protected static function getFirstValidTuple(iterable $tuples3): ?array
    {
        $firstValidTuple3 =
            it($tuples3)
            ->find(function (array $tuple3) {
                return StringUtils::isAllNotEmpty(...$tuple3);
            });

        if ($firstValidTuple3) {
            return self::normalizeTupleValues($firstValidTuple3);
        }

        return null;
    }

    protected static function normalizeTupleValues(array $tuple): array
    {
        $tuple =
            it($tuple)
            ->map(function ($value) {
                return $value instanceof None ? null : $value;
            })
            ->toArray();

        [$airline] = $tuple;

        if (
            \is_object($airline)
            || StringUtils::isNotEmpty($airline)
        ) {
            $tuple[0] = new CompanyInfo($airline);
        }

        return $tuple;
    }
}
