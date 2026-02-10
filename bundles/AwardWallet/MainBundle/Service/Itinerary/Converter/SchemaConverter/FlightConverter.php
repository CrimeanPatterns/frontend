<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Repositories\AircraftRepository;
use AwardWallet\MainBundle\Entity\Repositories\AirlineRepository;
use AwardWallet\MainBundle\Entity\TicketNumber as EntityTicketNumber;
use AwardWallet\MainBundle\Entity\Trip as EntityTrip;
use AwardWallet\MainBundle\Entity\Tripsegment as EntityTripSegment;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Segments\FlightSegmentMatcher;
use AwardWallet\MainBundle\Loyalty\AccountSaving\SavingOptions;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\Validator;
use AwardWallet\Schema\Itineraries\Flight as SchemaFlight;
use AwardWallet\Schema\Itineraries\FlightSegment as SchemaFlightSegment;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;
use AwardWallet\Schema\Itineraries\ParsedNumber as SchemaParsedNumber;
use AwardWallet\Schema\Itineraries\Person as SchemaPerson;
use AwardWallet\Schema\Itineraries\PhoneNumber as SchemaPhoneNumber;

class FlightConverter extends AbstractSegmentConverter implements ItinerarySchema2EntityConverterInterface
{
    private AirlineRepository $airlineRepository;

    private AircraftRepository $aircraftRepository;

    public function __construct(
        LoggerFactory $loggerFactory,
        BaseConverter $baseConverter,
        Helper $helper,
        FlightSegmentMatcher $segmentMatcher,
        Validator $sourcesValidator,
        AirlineRepository $airlineRepository,
        AircraftRepository $aircraftRepository
    ) {
        parent::__construct(
            $loggerFactory,
            $baseConverter,
            $helper,
            $segmentMatcher,
            $sourcesValidator
        );

        $this->airlineRepository = $airlineRepository;
        $this->aircraftRepository = $aircraftRepository;
    }

    /**
     * @param SchemaItinerary|SchemaFlight $schemaItinerary
     * @param EntityItinerary|EntityTrip $entityItinerary
     * @return EntityTrip
     */
    public function convert(
        SchemaItinerary $schemaItinerary,
        ?EntityItinerary $entityItinerary,
        SavingOptions $options
    ): EntityItinerary {
        $this->helper->validateObject($schemaItinerary, SchemaFlight::class);

        if (!$this->validateFlightSchema($schemaItinerary)) {
            throw new ConstructException('Ambiguous confirmation numbers!');
        }

        $update = !is_null($entityItinerary);

        if (!$update) {
            $entityItinerary = new EntityTrip();
            $entityItinerary->setCategory(EntityTrip::CATEGORY_AIR);
            $entityItinerary->setUser($options->getOwner()->getUser());
            $entityItinerary->setUserAgent($options->getOwner()->getFamilyMember());
        } else {
            $this->helper->validateObject($entityItinerary, EntityTrip::class);
        }

        $this->baseConverter->convert($schemaItinerary, $entityItinerary, $options);

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

        // issuingCarrier, airline
        if (!is_null($airline = $schemaItinerary->issuingCarrier->airline ?? null)) {
            $entityItinerary->setAirline(
                $this->airlineRepository->search($airline->icao, $airline->iata, $airline->name)
            );

            if (is_null($entityItinerary->getAirline())) {
                $entityItinerary->setAirlineName($airline->name);
            }
        }

        // issuingCarrier, confirmationNumber
        if (!is_null($confirmationNumber = $schemaItinerary->issuingCarrier->confirmationNumber ?? null)) {
            $entityItinerary->setIssuingAirlineConfirmationNumber($confirmationNumber);
            $entityItinerary->setProviderConfirmationNumbers([$confirmationNumber]);
        }

        // issuingCarrier, phoneNumbers
        if (is_array($phoneNumbers = $schemaItinerary->issuingCarrier->phoneNumbers ?? null)) {
            $entityItinerary->setPhone($phoneNumbers[0]->number);
        }

        // issuingCarrier, ticketNumbers
        if (is_array($ticketNumbers = $schemaItinerary->issuingCarrier->ticketNumbers ?? null)) {
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
     * @param SchemaFlightSegment $schemaSegment
     */
    public function convertSegment(
        SchemaItinerary $schemaItinerary,
        $schemaSegment,
        EntityTrip $entityTrip,
        ?EntityTripSegment $entitySegment,
        SavingOptions $options
    ): EntityTripSegment {
        $this->helper->validateObject($schemaSegment, SchemaFlightSegment::class);

        $update = !is_null($entitySegment);

        if (!$update) {
            $entitySegment = new EntityTripSegment();
        } else {
            $this->helper->validateObject($entitySegment, EntityTripSegment::class);
        }

        $this->baseConverter->convertSegment($schemaItinerary, $schemaSegment, $entityTrip, $entitySegment, $options);

        // departure, code
        if (!is_null($depAirportCode = $schemaSegment->departure->airportCode ?? null)) {
            $entitySegment->setDepcode($depAirportCode);
        }

        // departure, terminal
        if (!is_null($depTerminal = $schemaSegment->departure->terminal ?? null)) {
            $entitySegment->setDepartureTerminal($depTerminal);
        }

        // departure, name
        if (!is_null($depName = $schemaSegment->departure->name ?? null)) {
            $entitySegment->setDepname($depName);
        }

        // departure, localDateTime
        if (!is_null($depLocalDateTime = $schemaSegment->departure->localDateTime ?? null)) {
            $entitySegment->setDepartureDate(new \DateTime($depLocalDateTime));
            $entitySegment->setScheduledDepDate(new \DateTime($depLocalDateTime));
        }

        // departure, address
        if (!is_null($depAddress = $schemaSegment->departure->address->text ?? null)) {
            $entitySegment->setDepgeotagid($this->helper->convertAddress2GeoTag($depAddress));
        }

        // arrival, code
        if (!is_null($arrAirportCode = $schemaSegment->arrival->airportCode ?? null)) {
            $entitySegment->setArrcode($arrAirportCode);
        }

        // arrival, terminal
        if (!is_null($arrTerminal = $schemaSegment->arrival->terminal ?? null)) {
            $entitySegment->setArrivalTerminal($arrTerminal);
        }

        // arrival, name
        if (!is_null($arrName = $schemaSegment->arrival->name ?? null)) {
            $entitySegment->setArrname($arrName);
        }

        // arrival, localDateTime
        if (!is_null($arrLocalDateTime = $schemaSegment->arrival->localDateTime ?? null)) {
            $entitySegment->setArrivalDate(new \DateTime($arrLocalDateTime));
            $entitySegment->setScheduledArrDate(new \DateTime($arrLocalDateTime));
        }

        // arrival, address
        if (!is_null($arrAddress = $schemaSegment->arrival->address->text ?? null)) {
            $entitySegment->setArrgeotagid($this->helper->convertAddress2GeoTag($arrAddress));
        }

        // marketingCarrier, airline
        if (!is_null($airline = $schemaSegment->marketingCarrier->airline ?? null)) {
            $entitySegment->setAirline(
                $this->airlineRepository->search($airline->icao, $airline->iata, $airline->name)
            );

            if (is_null($entitySegment->getAirline())) {
                $entitySegment->setAirlineName($airline->name);
            }
        }

        // marketingCarrier, flightNumber
        if (!is_null($flightNumber = $schemaSegment->marketingCarrier->flightNumber ?? null)) {
            $entitySegment->setFlightNumber($flightNumber);
        }

        // marketingCarrier, confirmationNumber
        if (!is_null($confirmationNumber = $schemaSegment->marketingCarrier->confirmationNumber ?? null)) {
            $entitySegment->setMarketingAirlineConfirmationNumber($confirmationNumber);
        }

        // marketingCarrier, phoneNumbers
        if (is_array($phoneNumbers = $schemaSegment->marketingCarrier->phoneNumbers ?? null)) {
            $entitySegment->setMarketingAirlinePhoneNumbers(array_map(fn (SchemaPhoneNumber $number) => $number->number, $phoneNumbers));
        }

        // operatingCarrier, airline
        if (!is_null($airline = $schemaSegment->operatingCarrier->airline ?? null)) {
            $entitySegment->setOperatingAirline(
                $this->airlineRepository->search($airline->icao, $airline->iata, $airline->name)
            );

            if (is_null($entitySegment->getOperatingAirline())) {
                $entitySegment->setOperatingAirlineName($airline->name);
            }
        }

        // operatingCarrier, flightNumber
        if (!is_null($flightNumber = $schemaSegment->operatingCarrier->flightNumber ?? null)) {
            $entitySegment->setOperatingAirlineFlightNumber($flightNumber);
        }

        // operatingCarrier, confirmationNumber
        if (!is_null($confirmationNumber = $schemaSegment->operatingCarrier->confirmationNumber ?? null)) {
            $entitySegment->setOperatingAirlineConfirmationNumber($confirmationNumber);
        }

        // operatingCarrier, phoneNumbers
        if (is_array($phoneNumbers = $schemaSegment->operatingCarrier->phoneNumbers ?? null)) {
            $entitySegment->setOperatingAirlinePhoneNumbers(array_map(fn (SchemaPhoneNumber $number) => $number->number, $phoneNumbers));
        }

        // wetleaseCarrier
        if (!is_null($airline = $schemaSegment->wetleaseCarrier)) {
            $entitySegment->setWetLeaseAirline(
                $this->airlineRepository->search($airline->icao, $airline->iata, $airline->name)
            );

            if (is_null($entitySegment->getWetLeaseAirline())) {
                $entitySegment->setWetLeaseAirlineName($airline->name);
            }
        }

        // seats
        if (is_array($seats = $schemaSegment->seats)) {
            if ($update) {
                $this->helper->setSeats($seats, $entityTrip, $entitySegment, $schemaItinerary);
            } else {
                $entitySegment->setSeats($seats);
            }
        }

        // aircraft
        if (!is_null($aircraft = $schemaSegment->aircraft)) {
            if (!is_null($iataCode = $aircraft->iataCode)) {
                $entitySegment->setAircraft($this->aircraftRepository->findOneBy(['IataCode' => $iataCode]));
            }

            if (is_null($entitySegment->getAircraft())) {
                $entitySegment->setAircraftName($aircraft->name);
            }
        }

        // traveledMiles, todo: calculatedTraveledMiles ???
        if (!is_null($traveledMiles = $schemaSegment->traveledMiles)) {
            $entitySegment->setTraveledMiles($traveledMiles);
        }

        // cabin
        if (!is_null($cabin = $schemaSegment->cabin)) {
            $entitySegment->setCabinClass($cabin);
        }

        // bookingCode
        if (!is_null($bookingCode = $schemaSegment->bookingCode)) {
            $entitySegment->setBookingClass($bookingCode);
        }

        // duration, todo: calculatedDuration ???
        if (!is_null($duration = $schemaSegment->duration)) {
            $entitySegment->setDuration($duration);
        }

        // meal
        if (!is_null($meal = $schemaSegment->meal)) {
            $entitySegment->setMeal($meal);
        }

        // smoking
        if (!is_null($smoking = $schemaSegment->smoking)) {
            $entitySegment->setSmoking($smoking);
        }

        // status
        if (!is_null($status = $schemaSegment->status)) {
            $entitySegment->setParsedStatus($status);
        }

        // cancelled
        if ($schemaSegment->cancelled) {
            $entitySegment->cancel();
        }

        // stops
        if (!is_null($stops = $schemaSegment->stops)) {
            $entitySegment->setStops($stops);
        }

        // todo: flightStatsMethodUsed ???

        return $entitySegment;
    }

    private function validateFlightSchema(SchemaFlight $schemaFlight): bool
    {
        return
            count(
                array_unique(
                    array_filter(
                        array_map(function (SchemaFlightSegment $segment) {
                            return $segment->marketingCarrier->confirmationNumber;
                        }, $schemaFlight->segments ?? [])
                    )
                )
            ) === 1
            || (
                is_array($confirmationNumbers = $schemaFlight->travelAgency->confirmationNumbers ?? null)
                && count($confirmationNumbers) > 0
            )
            || !is_null($schemaFlight->issuingCarrier->confirmationNumber ?? null);
    }
}
