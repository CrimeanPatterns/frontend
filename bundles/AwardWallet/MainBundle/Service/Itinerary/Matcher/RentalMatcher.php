<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Matcher;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Rental as EntityRental;
use AwardWallet\MainBundle\Service\Itinerary\Matcher\Helper\ConfirmationNumberHelper;
use AwardWallet\MainBundle\Service\Itinerary\Matcher\Helper\CurrencyHelper;
use AwardWallet\MainBundle\Service\Itinerary\Matcher\Helper\DateHelper;
use AwardWallet\MainBundle\Service\Itinerary\Matcher\Helper\TravelerNamesHelper;
use AwardWallet\Schema\Itineraries\CarRental as SchemaRental;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;

class RentalMatcher extends AbstractItineraryMatcher
{
    /**
     * @param EntityItinerary|EntityRental $entityItinerary
     * @param SchemaItinerary|SchemaRental $schemaItinerary
     */
    public function match(EntityItinerary $entityItinerary, SchemaItinerary $schemaItinerary): float
    {
        if (!$entityItinerary instanceof EntityRental) {
            throw new \InvalidArgumentException(sprintf('Expected %s, got %s', EntityRental::class, get_class($entityItinerary)));
        }

        if (!$schemaItinerary instanceof SchemaRental) {
            throw new \InvalidArgumentException(sprintf('Expected %s, got %s', SchemaRental::class, get_class($schemaItinerary)));
        }

        $sameProviderButDifferentConfirmationNumber =
            ConfirmationNumberHelper::isSameProviderButDifferentConfirmationNumber($entityItinerary, $schemaItinerary);
        $sameStartDate = DateHelper::isSameEntityDateWithSchemaDate($entityItinerary->getPickupdatetime(), $schemaItinerary->pickup->localDateTime ?? null);
        $sameEndDate = DateHelper::isSameEntityDateWithSchemaDate($entityItinerary->getDropoffdatetime(), $schemaItinerary->dropoff->localDateTime ?? null);
        $samePickupLocation = $this->isSameLocation(
            $entityItinerary->getPickupgeotagid() ?? $entityItinerary->getPickuplocation(),
            $schemaItinerary->pickup->address->text ?? null,
            0.5
        );
        $sameDropoffLocation = $this->isSameLocation(
            $entityItinerary->getDropoffgeotagid() ?? $entityItinerary->getDropofflocation(),
            $schemaItinerary->dropoff->address->text ?? null,
            0.5
        );
        $sameDriver = $this->isSameDriver($entityItinerary, $schemaItinerary);
        $emptySchemaDriver = empty($schemaItinerary->driver ?? null);
        $emptyEntityDriver = empty($entityItinerary->getTravelerNames());
        $sameOrEmptyTotal = CurrencyHelper::isSameOrEmptyTotal($entityItinerary, $schemaItinerary);

        $result = MatchResult::create()
            ->merge($this->baseMatch($entityItinerary, $schemaItinerary))
            ->addResult(
                'rental.sameStartDate+sameEndDate+samePickupLocation+sameDropoffLocation',
                !$sameProviderButDifferentConfirmationNumber
                && $sameOrEmptyTotal
                && $sameStartDate
                && $sameEndDate
                && $samePickupLocation
                && $sameDropoffLocation,
                0.97
            )
            ->addResult(
                'rental.sameStartDate+sameEndDate+sameDriver',
                !$sameProviderButDifferentConfirmationNumber
                && $sameOrEmptyTotal
                && $sameStartDate
                && $sameEndDate
                && ($sameDriver || $emptySchemaDriver || $emptyEntityDriver),
                0.6
            );

        $result->writeLogs($this->logger, $entityItinerary, $schemaItinerary);

        return $result->maxConfidence();
    }

    /**
     * @param EntityItinerary|EntityRental $entityItinerary
     * @param SchemaItinerary|SchemaRental $schemaItinerary
     */
    private function isSameDriver(EntityItinerary $entityItinerary, SchemaItinerary $schemaItinerary): bool
    {
        return !empty($schemaItinerary->driver ?? null)
            && !empty($entityItinerary->getTravelerNames())
            && TravelerNamesHelper::isSame([$schemaItinerary->driver], $entityItinerary->getTravelerNames());
    }
}
