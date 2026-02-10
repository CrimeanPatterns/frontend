<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Matcher;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Parking as EntityParking;
use AwardWallet\MainBundle\Service\Itinerary\Matcher\Helper\ConfirmationNumberHelper;
use AwardWallet\MainBundle\Service\Itinerary\Matcher\Helper\CurrencyHelper;
use AwardWallet\MainBundle\Service\Itinerary\Matcher\Helper\DateHelper;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;
use AwardWallet\Schema\Itineraries\Parking as SchemaParking;

class ParkingMatcher extends AbstractItineraryMatcher
{
    /**
     * @param EntityItinerary|EntityParking $entityItinerary
     * @param SchemaItinerary|SchemaParking $schemaItinerary
     */
    public function match(EntityItinerary $entityItinerary, SchemaItinerary $schemaItinerary): float
    {
        if (!$entityItinerary instanceof EntityParking) {
            throw new \InvalidArgumentException(sprintf('Expected %s, got %s', EntityParking::class, get_class($entityItinerary)));
        }

        if (!$schemaItinerary instanceof SchemaParking) {
            throw new \InvalidArgumentException(sprintf('Expected %s, got %s', SchemaParking::class, get_class($schemaItinerary)));
        }

        $sameProviderButDifferentConfirmationNumber =
            ConfirmationNumberHelper::isSameProviderButDifferentConfirmationNumber($entityItinerary, $schemaItinerary);
        $sameStartDate = DateHelper::isSameEntityDateWithSchemaDate($entityItinerary->getStartDatetime(), $schemaItinerary->startDateTime);
        $sameEndDate = DateHelper::isSameEntityDateWithSchemaDate($entityItinerary->getEndDatetime(), $schemaItinerary->endDateTime);
        $sameLocation = $this->isSameLocation(
            $entityItinerary->getGeoTagID() ?? $entityItinerary->getLocation(),
            $schemaItinerary->address->text ?? null,
            0.5
        );
        $sameOrEmptyTotal = CurrencyHelper::isSameOrEmptyTotal($entityItinerary, $schemaItinerary);

        $result = MatchResult::create()
            ->merge($this->baseMatch($entityItinerary, $schemaItinerary))
            ->addResult(
                'parking.sameStartDate+sameEndDate+sameLocation+sameOrEmptyTotal',
                $sameStartDate && $sameEndDate && $sameLocation && !$sameProviderButDifferentConfirmationNumber && $sameOrEmptyTotal,
                0.97
            );

        $result->writeLogs($this->logger, $entityItinerary, $schemaItinerary);

        return $result->maxConfidence();
    }
}
