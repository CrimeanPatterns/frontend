<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Itineraries;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Trip as EntityCruise;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Segments\CruiseSegmentMatcher;
use AwardWallet\MainBundle\Service\GeoLocationMatcher;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\Helper;
use AwardWallet\Schema\Itineraries\Cruise as SchemaCruise;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;
use Psr\Log\LoggerInterface;

class CruiseMatcher extends AbstractItineraryMatcher
{
    use MatchesBySegments;

    /**
     * CruiseMatcher constructor.
     */
    public function __construct(
        Helper $helper,
        CruiseSegmentMatcher $segmentsMatcher,
        GeoLocationMatcher $locationMatcher,
        LoggerInterface $logger
    ) {
        parent::__construct($helper, $locationMatcher, $logger);
        $this->segmentMatcher = $segmentsMatcher;
    }

    /**
     * @param EntityItinerary|EntityCruise $entityCruise
     * @param SchemaItinerary|SchemaCruise $schemaCruise
     */
    public function match(EntityItinerary $entityCruise, SchemaItinerary $schemaCruise): float
    {
        $confidence = parent::match($entityCruise, $schemaCruise);
        $mainConfirmationNumber = $this->helper->extractPrimaryConfirmationNumber(
            array_merge(
                $schemaCruise->confirmationNumbers ?? [],
                $schemaCruise->travelAgency->confirmationNumbers ?? [],
            )
        );

        if (strcasecmp((string) $entityCruise->getConfirmationNumber(), (string) $mainConfirmationNumber) === 0) {
            $confidence = max($confidence, 0.99);
        }

        return max($confidence, $this->getMeanConfidence($entityCruise->getSegments()->toArray(), $schemaCruise->segments));
    }

    protected function getSupportedEntityClass(): string
    {
        return EntityCruise::class;
    }

    protected function getSupportedSchemaClass(): string
    {
        return SchemaCruise::class;
    }
}
