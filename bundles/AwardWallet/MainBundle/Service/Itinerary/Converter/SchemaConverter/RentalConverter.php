<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\PricedEquipment as EntityPricedEquipment;
use AwardWallet\MainBundle\Entity\Rental as EntityRental;
use AwardWallet\MainBundle\Entity\RentalDiscountDetails as EntityRentalDiscountDetails;
use AwardWallet\MainBundle\Loyalty\AccountSaving\SavingOptions;
use AwardWallet\Schema\Itineraries\CarRental as SchemaRental;
use AwardWallet\Schema\Itineraries\CarRentalDiscount as SchemaCarRentalDiscount;
use AwardWallet\Schema\Itineraries\ConfNo as SchemaConfNo;
use AwardWallet\Schema\Itineraries\Fee as SchemaFee;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;

class RentalConverter extends AbstractConverter implements ItinerarySchema2EntityConverterInterface
{
    /**
     * @param SchemaItinerary|SchemaRental $schemaItinerary
     * @param EntityItinerary|EntityRental $entityItinerary
     * @return EntityRental
     */
    public function convert(
        SchemaItinerary $schemaItinerary,
        ?EntityItinerary $entityItinerary,
        SavingOptions $options
    ): EntityItinerary {
        $this->helper->validateObject($schemaItinerary, SchemaRental::class);
        $update = !is_null($entityItinerary);

        if (!$update) {
            $entityItinerary = new EntityRental();
            $entityItinerary->setUser($options->getOwner()->getUser());
            $entityItinerary->setUserAgent($options->getOwner()->getFamilyMember());
        } else {
            $this->helper->validateObject($entityItinerary, EntityRental::class);
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

        // pickup
        if (!is_null($pickupAddress = $schemaItinerary->pickup->address->text ?? null)) {
            $entityItinerary->setPickupgeotagid($this->helper->convertAddress2GeoTag($pickupAddress));
        }

        if (!is_null($pickupDateTime = $schemaItinerary->pickup->localDateTime ?? null)) {
            $entityItinerary->setPickupdatetime(new \DateTime($pickupDateTime));
        }

        if (!is_null($pickupOpeningHours = $schemaItinerary->pickup->openingHours ?? null)) {
            $entityItinerary->setPickuphours($pickupOpeningHours);
        }

        if (!is_null($pickupPhone = $schemaItinerary->pickup->phone ?? null)) {
            $entityItinerary->setPickupphone($pickupPhone);
        }

        if (!is_null($pickupFax = $schemaItinerary->pickup->fax ?? null)) {
            $entityItinerary->setPickUpFax($pickupFax);
        }

        // dropoff
        if (!is_null($dropoffAddress = $schemaItinerary->dropoff->address->text ?? null)) {
            $entityItinerary->setDropoffgeotagid($this->helper->convertAddress2GeoTag($dropoffAddress));
        }

        if (!is_null($dropoffDateTime = $schemaItinerary->dropoff->localDateTime ?? null)) {
            $entityItinerary->setDropoffdatetime(new \DateTime($dropoffDateTime));
        }

        if (!is_null($dropoffOpeningHours = $schemaItinerary->dropoff->openingHours ?? null)) {
            $entityItinerary->setDropoffhours($dropoffOpeningHours);
        }

        if (!is_null($dropoffPhone = $schemaItinerary->dropoff->phone ?? null)) {
            $entityItinerary->setDropoffphone($dropoffPhone);
        }

        if (!is_null($dropoffFax = $schemaItinerary->dropoff->fax ?? null)) {
            $entityItinerary->setDropOffFax($dropoffFax);
        }

        // car
        if (!is_null($carType = $schemaItinerary->car->type ?? null)) {
            $entityItinerary->setCarType($carType);
        }

        if (!is_null($carModel = $schemaItinerary->car->model ?? null)) {
            $entityItinerary->setCarModel($carModel);
        }

        if (!is_null($carImageUrl = $schemaItinerary->car->imageUrl ?? null)) {
            $entityItinerary->setCarImageUrl($carImageUrl);
        }

        // discounts
        if (is_array($discounts = $schemaItinerary->discounts)) {
            $entityItinerary->setDiscountDetails(
                array_filter(array_map(function (SchemaCarRentalDiscount $discount) {
                    if (empty($discount->name) || empty($discount->code)) {
                        return null;
                    }

                    return new EntityRentalDiscountDetails($discount->name, $discount->code);
                }, $discounts))
            );
        }

        // driver
        if (!is_null($driver = $schemaItinerary->driver)) {
            if ($update) {
                $this->helper->updateTravelerNames([$driver], $entityItinerary, $schemaItinerary, $options->isPartialUpdate());
            } else {
                $entityItinerary->setTravelerNames([$driver->name]);
            }
        }

        // pricedEquipment
        if (is_array($pricedEquipment = $schemaItinerary->pricedEquipment)) {
            $entityItinerary->setPricedEquipment(
                array_filter(array_map(function (SchemaFee $item) {
                    if (empty($item->name) || is_null($item->charge)) {
                        return null;
                    }

                    return new EntityPricedEquipment($item->name, $item->charge);
                }, $pricedEquipment))
            );
        }

        // rentalCompany
        if (!is_null($rentalCompany = $schemaItinerary->rentalCompany)) {
            $entityItinerary->setRentalCompanyName($rentalCompany);
        }

        return $entityItinerary;
    }
}
