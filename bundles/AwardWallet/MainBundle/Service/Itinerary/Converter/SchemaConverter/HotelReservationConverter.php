<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Reservation as EntityReservation;
use AwardWallet\MainBundle\Entity\Room as EntityRoom;
use AwardWallet\MainBundle\Loyalty\AccountSaving\SavingOptions;
use AwardWallet\Schema\Itineraries\ConfNo as SchemaConfNo;
use AwardWallet\Schema\Itineraries\HotelReservation as SchemaReservation;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;
use AwardWallet\Schema\Itineraries\Person as SchemaPerson;
use AwardWallet\Schema\Itineraries\Room as SchemaRoom;

class HotelReservationConverter extends AbstractConverter implements ItinerarySchema2EntityConverterInterface
{
    /**
     * @param SchemaItinerary|SchemaReservation $schemaItinerary
     * @param EntityItinerary|EntityReservation $entityItinerary
     * @return EntityReservation
     */
    public function convert(
        SchemaItinerary $schemaItinerary,
        ?EntityItinerary $entityItinerary,
        SavingOptions $options
    ): EntityItinerary {
        $this->helper->validateObject($schemaItinerary, SchemaReservation::class);
        $update = !is_null($entityItinerary);

        if (!$update) {
            $entityItinerary = new EntityReservation();
            $entityItinerary->setUser($options->getOwner()->getUser());
            $entityItinerary->setUserAgent($options->getOwner()->getFamilyMember());
        } else {
            $this->helper->validateObject($entityItinerary, EntityReservation::class);
        }

        $this->baseConverter->convert($schemaItinerary, $entityItinerary, $options);

        // confirmationNumbers
        if (!is_null($confirmationNumbers = $schemaItinerary->confirmationNumbers)) {
            $entityItinerary->setConfirmationNumber(
                $this->helper->extractPrimaryConfirmationNumber(array_merge(
                    $confirmationNumbers,
                    $schemaItinerary->travelAgency->confirmationNumbers ?? [],
                ))
            );
            $entityItinerary->setProviderConfirmationNumbers(array_map(fn (SchemaConfNo $confNo) => $confNo->number, $confirmationNumbers));
        }

        // hotelName
        if (!is_null($hotelName = $schemaItinerary->hotelName)) {
            $entityItinerary->setHotelname($hotelName);
        }

        // chainName
        if (!is_null($chainName = $schemaItinerary->chainName)) {
            $entityItinerary->setChainName($chainName);
        }

        // address
        if (!is_null($address = $schemaItinerary->address->text ?? null)) {
            $entityItinerary->setAddress($address);
            $entityItinerary->setGeoTag($this->helper->convertAddress2GeoTag($address));
        }

        // checkInDate
        if ($this->shouldUpdateDate($entityItinerary->getCheckindate(), $schemaItinerary->checkInDate)) {
            $entityItinerary->setCheckindate(new \DateTime($schemaItinerary->checkInDate));
        }

        // checkOutDate
        if ($this->shouldUpdateDate($entityItinerary->getCheckoutdate(), $schemaItinerary->checkOutDate)) {
            $entityItinerary->setCheckoutdate(new \DateTime($schemaItinerary->checkOutDate));
        }

        // phone
        if (!is_null($phone = $schemaItinerary->phone)) {
            $entityItinerary->setPhone($phone);
        }

        // fax
        if (!is_null($fax = $schemaItinerary->fax)) {
            $entityItinerary->setFax($fax);
        }

        // guests
        if (!is_null($guests = $schemaItinerary->guests)) {
            if ($update) {
                $this->helper->updateTravelerNames($guests, $entityItinerary, $schemaItinerary, $options->isPartialUpdate());
            } else {
                $entityItinerary->setTravelerNames(
                    array_map(fn (SchemaPerson $person) => $person->name, $guests)
                );
            }
        }

        // guestCount
        if (!is_null($guestCount = $schemaItinerary->guestCount)) {
            $entityItinerary->setGuestCount($guestCount);
        }

        // kidsCount
        if (!is_null($kidsCount = $schemaItinerary->kidsCount)) {
            $entityItinerary->setKidsCount($kidsCount);
        }

        // roomsCount
        if (!is_null($roomsCount = $schemaItinerary->roomsCount)) {
            $entityItinerary->setRoomCount($roomsCount);
        }

        // cancellationNumber
        if (!is_null($cancellationNumber = $schemaItinerary->cancellationNumber)) {
            $entityItinerary->setCancellationNumber($cancellationNumber);
        }

        // cancellationDeadline
        if (!is_null($cancellationDeadline = $schemaItinerary->cancellationDeadline)) {
            $entityItinerary->setCancellationDeadline(new \DateTime($cancellationDeadline));
        }

        // isNonRefundable
        $entityItinerary->setNonRefundable($schemaItinerary->isNonRefundable);

        // rooms
        if (is_array($rooms = $schemaItinerary->rooms)) {
            $entityItinerary->setRooms(
                array_map(fn (SchemaRoom $room) => new EntityRoom(
                    $room->type,
                    $room->description,
                    $room->rate,
                    $room->rateType,
                ), $rooms)
            );
        }

        // freeNights
        if (!is_null($freeNights = $schemaItinerary->freeNights)) {
            $entityItinerary->setFreeNights($freeNights);
        }

        return $entityItinerary;
    }

    // prevent checkIn and checkOut dates with no time rewriting valid date with a default time value
    private function shouldUpdateDate(?\DateTime $entityDate, ?string $schemaDate): bool
    {
        if (empty($schemaDate)) {
            return false;
        }

        if (empty($entityDate)) {
            return true;
        }
        $schemaDate = new \DateTime($schemaDate);

        if ($entityDate->format('Y-m-d') !== $schemaDate->format('Y-m-d')) {
            return true;
        }

        return $schemaDate->format('H:i') !== '00:00';
    }
}
