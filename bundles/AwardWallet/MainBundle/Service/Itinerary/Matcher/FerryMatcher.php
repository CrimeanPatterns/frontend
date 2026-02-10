<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Matcher;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Trip as EntityFerry;
use AwardWallet\MainBundle\Service\GeoLocationMatcher;
use AwardWallet\Schema\Itineraries\Ferry as SchemaFerry;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;
use Psr\Log\LoggerInterface;

class FerryMatcher extends AbstractItineraryWithSegmentsMatcher
{
    public function __construct(
        LoggerInterface $logger,
        GeoLocationMatcher $locationMatcher,
        FerrySegmentMatcher $segmentMatcher
    ) {
        parent::__construct($logger, $locationMatcher, $segmentMatcher);
    }

    /**
     * @param EntityItinerary|EntityFerry $entityItinerary
     * @param SchemaItinerary|SchemaFerry $schemaItinerary
     */
    public function match(EntityItinerary $entityItinerary, SchemaItinerary $schemaItinerary): float
    {
        if (!$entityItinerary instanceof EntityFerry) {
            throw new \InvalidArgumentException(sprintf('Expected %s, got %s', EntityFerry::class, get_class($entityItinerary)));
        }

        if (!$schemaItinerary instanceof SchemaFerry) {
            throw new \InvalidArgumentException(sprintf('Expected %s, got %s', SchemaFerry::class, get_class($schemaItinerary)));
        }

        $result = MatchResult::create()
            ->merge($this->baseMatch($entityItinerary, $schemaItinerary))
            ->merge($this->baseSegmentMatch($entityItinerary, $schemaItinerary->segments));

        $result->writeLogs($this->logger, $entityItinerary, $schemaItinerary);

        return $result->maxConfidence();
    }
}
