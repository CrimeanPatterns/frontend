<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Itineraries;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Parking as EntityParking;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;
use AwardWallet\Schema\Itineraries\Parking as SchemaParking;

class ParkingMatcher extends AbstractItineraryMatcher
{
    /**
     * @param EntityItinerary|EntityParking $entityParking
     * @param SchemaItinerary|SchemaParking $schemaParking
     */
    public function match(EntityItinerary $entityParking, SchemaItinerary $schemaParking): float
    {
        $confidence = parent::match($entityParking, $schemaParking);
        $mainConfirmationNumber = $this->helper->extractPrimaryConfirmationNumber(
            array_merge(
                $schemaParking->confirmationNumbers ?? [],
                $schemaParking->travelAgency->confirmationNumbers ?? [],
            )
        );

        if ($mainConfirmationNumber !== null) {
            $mainConfirmationNumber = AbstractItineraryMatcher::filterConfirmationNumber($mainConfirmationNumber);

            if (strcasecmp(AbstractItineraryMatcher::filterConfirmationNumber((string) $entityParking->getConfirmationNumber()), $mainConfirmationNumber) === 0) {
                $confidence = max($confidence, 0.99);
            }

            if (in_array($mainConfirmationNumber, array_map([AbstractItineraryMatcher::class, "filterConfirmationNumber"], $entityParking->getTravelAgencyConfirmationNumbers()))) {
                $confidence = max($confidence, 0.99);
            }
        }

        if (
            $mainConfirmationNumber === null
            && $entityParking->getStartDatetime() == new \DateTime($schemaParking->startDateTime)
            && $entityParking->getEndDatetime() == new \DateTime($schemaParking->endDateTime)
            && null !== $schemaParking->address
            && $this->locationMatcher->match(
                $schemaParking->address->text ?? null,
                $entityParking->getGeoTagID() ?? $entityParking->getLocation()
            )
        ) {
            $confidence = max($confidence, 0.97);
        }

        return $confidence;
    }

    protected function getSupportedEntityClass(): string
    {
        return EntityParking::class;
    }

    protected function getSupportedSchemaClass(): string
    {
        return SchemaParking::class;
    }
}
