<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Parking as EntityParking;
use AwardWallet\MainBundle\Loyalty\AccountSaving\SavingOptions;
use AwardWallet\Schema\Itineraries\ConfNo as SchemaConfNo;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;
use AwardWallet\Schema\Itineraries\Parking as SchemaParking;

class ParkingConverter extends AbstractConverter implements ItinerarySchema2EntityConverterInterface
{
    /**
     * @param SchemaItinerary|SchemaParking $schemaItinerary
     * @param EntityItinerary|EntityParking $entityItinerary
     * @return EntityParking
     */
    public function convert(
        SchemaItinerary $schemaItinerary,
        ?EntityItinerary $entityItinerary,
        SavingOptions $options
    ): EntityItinerary {
        $this->helper->validateObject($schemaItinerary, SchemaParking::class);
        $update = !is_null($entityItinerary);

        if (!$update) {
            $entityItinerary = new EntityParking();
            $entityItinerary->setUser($options->getOwner()->getUser());
            $entityItinerary->setUserAgent($options->getOwner()->getFamilyMember());
        } else {
            $this->helper->validateObject($entityItinerary, EntityParking::class);
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
            $entityItinerary->setGeoTagID($this->helper->convertAddress2GeoTag($address));
        }

        // spotNumber
        if (!is_null($spotNumber = $schemaItinerary->spotNumber)) {
            $entityItinerary->setSpot($spotNumber);
        }

        // licensePlate
        if (!is_null($licensePlate = $schemaItinerary->licensePlate)) {
            $entityItinerary->setPlate($licensePlate);
        }

        // startDateTime
        if (!is_null($startDateTime = $schemaItinerary->startDateTime)) {
            $entityItinerary->setStartDatetime(new \DateTime($startDateTime));
        }

        // endDateTime
        if (!is_null($endDateTime = $schemaItinerary->endDateTime)) {
            $entityItinerary->setEndDatetime(new \DateTime($endDateTime));
        }

        // phone
        if (!is_null($phone = $schemaItinerary->phone)) {
            $entityItinerary->setPhone($phone);
        }

        // owner
        if (!is_null($owner = $schemaItinerary->owner)) {
            if ($update) {
                $this->helper->updateTravelerNames([$owner], $entityItinerary, $schemaItinerary, $options->isPartialUpdate());
            } else {
                $entityItinerary->setTravelerNames([$owner->name]);
            }
        }

        // rateType
        if (!is_null($rateType = $schemaItinerary->rateType)) {
            $entityItinerary->setRateType($rateType);
        }

        // carDescription
        if (!is_null($carDescription = $schemaItinerary->carDescription)) {
            $entityItinerary->setCarDescription($carDescription);
        }

        if (!is_null($companyName = $schemaItinerary->companyName)) {
            $entityItinerary->setParkingCompanyName($companyName);
        }

        return $entityItinerary;
    }
}
