<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Itineraries;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Trip as EntityTransfer;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Segments\TransferSegmentMatcher;
use AwardWallet\MainBundle\Service\GeoLocationMatcher;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\Helper;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;
use AwardWallet\Schema\Itineraries\Transfer as SchemaTransfer;
use Psr\Log\LoggerInterface;

class TransferMatcher extends AbstractItineraryMatcher
{
    use MatchesBySegments;

    /**
     * TransferMatcher constructor.
     */
    public function __construct(
        Helper $helper,
        TransferSegmentMatcher $segmentsMatcher,
        GeoLocationMatcher $locationMatcher,
        LoggerInterface $logger
    ) {
        parent::__construct($helper, $locationMatcher, $logger);
        $this->segmentMatcher = $segmentsMatcher;
    }

    /**
     * @param EntityItinerary|EntityTransfer $entityTransfer
     * @param SchemaItinerary|SchemaTransfer $schemaTransfer
     */
    public function match(EntityItinerary $entityTransfer, SchemaItinerary $schemaTransfer): float
    {
        $confidence = parent::match($entityTransfer, $schemaTransfer);
        $mainConfirmationNumber = $this->helper->extractPrimaryConfirmationNumber(
            array_merge(
                $schemaTransfer->confirmationNumbers ?? [],
                $schemaTransfer->travelAgency->confirmationNumbers ?? [],
            )
        );

        if (strcasecmp((string) $entityTransfer->getConfirmationNumber(), (string) $mainConfirmationNumber) === 0) {
            $confidence = max($confidence, 0.99);
        }

        return max($confidence, $this->getMeanConfidence($entityTransfer->getSegments()->toArray(), $schemaTransfer->segments));
    }

    protected function getSupportedEntityClass(): string
    {
        return EntityTransfer::class;
    }

    protected function getSupportedSchemaClass(): string
    {
        return SchemaTransfer::class;
    }
}
