<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Matcher;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Trip as EntityFlight;
use AwardWallet\MainBundle\Service\GeoLocationMatcher;
use AwardWallet\MainBundle\Service\Itinerary\Matcher\Helper\ConfirmationNumberHelper;
use AwardWallet\MainBundle\Service\Itinerary\Matcher\Helper\CurrencyHelper;
use AwardWallet\MainBundle\Service\Itinerary\Matcher\Helper\DateHelper;
use AwardWallet\MainBundle\Service\Itinerary\Matcher\Helper\LocationHelper;
use AwardWallet\MainBundle\Service\Itinerary\Matcher\Helper\TravelerNamesHelper;
use AwardWallet\Schema\Itineraries\Flight as SchemaFlight;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;
use Psr\Log\LoggerInterface;

class FlightMatcher extends AbstractItineraryWithSegmentsMatcher
{
    public function __construct(
        LoggerInterface $logger,
        GeoLocationMatcher $locationMatcher,
        FlightSegmentMatcher $segmentMatcher
    ) {
        parent::__construct($logger, $locationMatcher, $segmentMatcher);
    }

    /**
     * @param EntityItinerary|EntityFlight $entityItinerary
     * @param SchemaItinerary|SchemaFlight $schemaItinerary
     */
    public function match(EntityItinerary $entityItinerary, SchemaItinerary $schemaItinerary): float
    {
        if (!$entityItinerary instanceof EntityFlight) {
            throw new \InvalidArgumentException(sprintf('Expected %s, got %s', EntityFlight::class, get_class($entityItinerary)));
        }

        if (!$schemaItinerary instanceof SchemaFlight) {
            throw new \InvalidArgumentException(sprintf('Expected %s, got %s', SchemaFlight::class, get_class($schemaItinerary)));
        }

        $sameProviderButDifferentConfirmationNumber =
            ConfirmationNumberHelper::isSameProviderButDifferentConfirmationNumber($entityItinerary, $schemaItinerary);
        $sameIssuingConfirmationNumber = ConfirmationNumberHelper::isSameIssuingConfirmationNumber($entityItinerary, $schemaItinerary);
        $sameMarketingConfirmationNumber = ConfirmationNumberHelper::isSameAnyMarketingConfirmationNumber($entityItinerary, $schemaItinerary);
        $sameAnyFlightConfirmationNumber = ConfirmationNumberHelper::isSameAnyFlightConfirmationNumber($entityItinerary, $schemaItinerary);
        $sameTravelers = $this->isSameTravelers($entityItinerary, $schemaItinerary);
        $travelersEmptySchemaAndNotEmptyEntity = empty($schemaItinerary->travelers) && !empty($entityItinerary->getTravelerNames());
        $travelersNotEmptySchemaAndEmptyEntity = !empty($schemaItinerary->travelers) && empty($entityItinerary->getTravelerNames());
        $hasSameSegment = $this->hasSameSegment($entityItinerary, $schemaItinerary);
        // Very questionable condition
        $questionableCondition = ($travelersEmptySchemaAndNotEmptyEntity || $travelersNotEmptySchemaAndEmptyEntity || $sameTravelers)
            && $hasSameSegment;
        $sameOrEmptyTotal = CurrencyHelper::isSameOrEmptyTotal($entityItinerary, $schemaItinerary);

        $result = MatchResult::create()
            ->merge($this->baseMatch($entityItinerary, $schemaItinerary))
            ->merge($this->baseSegmentMatch($entityItinerary, $schemaItinerary->segments))
            ->addResult(
                'flight.sameIssuingConfirmationNumber',
                $sameIssuingConfirmationNumber && !$sameProviderButDifferentConfirmationNumber && $sameOrEmptyTotal,
                0.99
            )
            ->addResult(
                'flight.sameMarketingConfirmationNumber',
                $sameMarketingConfirmationNumber && !$sameProviderButDifferentConfirmationNumber && $sameOrEmptyTotal,
                0.99
            )
            ->addResult(
                'flight.sameAnyFlightConfirmationNumber',
                $sameAnyFlightConfirmationNumber && !$sameProviderButDifferentConfirmationNumber && $sameOrEmptyTotal,
                0.98
            )
            ->addResult(
                'flight.sameTravelers+anySegment',
                $questionableCondition && !$sameProviderButDifferentConfirmationNumber && $sameOrEmptyTotal,
                0.6
            );

        $result->writeLogs($this->logger, $entityItinerary, $schemaItinerary);

        return $result->maxConfidence();
    }

    private function isSameTravelers(EntityItinerary $entityItinerary, SchemaFlight $schemaFlight): bool
    {
        return !empty($schemaFlight->travelers)
            && !empty($entityItinerary->getTravelerNames())
            && TravelerNamesHelper::isSame($schemaFlight->travelers, $entityItinerary->getTravelerNames());
    }

    /**
     * refs https://redmine.awardwallet.com/issues/22067#note-5.
     *
     * @param EntityItinerary|EntityFlight $entityItinerary
     */
    private function hasSameSegment(EntityItinerary $entityItinerary, SchemaFlight $schemaFlight): bool
    {
        foreach ($schemaFlight->segments as $schemaSegment) {
            foreach ($entityItinerary->getSegments() as $entitySegment) {
                $sameDepatureCode = LocationHelper::isSameLocationCode(
                    $entitySegment->getDepcode(),
                    $schemaSegment->departure->airportCode ?? null
                );
                $sameArrivalCode = LocationHelper::isSameLocationCode(
                    $entitySegment->getArrcode(),
                    $schemaSegment->arrival->airportCode ?? null
                );
                $sameDepartureDate = DateHelper::isSameEntityDateWithSchemaDate(
                    $entitySegment->getScheduledDepDate(),
                    $schemaSegment->departure->localDateTime ?? null
                );
                $sameArrivalDate = DateHelper::isSameEntityDateWithSchemaDate(
                    $entitySegment->getScheduledArrDate(),
                    $schemaSegment->arrival->localDateTime ?? null
                );
                // Has same airline code and different confirmation number
                $absoluteNotSameMarketingConfirmationNumber =
                    !empty($schemaConfNo = $schemaSegment->marketingCarrier->confirmationNumber ?? null)
                    && !empty($schemaAirlineCode = $schemaSegment->marketingCarrier->airline->iata ?? null)
                    && !empty($entityConfNo = $entitySegment->getMarketingAirlineConfirmationNumber())
                    && !empty($entitySegment->getMarketingAirline())
                    && !empty($entityAirlineCode = $entitySegment->getMarketingAirline()->getCode())
                    && strcasecmp($schemaAirlineCode, $entityAirlineCode) === 0
                    && !ConfirmationNumberHelper::isSame($entityConfNo, $schemaConfNo);

                if (
                    $sameDepatureCode
                    && $sameArrivalCode
                    && $sameDepartureDate
                    && $sameArrivalDate
                    && !$absoluteNotSameMarketingConfirmationNumber
                ) {
                    return true;
                }
            }
        }

        return false;
    }
}
