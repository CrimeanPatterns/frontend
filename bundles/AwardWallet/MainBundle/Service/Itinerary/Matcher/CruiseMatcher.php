<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Matcher;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Trip as EntityCruise;
use AwardWallet\MainBundle\Service\GeoLocationMatcher;
use AwardWallet\Schema\Itineraries\Cruise as SchemaCruise;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;
use Psr\Log\LoggerInterface;

class CruiseMatcher extends AbstractItineraryWithSegmentsMatcher
{
    public function __construct(
        LoggerInterface $logger,
        GeoLocationMatcher $locationMatcher,
        CruiseSegmentMatcher $segmentMatcher
    ) {
        parent::__construct($logger, $locationMatcher, $segmentMatcher);
    }

    /**
     * @param EntityItinerary|EntityCruise $entityItinerary
     * @param SchemaItinerary|SchemaCruise $schemaItinerary
     */
    public function match(EntityItinerary $entityItinerary, SchemaItinerary $schemaItinerary): float
    {
        if (!$entityItinerary instanceof EntityCruise) {
            throw new \InvalidArgumentException(sprintf('Expected %s, got %s', EntityCruise::class, get_class($entityItinerary)));
        }

        if (!$schemaItinerary instanceof SchemaCruise) {
            throw new \InvalidArgumentException(sprintf('Expected %s, got %s', SchemaCruise::class, get_class($schemaItinerary)));
        }

        $result = MatchResult::create()
            ->merge($this->baseMatch($entityItinerary, $schemaItinerary))
            ->merge($this->baseSegmentMatch($entityItinerary, $schemaItinerary->segments));

        $result->writeLogs($this->logger, $entityItinerary, $schemaItinerary);

        return $result->maxConfidence();
    }
}
