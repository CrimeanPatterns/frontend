<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\TicketNumber as EntityTicketNumber;
use AwardWallet\MainBundle\Entity\Trip as EntityTrip;
use AwardWallet\MainBundle\Entity\Tripsegment as EntityTripSegment;
use AwardWallet\MainBundle\Entity\Vehicle as EntityVehicle;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Segments\FerrySegmentMatcher;
use AwardWallet\MainBundle\Loyalty\AccountSaving\SavingOptions;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\Validator;
use AwardWallet\Schema\Itineraries\ConfNo as SchemaConfNo;
use AwardWallet\Schema\Itineraries\Ferry as SchemaFerry;
use AwardWallet\Schema\Itineraries\FerrySegment as SchemaFerrySegment;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;
use AwardWallet\Schema\Itineraries\ParsedNumber as SchemaParsedNumber;
use AwardWallet\Schema\Itineraries\Person as SchemaPerson;
use AwardWallet\Schema\Itineraries\VehicleExt as SchemaVehicleExt;

class FerryConverter extends AbstractSegmentConverter implements ItinerarySchema2EntityConverterInterface
{
    public function __construct(
        LoggerFactory $loggerFactory,
        BaseConverter $baseConverter,
        Helper $helper,
        FerrySegmentMatcher $segmentMatcher,
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
     * @param SchemaItinerary|SchemaFerry $schemaItinerary
     * @param EntityItinerary|EntityTrip $entityItinerary
     * @return EntityTrip
     */
    public function convert(
        SchemaItinerary $schemaItinerary,
        ?EntityItinerary $entityItinerary,
        SavingOptions $options
    ): EntityItinerary {
        $this->helper->validateObject($schemaItinerary, SchemaFerry::class);
        $update = !is_null($entityItinerary);

        if (!$update) {
            $entityItinerary = new EntityTrip();
            $entityItinerary->setCategory(EntityTrip::CATEGORY_FERRY);
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

        // ticketNumbers
        if (is_array($ticketNumbers = $schemaItinerary->ticketNumbers)) {
            $entityItinerary->setTicketNumbers(array_map(fn (SchemaParsedNumber $number) => new EntityTicketNumber($number->number, $number->masked), $ticketNumbers));
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
     * @param SchemaFerrySegment $schemaSegment
     */
    public function convertSegment(
        SchemaItinerary $schemaItinerary,
        $schemaSegment,
        EntityTrip $entityTrip,
        ?EntityTripSegment $entitySegment,
        SavingOptions $options
    ): EntityTripSegment {
        $this->helper->validateObject($schemaSegment, SchemaFerrySegment::class);
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

        // accommodations
        if (is_array($accommodations = $schemaSegment->accommodations)) {
            $entitySegment->setAccommodations($accommodations);
        }

        // carrier
        if (!is_null($carrier = $schemaSegment->carrier)) {
            $entitySegment->setAircraftName($carrier);
        }

        // vessel
        if (!is_null($vessel = $schemaSegment->vessel)) {
            $entitySegment->setVessel($vessel);
        }

        // traveledMiles
        if (!is_null($traveledMiles = $schemaSegment->traveledMiles)) {
            $entitySegment->setTraveledMiles($traveledMiles);
        }

        // duration
        if (!is_null($duration = $schemaSegment->duration)) {
            $entitySegment->setDuration($duration);
        }

        // meal
        if (!is_null($meal = $schemaSegment->meal)) {
            $entitySegment->setMeal($meal);
        }

        // cabin
        if (!is_null($cabin = $schemaSegment->cabin)) {
            $entitySegment->setCabinClass($cabin);
        }

        // smoking
        if (!is_null($smoking = $schemaSegment->smoking)) {
            $entitySegment->setSmoking($smoking);
        }

        // adultsCount
        if (!is_null($adultsCount = $schemaSegment->adultsCount)) {
            $entitySegment->setAdultsCount($adultsCount);
        }

        // kidsCount
        if (!is_null($kidsCount = $schemaSegment->kidsCount)) {
            $entitySegment->setKidsCount($kidsCount);
        }

        // pets
        if (!is_null($pets = $schemaSegment->pets)) {
            $entitySegment->setPets($pets);
        }

        // vehicles
        if (is_array($vehicles = $schemaSegment->vehicles)) {
            $entitySegment->setVehicles(
                array_filter(array_map(function (SchemaVehicleExt $vehicle) {
                    return (new EntityVehicle())
                        ->setType($vehicle->type)
                        ->setModel($vehicle->model)
                        ->setHeight($vehicle->height)
                        ->setWidth($vehicle->width)
                        ->setLength($vehicle->length);
                }, $vehicles))
            );
        }

        // trailers
        if (is_array($trailers = $schemaSegment->trailers)) {
            $entitySegment->setTrailers(
                array_filter(array_map(function (SchemaVehicleExt $vehicle) {
                    return (new EntityVehicle())
                        ->setType($vehicle->type)
                        ->setModel($vehicle->model)
                        ->setHeight($vehicle->height)
                        ->setWidth($vehicle->width)
                        ->setLength($vehicle->length);
                }, $trailers))
            );
        }

        return $entitySegment;
    }
}
