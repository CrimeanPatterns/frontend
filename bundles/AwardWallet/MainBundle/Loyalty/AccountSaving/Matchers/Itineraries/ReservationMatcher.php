<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Itineraries;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Reservation as EntityReservation;
use AwardWallet\Schema\Itineraries\HotelReservation as SchemaReservation;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;

class ReservationMatcher extends AbstractItineraryMatcher
{
    /**
     * @param EntityItinerary|EntityReservation $entityReservation
     * @param SchemaItinerary|SchemaReservation $schemaReservation
     */
    public function match(EntityItinerary $entityReservation, SchemaItinerary $schemaReservation): float
    {
        $confidence = parent::match($entityReservation, $schemaReservation);
        $mainConfirmationNumber = $this->helper->extractPrimaryConfirmationNumber(
            array_merge(
                $schemaReservation->confirmationNumbers ?? [],
                $schemaReservation->travelAgency->confirmationNumbers ?? [],
            )
        );

        if ($mainConfirmationNumber !== null) {
            $mainConfirmationNumber = AbstractItineraryMatcher::filterConfirmationNumber($mainConfirmationNumber);

            if (strcasecmp(AbstractItineraryMatcher::filterConfirmationNumber((string) $entityReservation->getConfirmationNumber()), $mainConfirmationNumber) === 0) {
                $confidence = max($confidence, 0.99);
            }

            if (in_array($mainConfirmationNumber, array_map([AbstractItineraryMatcher::class, "filterConfirmationNumber"], $entityReservation->getTravelAgencyConfirmationNumbers()))) {
                $confidence = max($confidence, 0.99);
            }
        }

        if (
            $mainConfirmationNumber === null && $entityReservation->getConfirmationNumber(true) === null
            && $entityReservation->getCheckindate()->format("Y-m-d") == (new \DateTime($schemaReservation->checkInDate))->format("Y-m-d")
            && $entityReservation->getCheckoutdate()->format("Y-m-d") == (new \DateTime($schemaReservation->checkOutDate))->format("Y-m-d")
        ) {
            $confidence = max($confidence, 0.97);
        }

        if (
            (
                $schemaReservation->cancelled
                || (
                    is_null($mainConfirmationNumber) || is_null($entityReservation->getConfirmationNumber(true))
                )
            )
            && (
                (
                    $entityReservation->getHotelname() !== null
                    && $schemaReservation->hotelName !== null
                    && strcasecmp($entityReservation->getHotelname(), $schemaReservation->hotelName) === 0
                )
                || $this->locationMatcher->match(
                    $schemaReservation->address->text ?? null,
                    $entityReservation->getGeotagid() ?? $entityReservation->getAddress()
                )
            )
            && $schemaReservation->checkInDate !== null && $entityReservation->getCheckindate()->format("Y-m-d") == (new \DateTime($schemaReservation->checkInDate))->format("Y-m-d")
            && $schemaReservation->checkOutDate !== null && $entityReservation->getCheckoutdate()->format("Y-m-d") == (new \DateTime($schemaReservation->checkOutDate))->format("Y-m-d")
        ) {
            $confidence = max($confidence, 0.97);
        }

        return $confidence;
    }

    protected function getSupportedEntityClass(): string
    {
        return EntityReservation::class;
    }

    protected function getSupportedSchemaClass(): string
    {
        return SchemaReservation::class;
    }
}
