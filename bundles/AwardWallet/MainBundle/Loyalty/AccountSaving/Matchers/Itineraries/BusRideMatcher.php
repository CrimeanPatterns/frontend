<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Itineraries;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Trip as EntityBusRide;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Segments\BusRideSegmentMatcher;
use AwardWallet\MainBundle\Service\GeoLocationMatcher;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\Helper;
use AwardWallet\Schema\Itineraries\Bus as SchemaBusRide;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;
use Psr\Log\LoggerInterface;

class BusRideMatcher extends AbstractItineraryMatcher
{
    use MatchesBySegments;

    /**
     * TrainRideMatcher constructor.
     */
    public function __construct(
        Helper $helper,
        BusRideSegmentMatcher $segmentsMatcher,
        GeoLocationMatcher $locationMatcher,
        LoggerInterface $logger
    ) {
        parent::__construct($helper, $locationMatcher, $logger);
        $this->segmentMatcher = $segmentsMatcher;
    }

    /**
     * @param EntityItinerary|EntityBusRide $entityBusRide
     * @param SchemaItinerary|SchemaBusRide $schemaBusRide
     */
    public function match(EntityItinerary $entityBusRide, SchemaItinerary $schemaBusRide): float
    {
        $confidence = parent::match($entityBusRide, $schemaBusRide);
        $mainConfirmationNumber = $this->helper->extractPrimaryConfirmationNumber(
            array_merge(
                $schemaBusRide->confirmationNumbers ?? [],
                $schemaBusRide->travelAgency->confirmationNumbers ?? [],
            )
        );

        if (strcasecmp((string) $entityBusRide->getConfirmationNumber(), (string) $mainConfirmationNumber) === 0) {
            $confidence = max($confidence, 0.99);
        }

        return max($confidence, $this->getMeanConfidence($entityBusRide->getSegments()->toArray(), $schemaBusRide->segments));
    }

    protected function getSupportedEntityClass(): string
    {
        return EntityBusRide::class;
    }

    protected function getSupportedSchemaClass(): string
    {
        return SchemaBusRide::class;
    }
}
