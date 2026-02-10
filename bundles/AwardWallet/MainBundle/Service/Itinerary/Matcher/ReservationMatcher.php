<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Matcher;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Reservation as EntityReservation;
use AwardWallet\MainBundle\Service\Itinerary\Matcher\Helper\ConfirmationNumberHelper;
use AwardWallet\MainBundle\Service\Itinerary\Matcher\Helper\CurrencyHelper;
use AwardWallet\Schema\Itineraries\HotelReservation as SchemaReservation;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;

class ReservationMatcher extends AbstractItineraryMatcher
{
    /**
     * @param EntityItinerary|EntityReservation $entityItinerary
     * @param SchemaItinerary|SchemaReservation $schemaItinerary
     */
    public function match(EntityItinerary $entityItinerary, SchemaItinerary $schemaItinerary): float
    {
        if (!$entityItinerary instanceof EntityReservation) {
            throw new \InvalidArgumentException(sprintf('Expected %s, got %s', EntityReservation::class, get_class($entityItinerary)));
        }

        if (!$schemaItinerary instanceof SchemaReservation) {
            throw new \InvalidArgumentException(sprintf('Expected %s, got %s', SchemaReservation::class, get_class($schemaItinerary)));
        }

        $sameProviderButDifferentConfirmationNumber =
            ConfirmationNumberHelper::isSameProviderButDifferentConfirmationNumber($entityItinerary, $schemaItinerary);
        $sameDates = $this->isSameDates($entityItinerary, $schemaItinerary);
        $sameLocation = $this->isSameLocation(
            $entityItinerary->getGeotagid() ?? $entityItinerary->getAddress(),
            $schemaItinerary->address->text ?? null,
            0.5
        );
        $sameHotelName = $this->isSameHotelName($entityItinerary, $schemaItinerary);
        $primarySchemaConfirmationNumber = ConfirmationNumberHelper::filterConfirmationNumber(
            ConfirmationNumberHelper::extractSchemaPrimaryConfirmationNumber($schemaItinerary)
        );
        $primaryEntityConfirmationNumber = ConfirmationNumberHelper::filterConfirmationNumber(
            $entityItinerary->getConfirmationNumber(true)
        );
        $sameOrEmptyTotal = CurrencyHelper::isSameOrEmptyTotal($entityItinerary, $schemaItinerary);

        $result = MatchResult::create()
            ->merge($this->baseMatch($entityItinerary, $schemaItinerary))
            ->addResult(
                'reservation.emptyNumbers+sameDates',
                !$sameProviderButDifferentConfirmationNumber
                && $sameOrEmptyTotal
                && empty($primarySchemaConfirmationNumber)
                && empty($primaryEntityConfirmationNumber)
                && $sameDates,
                0.6
            )
            ->addResult(
                'reservation.sameHotelName+sameDates',
                !$sameProviderButDifferentConfirmationNumber
                && $sameOrEmptyTotal
                && ($schemaItinerary->cancelled || empty($primarySchemaConfirmationNumber) || empty($primaryEntityConfirmationNumber))
                && ($sameHotelName || $sameLocation)
                && $sameDates,
                0.6
            );

        $result->writeLogs($this->logger, $entityItinerary, $schemaItinerary);

        return $result->maxConfidence();
    }

    private function isSameDates(EntityReservation $entityReservation, SchemaReservation $schemaReservation): bool
    {
        $sameCheckinDate =
            !empty($entityReservation->getCheckindate())
            && !empty($schemaReservation->checkInDate)
            && $entityReservation->getCheckindate()->format('Y-m-d') === date_create($schemaReservation->checkInDate)->format('Y-m-d');
        $sameCheckoutDate =
            !empty($entityReservation->getCheckoutdate())
            && !empty($schemaReservation->checkOutDate)
            && $entityReservation->getCheckoutdate()->format('Y-m-d') === date_create($schemaReservation->checkOutDate)->format('Y-m-d');

        return $sameCheckinDate && $sameCheckoutDate;
    }

    private function isSameHotelName(EntityReservation $entityReservation, SchemaReservation $schemaReservation): bool
    {
        return !empty($entityReservation->getHotelname())
            && !empty($schemaReservation->hotelName)
            && strcasecmp($entityReservation->getHotelname(), $schemaReservation->hotelName) === 0;
    }
}
