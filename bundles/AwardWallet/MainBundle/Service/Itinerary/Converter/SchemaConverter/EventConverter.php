<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Restaurant as EntityEvent;
use AwardWallet\MainBundle\Loyalty\AccountSaving\SavingOptions;
use AwardWallet\Schema\Itineraries\ConfNo as SchemaConfNo;
use AwardWallet\Schema\Itineraries\Event as SchemaEvent;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;
use AwardWallet\Schema\Itineraries\Person as SchemaPerson;

class EventConverter extends AbstractConverter implements ItinerarySchema2EntityConverterInterface
{
    /**
     * @param SchemaItinerary|SchemaEvent $schemaItinerary
     * @param EntityItinerary|EntityEvent $entityItinerary
     * @return EntityEvent
     */
    public function convert(
        SchemaItinerary $schemaItinerary,
        ?EntityItinerary $entityItinerary,
        SavingOptions $options
    ): EntityItinerary {
        $this->helper->validateObject($schemaItinerary, SchemaEvent::class);
        $update = !is_null($entityItinerary);

        if (!$update) {
            $entityItinerary = new EntityEvent();
            $entityItinerary->setUser($options->getOwner()->getUser());
            $entityItinerary->setUserAgent($options->getOwner()->getFamilyMember());
        } else {
            $this->helper->validateObject($entityItinerary, EntityEvent::class);
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

        // address
        if (!is_null($address = $schemaItinerary->address->text ?? null)) {
            $entityItinerary->setAddress($address);
            $entityItinerary->setGeotagid($this->helper->convertAddress2GeoTag($address));
        }

        // eventName
        if (!is_null($eventName = $schemaItinerary->eventName)) {
            $entityItinerary->setName($eventName);
        }

        // eventType
        if (!is_null($eventType = $schemaItinerary->eventType)) {
            $entityItinerary->setEventtype($eventType);
        }

        // startDateTime
        if (!is_null($startDateTime = $schemaItinerary->startDateTime)) {
            $entityItinerary->setStartdate(new \DateTime($startDateTime));
        }

        // endDateTime
        if (!is_null($endDateTime = $schemaItinerary->endDateTime)) {
            $entityItinerary->setEnddate(new \DateTime($endDateTime));
        }

        // phone
        if (!is_null($phone = $schemaItinerary->phone)) {
            $entityItinerary->setPhone($phone);
        }

        // fax
        if (!is_null($fax = $schemaItinerary->fax)) {
            $entityItinerary->setFax($fax);
        }

        // guestCount
        if (!is_null($guestCount = $schemaItinerary->guestCount)) {
            $entityItinerary->setGuestCount($guestCount);
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

        // seats
        if (is_array($seats = $schemaItinerary->seats)) {
            $entityItinerary->setSeats($seats);
        }

        return $entityItinerary;
    }
}
