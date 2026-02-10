<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Trip as EntityTrip;
use AwardWallet\MainBundle\Entity\Tripsegment as EntityTripSegment;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Segments\CruiseSegmentMatcher;
use AwardWallet\MainBundle\Loyalty\AccountSaving\SavingOptions;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\Validator;
use AwardWallet\Schema\Itineraries\ConfNo as SchemaConfNo;
use AwardWallet\Schema\Itineraries\Cruise as SchemaCruise;
use AwardWallet\Schema\Itineraries\CruiseSegment as SchemaCruiseSegment;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;
use AwardWallet\Schema\Itineraries\Person as SchemaPerson;

class CruiseConverter extends AbstractSegmentConverter implements ItinerarySchema2EntityConverterInterface
{
    public function __construct(
        LoggerFactory $loggerFactory,
        BaseConverter $baseConverter,
        Helper $helper,
        CruiseSegmentMatcher $segmentMatcher,
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
     * @param SchemaItinerary|SchemaCruise $schemaItinerary
     * @param EntityItinerary|EntityTrip $entityItinerary
     * @return EntityTrip
     */
    public function convert(
        SchemaItinerary $schemaItinerary,
        ?EntityItinerary $entityItinerary,
        SavingOptions $options
    ): EntityItinerary {
        $this->helper->validateObject($schemaItinerary, SchemaCruise::class);
        $update = !is_null($entityItinerary);

        if (!$update) {
            $entityItinerary = new EntityTrip();
            $entityItinerary->setCategory(EntityTrip::CATEGORY_CRUISE);
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

        // cruiseDetails, description
        if (!is_null($description = $schemaItinerary->cruiseDetails->description ?? null)) {
            $entityItinerary->setCruiseName($description);
        }

        // cruiseDetails, class
        if (!is_null($class = $schemaItinerary->cruiseDetails->class ?? null)) {
            $entityItinerary->setShipCabinClass($class);
        }

        // cruiseDetails, deck
        if (!is_null($deck = $schemaItinerary->cruiseDetails->deck ?? null)) {
            $entityItinerary->setDeck($deck);
        }

        // cruiseDetails, room
        if (!is_null($room = $schemaItinerary->cruiseDetails->room ?? null)) {
            $entityItinerary->setCabinNumber($room);
        }

        // cruiseDetails, ship
        if (!is_null($ship = $schemaItinerary->cruiseDetails->ship ?? null)) {
            $entityItinerary->setShipName($ship);
        }

        // cruiseDetails, shipCode
        if (!is_null($shipCode = $schemaItinerary->cruiseDetails->shipCode ?? null)) {
            $entityItinerary->setShipCode($shipCode);
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
     * @param SchemaCruiseSegment $schemaSegment
     */
    public function convertSegment(
        SchemaItinerary $schemaItinerary,
        $schemaSegment,
        EntityTrip $entityTrip,
        ?EntityTripSegment $entitySegment,
        SavingOptions $options
    ): EntityTripSegment {
        $this->helper->validateObject($schemaSegment, SchemaCruiseSegment::class);
        $update = !is_null($entitySegment);

        if (!$update) {
            $entitySegment = new EntityTripSegment();
        } else {
            $this->helper->validateObject($entitySegment, EntityTripSegment::class);
        }

        $this->baseConverter->convertSegment($schemaItinerary, $schemaSegment, $entityTrip, $entitySegment, $options);

        // departure, stationCode
        if (!is_null($depStationCode = $schemaSegment->departure->stationCode ?? null)) {
            $entitySegment->setDepcode($depStationCode);
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

        // arrival, stationCode
        if (!is_null($arrStationCode = $schemaSegment->arrival->stationCode ?? null)) {
            $entitySegment->setArrcode($arrStationCode);
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

        // cruiseDetails, voyageNumber
        if (!is_null($voyageNumber = $schemaItinerary->cruiseDetails->voyageNumber ?? null)) {
            $entitySegment->setFlightNumber($voyageNumber);
        }

        return $entitySegment;
    }
}
