<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Matcher;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Trip as EntityBus;
use AwardWallet\MainBundle\Service\GeoLocationMatcher;
use AwardWallet\Schema\Itineraries\Bus as SchemaBus;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;
use Psr\Log\LoggerInterface;

class BusMatcher extends AbstractItineraryWithSegmentsMatcher
{
    public function __construct(
        LoggerInterface $logger,
        GeoLocationMatcher $locationMatcher,
        BusSegmentMatcher $segmentMatcher
    ) {
        parent::__construct($logger, $locationMatcher, $segmentMatcher);
    }

    /**
     * @param EntityItinerary|EntityBus $entityItinerary
     * @param SchemaItinerary|SchemaBus $schemaItinerary
     */
    public function match(EntityItinerary $entityItinerary, SchemaItinerary $schemaItinerary): float
    {
        if (!$entityItinerary instanceof EntityBus) {
            throw new \InvalidArgumentException(sprintf('Expected %s, got %s', EntityBus::class, get_class($entityItinerary)));
        }

        if (!$schemaItinerary instanceof SchemaBus) {
            throw new \InvalidArgumentException(sprintf('Expected %s, got %s', SchemaBus::class, get_class($schemaItinerary)));
        }

        $result = MatchResult::create()
            ->merge($this->baseMatch($entityItinerary, $schemaItinerary))
            ->merge($this->baseSegmentMatch($entityItinerary, $schemaItinerary->segments));

        $result->writeLogs($this->logger, $entityItinerary, $schemaItinerary);

        return $result->maxConfidence();
    }
}
