<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Matcher;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Trip as EntityTransfer;
use AwardWallet\MainBundle\Service\GeoLocationMatcher;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;
use AwardWallet\Schema\Itineraries\Transfer as SchemaTransfer;
use Psr\Log\LoggerInterface;

class TransferMatcher extends AbstractItineraryWithSegmentsMatcher
{
    public function __construct(
        LoggerInterface $logger,
        GeoLocationMatcher $locationMatcher,
        TransferSegmentMatcher $segmentMatcher
    ) {
        parent::__construct($logger, $locationMatcher, $segmentMatcher);
    }

    /**
     * @param EntityItinerary|EntityTransfer $entityItinerary
     * @param SchemaItinerary|SchemaTransfer $schemaItinerary
     */
    public function match(EntityItinerary $entityItinerary, SchemaItinerary $schemaItinerary): float
    {
        if (!$entityItinerary instanceof EntityTransfer) {
            throw new \InvalidArgumentException(sprintf('Expected %s, got %s', EntityTransfer::class, get_class($entityItinerary)));
        }

        if (!$schemaItinerary instanceof SchemaTransfer) {
            throw new \InvalidArgumentException(sprintf('Expected %s, got %s', SchemaTransfer::class, get_class($schemaItinerary)));
        }

        $result = MatchResult::create()
            ->merge($this->baseMatch($entityItinerary, $schemaItinerary))
            ->merge($this->baseSegmentMatch($entityItinerary, $schemaItinerary->segments));

        $result->writeLogs($this->logger, $entityItinerary, $schemaItinerary);

        return $result->maxConfidence();
    }
}
