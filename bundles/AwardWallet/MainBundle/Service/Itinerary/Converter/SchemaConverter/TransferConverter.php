<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Trip as EntityTrip;
use AwardWallet\MainBundle\Entity\Tripsegment as EntityTripSegment;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Segments\TransferSegmentMatcher;
use AwardWallet\MainBundle\Loyalty\AccountSaving\SavingOptions;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\Validator;
use AwardWallet\Schema\Itineraries\ConfNo as SchemaConfNo;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;
use AwardWallet\Schema\Itineraries\Person as SchemaPerson;
use AwardWallet\Schema\Itineraries\Transfer as SchemaTransfer;
use AwardWallet\Schema\Itineraries\TransferSegment as SchemaTransferSegment;

class TransferConverter extends AbstractSegmentConverter implements ItinerarySchema2EntityConverterInterface
{
    public function __construct(
        LoggerFactory $loggerFactory,
        BaseConverter $baseConverter,
        Helper $helper,
        TransferSegmentMatcher $segmentMatcher,
        Validator $sourcesValidator
    ) {
        parent::__construct(
            $loggerFactory,
            $baseConverter,
            $helper,
            $segmentMatcher,
            $sourcesValidator
        );
    }

    /**
     * @param SchemaItinerary|SchemaTransfer $schemaItinerary
     * @param EntityItinerary|EntityTrip $entityItinerary
     * @return EntityTrip
     */
    public function convert(
        SchemaItinerary $schemaItinerary,
        ?EntityItinerary $entityItinerary,
        SavingOptions $options
    ): EntityItinerary {
        $this->helper->validateObject($schemaItinerary, SchemaTransfer::class);
        $update = !is_null($entityItinerary);

        if (!$update) {
            $entityItinerary = new EntityTrip();
            $entityItinerary->setCategory(EntityTrip::CATEGORY_TRANSFER);
            $entityItinerary->setUser($options->getOwner()->getUser());
            $entityItinerary->setUserAgent($options->getOwner()->getFamilyMember());
        } else {
            $this->helper->validateObject($entityItinerary, EntityTrip::class);
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

        // travelers
        if (!is_null($travelers = $schemaItinerary->travelers)) {
            if ($update) {
                $this->helper->updateTravelerNames($travelers, $entityItinerary, $schemaItinerary, $options->isPartialUpdate());
            } else {
                $entityItinerary->setTravelerNames(
                    array_map(fn (SchemaPerson $person) => $person->name, $travelers)
                );
            }
        }

        // segments
        if ($update) {
            $this->updateSegments($entityItinerary, $schemaItinerary, $options);
        } else {
            foreach ($schemaItinerary->segments ?? [] as $schemaSegment) {
                $entityItinerary->addSegment(
                    $this->convertSegment(
                        $schemaItinerary,
                        $schemaSegment,
                        $entityItinerary,
                        null,
                        $options
                    )
                );
            }
        }

        return $entityItinerary;
    }

    /**
     * @param SchemaTransferSegment $schemaSegment
     */
    public function convertSegment(
        SchemaItinerary $schemaItinerary,
        $schemaSegment,
        EntityTrip $entityTrip,
        ?EntityTripSegment $entitySegment,
        SavingOptions $options
    ): EntityTripSegment {
        $this->helper->validateObject($schemaSegment, SchemaTransferSegment::class);
        $update = !is_null($entitySegment);

        if (!$update) {
            $entitySegment = new EntityTripSegment();
        } else {
            $this->helper->validateObject($entitySegment, EntityTripSegment::class);
        }

        $this->baseConverter->convertSegment($schemaItinerary, $schemaSegment, $entityTrip, $entitySegment, $options);

        // departure, airportCode
        if (!is_null($depAirportCode = $schemaSegment->departure->airportCode ?? null)) {
            $entitySegment->setDepcode($depAirportCode);
        }

        // departure, name
        if (!is_null($depName = $schemaSegment->departure->name ?? null)) {
            $entitySegment->setDepname($depName);
        }

        // departure, localDateTime
        if (!is_null($depLocalDateTime = $schemaSegment->departure->localDateTime ?? null)) {
            $entitySegment->setDepartureDate(new \DateTime($depLocalDateTime));
        }

        // departure, address
        if (!is_null($depAddress = $schemaSegment->departure->address->text ?? null)) {
            $entitySegment->setDepgeotagid($this->helper->convertAddress2GeoTag($depAddress));
        }

        // arrival, airportCode
        if (!is_null($arrAirportCode = $schemaSegment->arrival->airportCode ?? null)) {
            $entitySegment->setArrcode($arrAirportCode);
        }

        // arrival, name
        if (!is_null($arrName = $schemaSegment->arrival->name ?? null)) {
            $entitySegment->setArrname($arrName);
        }

        // arrival, localDateTime
        if (!is_null($arrLocalDateTime = $schemaSegment->arrival->localDateTime ?? null)) {
            $entitySegment->setArrivalDate(new \DateTime($arrLocalDateTime));
        }

        // arrival, address
        if (!is_null($arrAddress = $schemaSegment->arrival->address->text ?? null)) {
            $entitySegment->setArrgeotagid($this->helper->convertAddress2GeoTag($arrAddress));
        }

        // vehicleInfo
        if (!is_null($model = $schemaSegment->vehicleInfo->model ?? null)) {
            $entitySegment->setAircraftName($model);
        }

        // adults
        if (!is_null($adults = $schemaSegment->adults)) {
            $entitySegment->setAdultsCount($adults);
        }

        // kids
        if (!is_null($kids = $schemaSegment->kids)) {
            $entitySegment->setKidsCount($kids);
        }

        // traveledMiles
        if (!is_null($traveledMiles = $schemaSegment->traveledMiles)) {
            $entitySegment->setTraveledMiles($traveledMiles);
        }

        // duration
        if (!is_null($duration = $schemaSegment->duration)) {
            $entitySegment->setDuration($duration);
        }

        return $entitySegment;
    }
}
