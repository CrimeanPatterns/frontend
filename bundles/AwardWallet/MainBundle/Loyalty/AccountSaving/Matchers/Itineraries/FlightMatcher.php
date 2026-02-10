<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Itineraries;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Trip as EntityFlight;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\Schema\Itineraries\ConfNo;
use AwardWallet\Schema\Itineraries\Flight as SchemaFlight;
use AwardWallet\Schema\Itineraries\FlightSegment;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class FlightMatcher extends AbstractItineraryMatcher
{
    /**
     * Match Flights by issuing airline confirmation number or by the best segment match.
     *
     * @param EntityItinerary|EntityFlight $entityFlight
     * @param SchemaItinerary|SchemaFlight $schemaFlight
     */
    public function match(EntityItinerary $entityFlight, SchemaItinerary $schemaFlight): float
    {
        $confidence = parent::match($entityFlight, $schemaFlight);

        if (
            null !== $entityFlight->getIssuingAirlineConfirmationNumber()
            && null !== $schemaFlight->issuingCarrier
            && strcasecmp((string) $entityFlight->getIssuingAirlineConfirmationNumber(), (string) $schemaFlight->issuingCarrier->confirmationNumber) === 0
        ) {
            $confidence = max($confidence, 0.99);
        }

        $schemaTravelAgencyNumbers = [];

        if (!empty($schemaFlight->travelAgency->confirmationNumbers)) {
            $schemaTravelAgencyNumbers = array_map(function (ConfNo $number) {
                return AbstractItineraryMatcher::filterConfirmationNumber($number->number);
            }, $schemaFlight->travelAgency->confirmationNumbers);
        }

        $entityTravelAgencyNumbers = [];

        if (!empty($entityFlight->getTravelAgencyConfirmationNumbers())) {
            $entityTravelAgencyNumbers = array_map(function (string $number) {
                return AbstractItineraryMatcher::filterConfirmationNumber($number);
            }, $entityFlight->getTravelAgencyConfirmationNumbers());
        }

        if (
            !empty($entityTravelAgencyNumbers)
            && !empty($schemaTravelAgencyNumbers)
        ) {
            $sameTravelAgencyConfirmationNumbers = !empty(
                array_intersect($entityTravelAgencyNumbers, $schemaTravelAgencyNumbers)
            );
            $confidence = max($confidence, 0.99 * $sameTravelAgencyConfirmationNumbers);
        }

        /** @var FlightSegment $firstSchemaSegment */
        $schemaMarketingNumbers = it($schemaFlight->segments)
            ->filter(function (FlightSegment $segment) { return $segment->marketingCarrier !== null && !empty($segment->marketingCarrier->confirmationNumber); })
            ->map(function (FlightSegment $segment) { return AbstractItineraryMatcher::filterConfirmationNumber($segment->marketingCarrier->confirmationNumber); })
            ->toArray();

        $entityMarketingNumbers = it($entityFlight->getSegments())
            ->map(function (Tripsegment $entitySegment) {
                return AbstractItineraryMatcher::filterConfirmationNumber(
                    (string) $entitySegment->getMarketingAirlineConfirmationNumber());
            })
            ->filterNotEmpty()
            ->toArray();

        if (count(array_intersect($schemaMarketingNumbers, $entityMarketingNumbers)) > 0) {
            $confidence = max($confidence, 0.99);
        }

        // match by any number, if there are only one number
        $schemaNumbers = [];

        if ($schemaFlight->issuingCarrier !== null) {
            $schemaNumbers[] = (string) $schemaFlight->issuingCarrier->confirmationNumber;
        }
        $schemaNumbers = array_merge($schemaNumbers, $schemaTravelAgencyNumbers);

        if (!empty($schemaMarketingNumbers)) {
            $schemaNumbers = array_merge($schemaNumbers, $schemaMarketingNumbers);
        }
        $schemaNumbers = array_unique(array_map([AbstractItineraryMatcher::class, 'filterConfirmationNumber'], $schemaNumbers));
        sort($schemaNumbers);

        $entityNumbers = [];

        if ($entityFlight->getIssuingAirlineConfirmationNumber() !== null) {
            $entityNumbers[] = $entityFlight->getIssuingAirlineConfirmationNumber();
        }
        $entityNumbers = array_merge($entityNumbers, $entityTravelAgencyNumbers);

        if (!empty($entityMarketingNumbers)) {
            $entityNumbers = array_merge($entityNumbers, $entityMarketingNumbers);
        }
        $entityNumbers = array_unique(array_map([AbstractItineraryMatcher::class, 'filterConfirmationNumber'], $entityNumbers));
        sort($entityNumbers);

        if (count(array_intersect($schemaNumbers, $entityNumbers)) > 0) {
            $confidence = max($confidence, 0.99);
        }

        // match by depDate + arrDate + depCode + arrCode + passengers
        if ($confidence < 0.9) {
            if (
                (empty($schemaFlight->travelers) && !empty($entityFlight->getTravelerNames()))
                || (empty($entityFlight->getTravelerNames()) && !empty($schemaFlight->travelers))
                || (
                    !empty($schemaFlight->travelers) && !empty($entityFlight->getTravelerNames())
                    && TravelerNamesMatcher::same($schemaFlight->travelers ?? [], $entityFlight->getTravelerNames())
                )
            ) {
                /** @var FlightSegment $schemaSegment */
                foreach ($schemaFlight->segments as $schemaSegment) {
                    foreach ($entityFlight->getSegments() as $entitySegment) {
                        if (
                            $schemaSegment->departure !== null && $schemaSegment->departure->localDateTime !== null
                            && $schemaSegment->arrival !== null && $schemaSegment->arrival->localDateTime !== null
                            && strtotime($schemaSegment->departure->localDateTime) === $entitySegment->getScheduledDepDate()->getTimestamp()
                            && strtotime($schemaSegment->arrival->localDateTime) === $entitySegment->getScheduledArrDate()->getTimestamp()
                            && $schemaSegment->departure->airportCode === $entitySegment->getDepcode()
                            && $schemaSegment->arrival->airportCode === $entitySegment->getArrcode()
                            && !(
                                !empty($schemaConfNo = $schemaSegment->marketingCarrier->confirmationNumber ?? null)
                                && !empty($schemaAirlineCode = $schemaSegment->marketingCarrier->airline->iata ?? null)
                                && !empty($entityConfNo = $entitySegment->getMarketingAirlineConfirmationNumber())
                                && !is_null($entitySegment->getMarketingAirline())
                                && !empty($entityAirlineCode = $entitySegment->getMarketingAirline()->getCode())
                                && $schemaAirlineCode === $entityAirlineCode
                                && $schemaConfNo !== $entityConfNo
                            )
                        ) {
                            $confidence = max($confidence, 0.99);

                            break 2;
                        }
                    }
                }
            }
        }

        return $confidence;
    }

    protected function getSupportedEntityClass(): string
    {
        return EntityFlight::class;
    }

    protected function getSupportedSchemaClass(): string
    {
        return SchemaFlight::class;
    }
}
