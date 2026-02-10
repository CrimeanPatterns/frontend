<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Itineraries;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Trip as EntityFerry;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Segments\FerrySegmentMatcher;
use AwardWallet\MainBundle\Service\GeoLocationMatcher;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\Helper;
use AwardWallet\Schema\Itineraries\Ferry as SchemaFerry;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;
use Psr\Log\LoggerInterface;

class FerryMatcher extends AbstractItineraryMatcher
{
    use MatchesBySegments;

    /**
     * FerryMatcher constructor.
     */
    public function __construct(
        Helper $helper,
        FerrySegmentMatcher $segmentsMatcher,
        GeoLocationMatcher $locationMatcher,
        LoggerInterface $logger
    ) {
        parent::__construct($helper, $locationMatcher, $logger);
        $this->segmentMatcher = $segmentsMatcher;
    }

    /**
     * @param EntityItinerary|EntityFerry $entityFerry
     * @param SchemaItinerary|SchemaFerry $schemaFerry
     */
    public function match(EntityItinerary $entityFerry, SchemaItinerary $schemaFerry): float
    {
        $confidence = parent::match($entityFerry, $schemaFerry);
        $mainConfirmationNumber = $this->helper->extractPrimaryConfirmationNumber(
            array_merge(
                $schemaFerry->confirmationNumbers ?? [],
                $schemaFerry->travelAgency->confirmationNumbers ?? [],
            )
        );

        if (strcasecmp((string) $entityFerry->getConfirmationNumber(), (string) $mainConfirmationNumber) === 0) {
            $confidence = max($confidence, 0.99);
        }

        return max($confidence, $this->getMeanConfidence($entityFerry->getSegments()->toArray(), $schemaFerry->segments));
    }

    protected function getSupportedEntityClass(): string
    {
        return EntityFerry::class;
    }

    protected function getSupportedSchemaClass(): string
    {
        return SchemaFerry::class;
    }
}
