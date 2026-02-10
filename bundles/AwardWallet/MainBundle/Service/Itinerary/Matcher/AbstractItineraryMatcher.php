<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Matcher;

use AwardWallet\Common\Entity\Geotag;
use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Service\GeoLocationMatcher;
use AwardWallet\MainBundle\Service\Itinerary\Matcher\Helper\ConfirmationNumberHelper;
use AwardWallet\MainBundle\Service\Itinerary\Matcher\Helper\CurrencyHelper;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;
use Psr\Log\LoggerInterface;

abstract class AbstractItineraryMatcher implements ItineraryMatcherInterface
{
    protected LoggerInterface $logger;

    protected GeoLocationMatcher $locationMatcher;

    public function __construct(LoggerInterface $logger, GeoLocationMatcher $locationMatcher)
    {
        $this->logger = $logger;
        $this->locationMatcher = $locationMatcher;
    }

    protected function baseMatch(EntityItinerary $entityItinerary, SchemaItinerary $schemaItinerary): MatchResult
    {
        $sameProviderButDifferentConfirmationNumber =
            ConfirmationNumberHelper::isSameProviderButDifferentConfirmationNumber($entityItinerary, $schemaItinerary);
        $sameOrEmptyTotal = CurrencyHelper::isSameOrEmptyTotal($entityItinerary, $schemaItinerary);

        return MatchResult::create()
            ->addResult(
                'baseMatch.samePrimaryConfirmationNumber',
                !$sameProviderButDifferentConfirmationNumber
                && $sameOrEmptyTotal
                && ConfirmationNumberHelper::isSamePrimaryConfirmationNumber($entityItinerary, $schemaItinerary),
                0.99
            )
            ->addResult(
                'baseMatch.sameTravelAgencyConfirmationNumbers',
                !$sameProviderButDifferentConfirmationNumber
                && $sameOrEmptyTotal
                && ConfirmationNumberHelper::isSameTravelAgencyConfirmationNumber($entityItinerary, $schemaItinerary),
                0.99
            )
            ->addResult(
                'baseMatch.sameAllConfirmationNumbers',
                !$sameProviderButDifferentConfirmationNumber
                && $sameOrEmptyTotal
                && ConfirmationNumberHelper::isSameAllConfirmationNumbers($entityItinerary, $schemaItinerary),
                0.99
            );
    }

    /**
     * @param string|Geotag $entityLocation
     */
    protected function isSameLocation($entityLocation, ?string $schemaLocation, float $maxDistance = 2): bool
    {
        return $this->locationMatcher->match($entityLocation, $schemaLocation, $maxDistance, false);
    }
}
