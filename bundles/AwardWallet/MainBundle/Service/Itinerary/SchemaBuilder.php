<?php

namespace AwardWallet\MainBundle\Service\Itinerary;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\Schema\Itineraries\Address as SchemaAddress;
use AwardWallet\Schema\Itineraries\Aircraft as SchemaAircraft;
use AwardWallet\Schema\Itineraries\Airline as SchemaAirline;
use AwardWallet\Schema\Itineraries\Bus as SchemaBus;
use AwardWallet\Schema\Itineraries\BusSegment as SchemaBusSegment;
use AwardWallet\Schema\Itineraries\Car as SchemaCar;
use AwardWallet\Schema\Itineraries\CarRental as SchemaRental;
use AwardWallet\Schema\Itineraries\CarRentalDiscount as SchemaCarRentalDiscount;
use AwardWallet\Schema\Itineraries\CarRentalLocation as SchemaCarRentalLocation;
use AwardWallet\Schema\Itineraries\ConfNo as SchemaConfNo;
use AwardWallet\Schema\Itineraries\Cruise as SchemaCruise;
use AwardWallet\Schema\Itineraries\CruiseDetails as SchemaCruiseDetails;
use AwardWallet\Schema\Itineraries\CruiseSegment as SchemaCruiseSegment;
use AwardWallet\Schema\Itineraries\Event as SchemaEvent;
use AwardWallet\Schema\Itineraries\Fee as SchemaFee;
use AwardWallet\Schema\Itineraries\Ferry as SchemaFerry;
use AwardWallet\Schema\Itineraries\FerrySegment as SchemaFerrySegment;
use AwardWallet\Schema\Itineraries\Flight as SchemaFlight;
use AwardWallet\Schema\Itineraries\FlightSegment as SchemaFlightSegment;
use AwardWallet\Schema\Itineraries\HotelReservation as SchemaReservation;
use AwardWallet\Schema\Itineraries\IssuingCarrier as SchemaIssuingCarrier;
use AwardWallet\Schema\Itineraries\MarketingCarrier as SchemaMarketingCarrier;
use AwardWallet\Schema\Itineraries\OperatingCarrier as SchemaOperatingCarrier;
use AwardWallet\Schema\Itineraries\Parking as SchemaParking;
use AwardWallet\Schema\Itineraries\ParsedNumber as SchemaParsedNumber;
use AwardWallet\Schema\Itineraries\Person as SchemaPerson;
use AwardWallet\Schema\Itineraries\PhoneNumber as SchemaPhoneNumber;
use AwardWallet\Schema\Itineraries\PricingInfo as SchemaPricingInfo;
use AwardWallet\Schema\Itineraries\ProviderInfo as SchemaProviderInfo;
use AwardWallet\Schema\Itineraries\Room as SchemaRoom;
use AwardWallet\Schema\Itineraries\Train as SchemaTrain;
use AwardWallet\Schema\Itineraries\TrainSegment as SchemaTrainSegment;
use AwardWallet\Schema\Itineraries\Transfer as SchemaTransfer;
use AwardWallet\Schema\Itineraries\TransferLocation as SchemaTransferLocation;
use AwardWallet\Schema\Itineraries\TransferSegment as SchemaTransferSegment;
use AwardWallet\Schema\Itineraries\TransportLocation as SchemaTransportLocation;
use AwardWallet\Schema\Itineraries\TravelAgency as SchemaTravelAgency;
use AwardWallet\Schema\Itineraries\TripLocation as SchemaTripLocation;
use AwardWallet\Schema\Itineraries\Vehicle as SchemaVehicle;
use AwardWallet\Schema\Itineraries\VehicleExt as SchemaVehicleExt;

/**
 * @NoDI()
 */
class SchemaBuilder
{
    /**
     * @param SchemaConfNo[]|null $confirmationNumbers
     * @param SchemaPhoneNumber[]|null $phoneNumbers
     */
    public static function makeSchemaTravelAgency(
        ?SchemaProviderInfo $providerInfo = null,
        ?array $confirmationNumbers = null,
        ?array $phoneNumbers = null,
        ?callable $creator = null
    ): SchemaTravelAgency {
        $travelAgency = new SchemaTravelAgency();
        $travelAgency->providerInfo = $providerInfo;
        $travelAgency->confirmationNumbers = $confirmationNumbers;
        $travelAgency->phoneNumbers = $phoneNumbers;

        if (is_callable($creator)) {
            $creator($travelAgency);
        }

        return $travelAgency;
    }

    /**
     * @param SchemaParsedNumber[]|null $accountNumbers
     */
    public static function makeSchemaProviderInfo(
        ?string $code = null,
        ?string $name = null,
        ?array $accountNumbers = null,
        ?string $earnedRewards = null,
        ?callable $creator = null
    ): SchemaProviderInfo {
        $providerInfo = new SchemaProviderInfo();
        $providerInfo->code = $code;
        $providerInfo->name = $name;
        $providerInfo->accountNumbers = $accountNumbers;
        $providerInfo->earnedRewards = $earnedRewards;

        if (is_callable($creator)) {
            $creator($providerInfo);
        }

        return $providerInfo;
    }

    public static function makeSchemaParsedNumber(string $number, bool $masked = false, ?callable $creator = null): SchemaParsedNumber
    {
        $parsedNumber = new SchemaParsedNumber();
        $parsedNumber->number = $number;
        $parsedNumber->masked = $masked;

        if (is_callable($creator)) {
            $creator($parsedNumber);
        }

        return $parsedNumber;
    }

    public static function makeSchemaConfNo(string $number, ?bool $isPrimary = false, ?string $description = null, ?callable $creator = null): SchemaConfNo
    {
        $confNo = new SchemaConfNo();
        $confNo->number = $number;
        $confNo->isPrimary = $isPrimary;
        $confNo->description = $description;

        if (is_callable($creator)) {
            $creator($confNo);
        }

        return $confNo;
    }

    public static function makeSchemaPhoneNumber(string $number, ?string $description = null, ?callable $creator = null): SchemaPhoneNumber
    {
        $phoneNumber = new SchemaPhoneNumber();
        $phoneNumber->number = $number;
        $phoneNumber->description = $description;

        if (is_callable($creator)) {
            $creator($phoneNumber);
        }

        return $phoneNumber;
    }

    /**
     * @param SchemaFee[]|null $fees
     */
    public static function makeSchemaPricingInfo(
        ?float $total = null,
        ?float $cost = null,
        ?float $discount = null,
        ?string $spentAwards = null,
        ?string $currencyCode = null,
        ?array $fees = null,
        ?callable $creator = null
    ): SchemaPricingInfo {
        $pricingInfo = new SchemaPricingInfo();
        $pricingInfo->total = $total;
        $pricingInfo->cost = $cost;
        $pricingInfo->discount = $discount;
        $pricingInfo->spentAwards = $spentAwards;
        $pricingInfo->currencyCode = $currencyCode;
        $pricingInfo->fees = $fees;

        if (is_callable($creator)) {
            $creator($pricingInfo);
        }

        return $pricingInfo;
    }

    public static function makeSchemaFee(string $name, float $charge, ?callable $creator = null): SchemaFee
    {
        $fee = new SchemaFee();
        $fee->name = $name;
        $fee->charge = $charge;

        if (is_callable($creator)) {
            $creator($fee);
        }

        return $fee;
    }

    public static function makeSchemaAddress(
        string $text,
        ?string $addressLine = null,
        ?string $city = null,
        ?string $stateName = null,
        ?string $countryName = null,
        ?string $countryCode = null,
        ?string $postalCode = null,
        ?float $lat = null,
        ?float $lng = null,
        ?int $timezone = null,
        ?string $timezoneId = null,
        ?callable $creator = null
    ): SchemaAddress {
        $address = new SchemaAddress();
        $address->text = $text;
        $address->addressLine = $addressLine;
        $address->city = $city;
        $address->stateName = $stateName;
        $address->countryName = $countryName;
        $address->countryCode = $countryCode;
        $address->postalCode = $postalCode;
        $address->lat = $lat;
        $address->lng = $lng;
        $address->timezone = $timezone;
        $address->timezoneId = $timezoneId;

        if (is_callable($creator)) {
            $creator($address);
        }

        return $address;
    }

    public static function makeSchemaPerson(
        string $name,
        bool $full = false,
        ?string $type = null,
        ?callable $creator = null
    ): SchemaPerson {
        $person = new SchemaPerson();
        $person->name = $name;
        $person->full = $full;
        $person->type = $type;

        if (is_callable($creator)) {
            $creator($person);
        }

        return $person;
    }

    public static function makeSchemaCar(
        ?string $type = null,
        ?string $model = null,
        ?string $imageUrl = null,
        ?callable $creator = null
    ): SchemaCar {
        $car = new SchemaCar();
        $car->type = $type;
        $car->model = $model;
        $car->imageUrl = $imageUrl;

        if (is_callable($creator)) {
            $creator($car);
        }

        return $car;
    }

    public static function makeSchemaTransportLocation(
        string $name,
        \DateTime $localDateTime,
        SchemaAddress $address,
        ?string $stationCode = null,
        ?callable $creator = null
    ): SchemaTransportLocation {
        $transportLocation = new SchemaTransportLocation();
        $transportLocation->name = $name;
        $transportLocation->localDateTime = $localDateTime->format('Y-m-d\TH:i:s');
        $transportLocation->address = $address;
        $transportLocation->stationCode = $stationCode;

        if (is_callable($creator)) {
            $creator($transportLocation);
        }

        return $transportLocation;
    }

    public static function makeSchemaTransferLocation(
        string $name,
        \DateTime $localDateTime,
        SchemaAddress $address,
        ?string $airportCode = null,
        ?callable $creator = null
    ): SchemaTransferLocation {
        $transferLocation = new SchemaTransferLocation();
        $transferLocation->name = $name;
        $transferLocation->localDateTime = $localDateTime->format('Y-m-d\TH:i:s');
        $transferLocation->address = $address;
        $transferLocation->airportCode = $airportCode;

        if (is_callable($creator)) {
            $creator($transferLocation);
        }

        return $transferLocation;
    }

    public static function makeSchemaVehicle(
        ?string $type = null,
        ?string $model = null,
        ?callable $creator = null
    ): SchemaVehicle {
        $vehicle = new SchemaVehicle();
        $vehicle->type = $type;
        $vehicle->model = $model;

        if (is_callable($creator)) {
            $creator($vehicle);
        }

        return $vehicle;
    }

    /**
     * @param SchemaBusSegment[] $segments
     * @param SchemaPerson[]|null $travelers
     * @param SchemaConfNo[]|null $confirmationNumbers
     * @param SchemaParsedNumber[]|null $ticketNumbers
     */
    public static function makeSchemaBus(
        array $segments,
        ?array $travelers = null,
        ?array $confirmationNumbers = null,
        ?array $ticketNumbers = null,
        ?SchemaProviderInfo $providerInfo = null,
        ?SchemaTravelAgency $travelAgency = null,
        ?SchemaPricingInfo $pricingInfo = null,
        ?string $status = null,
        ?\DateTime $reservationDate = null,
        ?string $cancellationPolicy = null,
        ?string $notes = null,
        ?bool $cancelled = null,
        ?callable $creator = null
    ): SchemaBus {
        $schemaItinerary = new SchemaBus();
        $schemaItinerary->segments = $segments;
        $schemaItinerary->travelers = $travelers;
        $schemaItinerary->confirmationNumbers = $confirmationNumbers;
        $schemaItinerary->ticketNumbers = $ticketNumbers;
        $schemaItinerary->providerInfo = $providerInfo;
        $schemaItinerary->travelAgency = $travelAgency;
        $schemaItinerary->pricingInfo = $pricingInfo;
        $schemaItinerary->status = $status;
        $schemaItinerary->reservationDate = $reservationDate ? $reservationDate->format('Y-m-dTH:i:s') : null;
        $schemaItinerary->cancellationPolicy = $cancellationPolicy;
        $schemaItinerary->notes = $notes;
        $schemaItinerary->cancelled = $cancelled;

        if (is_callable($creator)) {
            $creator($schemaItinerary);
        }

        return $schemaItinerary;
    }

    public static function makeSchemaVehicleExt(
        ?string $type = null,
        ?string $model = null,
        ?string $length = null,
        ?string $width = null,
        ?string $height = null,
        ?callable $creator = null
    ): SchemaVehicleExt {
        $vehicle = new SchemaVehicleExt();
        $vehicle->type = $type;
        $vehicle->model = $model;
        $vehicle->length = $length;
        $vehicle->width = $width;
        $vehicle->height = $height;

        if (is_callable($creator)) {
            $creator($vehicle);
        }

        return $vehicle;
    }

    /**
     * @param string[]|null $seats
     */
    public static function makeSchemaBusSegment(
        SchemaTransportLocation $departure,
        SchemaTransportLocation $arrival,
        ?string $scheduleNumber = null,
        ?SchemaVehicle $busInfo = null,
        ?array $seats = null,
        ?string $traveledMiles = null,
        ?string $cabin = null,
        ?string $bookingCode = null,
        ?string $duration = null,
        ?string $meal = null,
        ?bool $smoking = null,
        ?int $stops = null,
        ?callable $creator = null
    ): SchemaBusSegment {
        $busSegment = new SchemaBusSegment();
        $busSegment->departure = $departure;
        $busSegment->arrival = $arrival;
        $busSegment->scheduleNumber = $scheduleNumber;
        $busSegment->busInfo = $busInfo;
        $busSegment->seats = $seats;
        $busSegment->traveledMiles = $traveledMiles;
        $busSegment->cabin = $cabin;
        $busSegment->bookingCode = $bookingCode;
        $busSegment->duration = $duration;
        $busSegment->meal = $meal;
        $busSegment->smoking = $smoking;
        $busSegment->stops = $stops;

        if (is_callable($creator)) {
            $creator($busSegment);
        }

        return $busSegment;
    }

    /**
     * @param SchemaCruiseSegment[] $segments
     * @param SchemaPerson[]|null $travelers
     * @param SchemaConfNo[]|null $confirmationNumbers
     */
    public static function makeSchemaCruise(
        array $segments,
        ?SchemaCruiseDetails $cruiseDetails = null,
        ?array $travelers = null,
        ?array $confirmationNumbers = null,
        ?SchemaProviderInfo $providerInfo = null,
        ?SchemaTravelAgency $travelAgency = null,
        ?SchemaPricingInfo $pricingInfo = null,
        ?string $status = null,
        ?\DateTime $reservationDate = null,
        ?string $cancellationPolicy = null,
        ?string $notes = null,
        ?bool $cancelled = null,
        ?callable $creator = null
    ): SchemaCruise {
        $schemaItinerary = new SchemaCruise();
        $schemaItinerary->segments = $segments;
        $schemaItinerary->cruiseDetails = $cruiseDetails;
        $schemaItinerary->travelers = $travelers;
        $schemaItinerary->confirmationNumbers = $confirmationNumbers;
        $schemaItinerary->providerInfo = $providerInfo;
        $schemaItinerary->travelAgency = $travelAgency;
        $schemaItinerary->pricingInfo = $pricingInfo;
        $schemaItinerary->status = $status;
        $schemaItinerary->reservationDate = $reservationDate ? $reservationDate->format('Y-m-dTH:i:s') : null;
        $schemaItinerary->cancellationPolicy = $cancellationPolicy;
        $schemaItinerary->notes = $notes;
        $schemaItinerary->cancelled = $cancelled;

        if (is_callable($creator)) {
            $creator($schemaItinerary);
        }

        return $schemaItinerary;
    }

    public static function makeSchemaCruiseDetails(
        ?string $description = null,
        ?string $class = null,
        ?string $deck = null,
        ?string $room = null,
        ?string $ship = null,
        ?string $shipCode = null,
        ?string $voyageNumber = null,
        ?callable $creator = null
    ): SchemaCruiseDetails {
        $cruiseDetails = new SchemaCruiseDetails();
        $cruiseDetails->description = $description;
        $cruiseDetails->class = $class;
        $cruiseDetails->deck = $deck;
        $cruiseDetails->room = $room;
        $cruiseDetails->ship = $ship;
        $cruiseDetails->shipCode = $shipCode;
        $cruiseDetails->voyageNumber = $voyageNumber;

        if (is_callable($creator)) {
            $creator($cruiseDetails);
        }

        return $cruiseDetails;
    }

    public static function makeSchemaCruiseSegment(
        SchemaTransportLocation $departure,
        SchemaTransportLocation $arrival,
        ?callable $creator = null
    ): SchemaCruiseSegment {
        $cruiseSegment = new SchemaCruiseSegment();
        $cruiseSegment->departure = $departure;
        $cruiseSegment->arrival = $arrival;

        if (is_callable($creator)) {
            $creator($cruiseSegment);
        }

        return $cruiseSegment;
    }

    /**
     * @param SchemaPerson[]|null $guests
     * @param string[]|null $seats
     * @param SchemaConfNo[]|null $confirmationNumbers
     */
    public static function makeSchemaEvent(
        SchemaAddress $address,
        int $eventType,
        string $eventName,
        \DateTime $startDateTime,
        ?\DateTime $endDateTime = null,
        ?array $guests = null,
        ?int $guestCount = null,
        ?array $seats = null,
        ?array $confirmationNumbers = null,
        ?string $phone = null,
        ?string $fax = null,
        ?SchemaProviderInfo $providerInfo = null,
        ?SchemaTravelAgency $travelAgency = null,
        ?SchemaPricingInfo $pricingInfo = null,
        ?string $status = null,
        ?\DateTime $reservationDate = null,
        ?string $cancellationPolicy = null,
        ?string $notes = null,
        ?bool $cancelled = null,
        ?callable $creator = null
    ): SchemaEvent {
        $schemaItinerary = new SchemaEvent();
        $schemaItinerary->address = $address;
        $schemaItinerary->eventType = $eventType;
        $schemaItinerary->eventName = $eventName;
        $schemaItinerary->startDateTime = $startDateTime->format('Y-m-dTH:i:s');
        $schemaItinerary->endDateTime = $endDateTime ? $endDateTime->format('Y-m-dTH:i:s') : null;
        $schemaItinerary->guests = $guests;
        $schemaItinerary->guestCount = $guestCount;
        $schemaItinerary->seats = $seats;
        $schemaItinerary->confirmationNumbers = $confirmationNumbers;
        $schemaItinerary->phone = $phone;
        $schemaItinerary->fax = $fax;
        $schemaItinerary->providerInfo = $providerInfo;
        $schemaItinerary->travelAgency = $travelAgency;
        $schemaItinerary->pricingInfo = $pricingInfo;
        $schemaItinerary->status = $status;
        $schemaItinerary->reservationDate = $reservationDate ? $reservationDate->format('Y-m-dTH:i:s') : null;
        $schemaItinerary->cancellationPolicy = $cancellationPolicy;
        $schemaItinerary->notes = $notes;
        $schemaItinerary->cancelled = $cancelled;

        if (is_callable($creator)) {
            $creator($schemaItinerary);
        }

        return $schemaItinerary;
    }

    /**
     * @param string[]|null $accommodations
     * @param SchemaVehicleExt[]|null $vehicles
     * @param SchemaVehicleExt[]|null $trailers
     */
    public static function makeSchemaFerrySegment(
        SchemaTransportLocation $departure,
        SchemaTransportLocation $arrival,
        ?array $accommodations = null,
        ?string $carrier = null,
        ?string $vessel = null,
        ?string $traveledMiles = null,
        ?string $duration = null,
        ?string $meal = null,
        ?string $cabin = null,
        ?bool $smoking = null,
        ?int $adultsCount = null,
        ?int $kidsCount = null,
        ?string $pets = null,
        ?array $vehicles = null,
        ?array $trailers = null,
        ?callable $creator = null
    ): SchemaFerrySegment {
        $ferrySegment = new SchemaFerrySegment();
        $ferrySegment->departure = $departure;
        $ferrySegment->arrival = $arrival;
        $ferrySegment->accommodations = $accommodations;
        $ferrySegment->carrier = $carrier;
        $ferrySegment->vessel = $vessel;
        $ferrySegment->traveledMiles = $traveledMiles;
        $ferrySegment->duration = $duration;
        $ferrySegment->meal = $meal;
        $ferrySegment->cabin = $cabin;
        $ferrySegment->smoking = $smoking;
        $ferrySegment->adultsCount = $adultsCount;
        $ferrySegment->kidsCount = $kidsCount;
        $ferrySegment->pets = $pets;
        $ferrySegment->vehicles = $vehicles;
        $ferrySegment->trailers = $trailers;

        if (is_callable($creator)) {
            $creator($ferrySegment);
        }

        return $ferrySegment;
    }

    /**
     * @param SchemaFerrySegment[] $segments
     * @param SchemaPerson[]|null $travelers
     * @param SchemaConfNo[]|null $confirmationNumbers
     * @param SchemaParsedNumber[]|null $ticketNumbers
     */
    public static function makeSchemaFerry(
        array $segments,
        ?array $travelers = null,
        ?array $confirmationNumbers = null,
        ?array $ticketNumbers = null,
        ?SchemaProviderInfo $providerInfo = null,
        ?SchemaTravelAgency $travelAgency = null,
        ?SchemaPricingInfo $pricingInfo = null,
        ?string $status = null,
        ?\DateTime $reservationDate = null,
        ?string $cancellationPolicy = null,
        ?string $notes = null,
        ?bool $cancelled = null,
        ?callable $creator = null
    ): SchemaFerry {
        $schemaItinerary = new SchemaFerry();
        $schemaItinerary->segments = $segments;
        $schemaItinerary->travelers = $travelers;
        $schemaItinerary->confirmationNumbers = $confirmationNumbers;
        $schemaItinerary->ticketNumbers = $ticketNumbers;
        $schemaItinerary->providerInfo = $providerInfo;
        $schemaItinerary->travelAgency = $travelAgency;
        $schemaItinerary->pricingInfo = $pricingInfo;
        $schemaItinerary->status = $status;
        $schemaItinerary->reservationDate = $reservationDate ? $reservationDate->format('Y-m-dTH:i:s') : null;
        $schemaItinerary->cancellationPolicy = $cancellationPolicy;
        $schemaItinerary->notes = $notes;
        $schemaItinerary->cancelled = $cancelled;

        if (is_callable($creator)) {
            $creator($schemaItinerary);
        }

        return $schemaItinerary;
    }

    /**
     * @param string[]|null $seats
     */
    public static function makeSchemaFlightSegment(
        SchemaTripLocation $departure,
        SchemaTripLocation $arrival,
        SchemaMarketingCarrier $marketingCarrier,
        ?SchemaOperatingCarrier $operatingCarrier = null,
        ?SchemaAirline $wetleaseCarrier = null,
        ?array $seats = null,
        ?SchemaAircraft $aircraft = null,
        ?string $traveledMiles = null,
        ?string $cabin = null,
        ?string $bookingCode = null,
        ?string $duration = null,
        ?string $meal = null,
        ?bool $smoking = null,
        ?string $status = null,
        ?bool $cancelled = null,
        ?int $stops = null,
        ?callable $creator = null
    ): SchemaFlightSegment {
        $flightSegment = new SchemaFlightSegment();
        $flightSegment->departure = $departure;
        $flightSegment->arrival = $arrival;
        $flightSegment->marketingCarrier = $marketingCarrier;
        $flightSegment->operatingCarrier = $operatingCarrier;
        $flightSegment->wetleaseCarrier = $wetleaseCarrier;
        $flightSegment->seats = $seats;
        $flightSegment->aircraft = $aircraft;
        $flightSegment->traveledMiles = $traveledMiles;
        $flightSegment->cabin = $cabin;
        $flightSegment->bookingCode = $bookingCode;
        $flightSegment->duration = $duration;
        $flightSegment->meal = $meal;
        $flightSegment->smoking = $smoking;
        $flightSegment->status = $status;
        $flightSegment->cancelled = $cancelled;
        $flightSegment->stops = $stops;

        if (is_callable($creator)) {
            $creator($flightSegment);
        }

        return $flightSegment;
    }

    public static function makeSchemaTripLocation(
        string $name,
        \DateTime $localDateTime,
        SchemaAddress $address,
        ?string $airportCode = null,
        ?string $terminal = null,
        ?callable $creator = null
    ): SchemaTripLocation {
        $tripLocation = new SchemaTripLocation();
        $tripLocation->name = $name;
        $tripLocation->localDateTime = $localDateTime->format('Y-m-d\TH:i:s');
        $tripLocation->address = $address;
        $tripLocation->airportCode = $airportCode;
        $tripLocation->terminal = $terminal;

        if (is_callable($creator)) {
            $creator($tripLocation);
        }

        return $tripLocation;
    }

    /**
     * @param SchemaPhoneNumber[]|null $phoneNumbers
     */
    public static function makeSchemaMarketingCarrier(
        SchemaAirline $airline,
        string $flightNumber,
        ?string $confirmationNumber = null,
        ?array $phoneNumbers = null,
        ?bool $isCodeshare = false,
        ?callable $creator = null
    ): SchemaMarketingCarrier {
        $marketingCarrier = new SchemaMarketingCarrier();
        $marketingCarrier->airline = $airline;
        $marketingCarrier->flightNumber = $flightNumber;
        $marketingCarrier->confirmationNumber = $confirmationNumber;
        $marketingCarrier->phoneNumbers = $phoneNumbers;
        $marketingCarrier->isCodeshare = $isCodeshare;

        if (is_callable($creator)) {
            $creator($marketingCarrier);
        }

        return $marketingCarrier;
    }

    /**
     * @param SchemaPhoneNumber[]|null $phoneNumbers
     */
    public static function makeSchemaOperatingCarrier(
        ?SchemaAirline $airline = null,
        ?string $flightNumber = null,
        ?string $confirmationNumber = null,
        ?array $phoneNumbers = null,
        ?callable $creator = null
    ): SchemaOperatingCarrier {
        $operatingCarrier = new SchemaOperatingCarrier();
        $operatingCarrier->airline = $airline;
        $operatingCarrier->flightNumber = $flightNumber;
        $operatingCarrier->confirmationNumber = $confirmationNumber;
        $operatingCarrier->phoneNumbers = $phoneNumbers;

        if (is_callable($creator)) {
            $creator($operatingCarrier);
        }

        return $operatingCarrier;
    }

    /**
     * @param SchemaPhoneNumber[]|null $phoneNumbers
     * @param SchemaParsedNumber[]|null $ticketNumbers
     */
    public static function makeSchemaIssuingCarrier(
        ?SchemaAirline $airline = null,
        ?string $confirmationNumber = null,
        ?array $phoneNumbers = null,
        ?array $ticketNumbers = null,
        ?callable $creator = null
    ): SchemaIssuingCarrier {
        $issuingCarrier = new SchemaIssuingCarrier();
        $issuingCarrier->airline = $airline;
        $issuingCarrier->confirmationNumber = $confirmationNumber;
        $issuingCarrier->phoneNumbers = $phoneNumbers;
        $issuingCarrier->ticketNumbers = $ticketNumbers;

        if (is_callable($creator)) {
            $creator($issuingCarrier);
        }

        return $issuingCarrier;
    }

    public static function makeSchemaAirline(
        string $name,
        ?string $iata = null,
        ?string $icao = null,
        ?callable $creator = null
    ): SchemaAirline {
        $airline = new SchemaAirline();
        $airline->name = $name;
        $airline->iata = $iata;
        $airline->icao = $icao;

        if (is_callable($creator)) {
            $creator($airline);
        }

        return $airline;
    }

    public static function makeSchemaAircraft(
        string $name,
        ?string $iataCode = null,
        ?bool $turboProp = null,
        ?bool $jet = null,
        ?bool $wideBody = null,
        ?bool $regional = null,
        ?string $registrationNumber = null,
        ?callable $creator = null
    ): SchemaAircraft {
        $aircraft = new SchemaAircraft();
        $aircraft->name = $name;
        $aircraft->iataCode = $iataCode;
        $aircraft->turboProp = $turboProp;
        $aircraft->jet = $jet;
        $aircraft->wideBody = $wideBody;
        $aircraft->regional = $regional;
        $aircraft->registrationNumber = $registrationNumber;

        if (is_callable($creator)) {
            $creator($aircraft);
        }

        return $aircraft;
    }

    /**
     * @param SchemaFlightSegment[] $segments
     * @param SchemaPerson[]|null $travelers
     */
    public static function makeSchemaFlight(
        array $segments,
        ?array $travelers = null,
        ?SchemaIssuingCarrier $issuingCarrier = null,
        ?SchemaProviderInfo $providerInfo = null,
        ?SchemaTravelAgency $travelAgency = null,
        ?SchemaPricingInfo $pricingInfo = null,
        ?string $status = null,
        ?\DateTime $reservationDate = null,
        ?string $cancellationPolicy = null,
        ?string $notes = null,
        ?bool $cancelled = null,
        ?callable $creator = null
    ): SchemaFlight {
        $schemaItinerary = new SchemaFlight();
        $schemaItinerary->segments = $segments;
        $schemaItinerary->travelers = $travelers;
        $schemaItinerary->issuingCarrier = $issuingCarrier;
        $schemaItinerary->providerInfo = $providerInfo;
        $schemaItinerary->travelAgency = $travelAgency;
        $schemaItinerary->pricingInfo = $pricingInfo;
        $schemaItinerary->status = $status;
        $schemaItinerary->reservationDate = $reservationDate ? $reservationDate->format('Y-m-dTH:i:s') : null;
        $schemaItinerary->cancellationPolicy = $cancellationPolicy;
        $schemaItinerary->notes = $notes;
        $schemaItinerary->cancelled = $cancelled;

        if (is_callable($creator)) {
            $creator($schemaItinerary);
        }

        return $schemaItinerary;
    }

    public static function makeSchemaRoom(
        ?string $type = null,
        ?string $description = null,
        ?string $rateType = null,
        ?string $rate = null,
        ?callable $creator = null
    ): SchemaRoom {
        $room = new SchemaRoom();
        $room->type = $type;
        $room->description = $description;
        $room->rateType = $rateType;
        $room->rate = $rate;

        if (is_callable($creator)) {
            $creator($room);
        }

        return $room;
    }

    /**
     * @param SchemaPerson[]|null $guests
     * @param SchemaConfNo[]|null $confirmationNumbers
     * @param SchemaRoom[]|null $rooms
     */
    public static function makeSchemaReservation(
        string $hotelName,
        SchemaAddress $address,
        \DateTime $checkInDate,
        \DateTime $checkOutDate,
        ?array $guests = null,
        ?int $guestCount = null,
        ?int $kidsCount = null,
        ?array $confirmationNumbers = null,
        ?array $rooms = null,
        ?int $roomsCount = null,
        ?string $phone = null,
        ?string $fax = null,
        ?int $freeNights = null,
        ?string $chainName = null,
        ?SchemaProviderInfo $providerInfo = null,
        ?SchemaTravelAgency $travelAgency = null,
        ?SchemaPricingInfo $pricingInfo = null,
        ?string $status = null,
        ?\DateTime $reservationDate = null,
        ?string $cancellationPolicy = null,
        ?string $notes = null,
        ?bool $cancelled = null,
        ?string $cancellationNumber = null,
        ?\DateTime $cancellationDeadline = null,
        ?bool $isNonRefundable = null,
        ?callable $creator = null
    ): SchemaReservation {
        $schemaItinerary = new SchemaReservation();
        $schemaItinerary->hotelName = $hotelName;
        $schemaItinerary->address = $address;
        $schemaItinerary->checkInDate = $checkInDate->format('Y-m-dTH:i:s');
        $schemaItinerary->checkOutDate = $checkOutDate->format('Y-m-dTH:i:s');
        $schemaItinerary->guests = $guests;
        $schemaItinerary->guestCount = $guestCount;
        $schemaItinerary->kidsCount = $kidsCount;
        $schemaItinerary->confirmationNumbers = $confirmationNumbers;
        $schemaItinerary->rooms = $rooms;
        $schemaItinerary->roomsCount = $roomsCount;
        $schemaItinerary->phone = $phone;
        $schemaItinerary->fax = $fax;
        $schemaItinerary->freeNights = $freeNights;
        $schemaItinerary->chainName = $chainName;
        $schemaItinerary->providerInfo = $providerInfo;
        $schemaItinerary->travelAgency = $travelAgency;
        $schemaItinerary->pricingInfo = $pricingInfo;
        $schemaItinerary->status = $status;
        $schemaItinerary->reservationDate = $reservationDate ? $reservationDate->format('Y-m-dTH:i:s') : null;
        $schemaItinerary->cancellationPolicy = $cancellationPolicy;
        $schemaItinerary->notes = $notes;
        $schemaItinerary->cancelled = $cancelled;
        $schemaItinerary->cancellationNumber = $cancellationNumber;
        $schemaItinerary->cancellationDeadline = $cancellationDeadline ? $cancellationDeadline->format('Y-m-dTH:i:s') : null;
        $schemaItinerary->isNonRefundable = $isNonRefundable;

        if (is_callable($creator)) {
            $creator($schemaItinerary);
        }

        return $schemaItinerary;
    }

    /**
     * @param SchemaConfNo[]|null $confirmationNumbers
     */
    public static function makeSchemaParking(
        \DateTime $startDateTime,
        \DateTime $endDateTime,
        ?SchemaPerson $owner = null,
        ?array $confirmationNumbers = null,
        ?SchemaAddress $address = null,
        ?string $locationName = null,
        ?string $spotNumber = null,
        ?string $licensePlate = null,
        ?string $phone = null,
        ?string $openingHours = null,
        ?string $carDescription = null,
        ?string $rateType = null,
        ?SchemaProviderInfo $providerInfo = null,
        ?SchemaTravelAgency $travelAgency = null,
        ?SchemaPricingInfo $pricingInfo = null,
        ?string $status = null,
        ?\DateTime $reservationDate = null,
        ?string $cancellationPolicy = null,
        ?string $notes = null,
        ?bool $cancelled = null,
        ?callable $creator = null
    ): SchemaParking {
        $schemaItinerary = new SchemaParking();
        $schemaItinerary->startDateTime = $startDateTime->format('Y-m-dTH:i:s');
        $schemaItinerary->endDateTime = $endDateTime->format('Y-m-dTH:i:s');
        $schemaItinerary->owner = $owner;
        $schemaItinerary->confirmationNumbers = $confirmationNumbers;
        $schemaItinerary->address = $address;
        $schemaItinerary->locationName = $locationName;
        $schemaItinerary->spotNumber = $spotNumber;
        $schemaItinerary->licensePlate = $licensePlate;
        $schemaItinerary->phone = $phone;
        $schemaItinerary->openingHours = $openingHours;
        $schemaItinerary->carDescription = $carDescription;
        $schemaItinerary->rateType = $rateType;
        $schemaItinerary->providerInfo = $providerInfo;
        $schemaItinerary->travelAgency = $travelAgency;
        $schemaItinerary->pricingInfo = $pricingInfo;
        $schemaItinerary->status = $status;
        $schemaItinerary->reservationDate = $reservationDate ? $reservationDate->format('Y-m-dTH:i:s') : null;
        $schemaItinerary->cancellationPolicy = $cancellationPolicy;
        $schemaItinerary->notes = $notes;
        $schemaItinerary->cancelled = $cancelled;

        if (is_callable($creator)) {
            $creator($schemaItinerary);
        }

        return $schemaItinerary;
    }

    public static function makeSchemaCarRentalLocation(
        SchemaAddress $address,
        \DateTime $dateTime,
        ?string $openingHours = null,
        ?string $phone = null,
        ?string $fax = null,
        ?callable $creator = null
    ): SchemaCarRentalLocation {
        $carRentalLocation = new SchemaCarRentalLocation();
        $carRentalLocation->address = $address;
        $carRentalLocation->localDateTime = $dateTime->format('Y-m-d\TH:i:s');
        $carRentalLocation->openingHours = $openingHours;
        $carRentalLocation->phone = $phone;
        $carRentalLocation->fax = $fax;

        if (is_callable($creator)) {
            $creator($carRentalLocation);
        }

        return $carRentalLocation;
    }

    public static function makeSchemaCarRentalDiscount(string $name, string $code, ?callable $creator = null): SchemaCarRentalDiscount
    {
        $discount = new SchemaCarRentalDiscount();
        $discount->name = $name;
        $discount->code = $code;

        if (is_callable($creator)) {
            $creator($discount);
        }

        return $discount;
    }

    public static function makeSchemaPricedEquipment(string $name, float $charge, ?callable $creator = null): SchemaFee
    {
        $pricedEquipment = new SchemaFee();
        $pricedEquipment->name = $name;
        $pricedEquipment->charge = $charge;

        if (is_callable($creator)) {
            $creator($pricedEquipment);
        }

        return $pricedEquipment;
    }

    /**
     * @param SchemaConfNo[]|null $confirmationNumbers
     * @param SchemaFee[]|null $pricedEquipment
     * @param SchemaCarRentalDiscount[]|null $discounts
     */
    public static function makeSchemaRental(
        SchemaCarRentalLocation $pickup,
        SchemaCarRentalLocation $dropoff,
        ?SchemaPerson $driver = null,
        ?array $confirmationNumbers = null,
        ?SchemaCar $car = null,
        ?array $pricedEquipment = null,
        ?string $rentalCompany = null,
        ?array $discounts = null,
        ?SchemaProviderInfo $providerInfo = null,
        ?SchemaTravelAgency $travelAgency = null,
        ?SchemaPricingInfo $pricingInfo = null,
        ?string $status = null,
        ?\DateTime $reservationDate = null,
        ?string $cancellationPolicy = null,
        ?string $notes = null,
        ?bool $cancelled = null,
        ?callable $creator = null
    ): SchemaRental {
        $schemaItinerary = new SchemaRental();
        $schemaItinerary->pickup = $pickup;
        $schemaItinerary->dropoff = $dropoff;
        $schemaItinerary->driver = $driver;
        $schemaItinerary->confirmationNumbers = $confirmationNumbers;
        $schemaItinerary->car = $car;
        $schemaItinerary->pricedEquipment = $pricedEquipment;
        $schemaItinerary->rentalCompany = $rentalCompany;
        $schemaItinerary->discounts = $discounts;
        $schemaItinerary->providerInfo = $providerInfo;
        $schemaItinerary->travelAgency = $travelAgency;
        $schemaItinerary->pricingInfo = $pricingInfo;
        $schemaItinerary->status = $status;
        $schemaItinerary->reservationDate = $reservationDate ? $reservationDate->format('Y-m-dTH:i:s') : null;
        $schemaItinerary->cancellationPolicy = $cancellationPolicy;
        $schemaItinerary->notes = $notes;
        $schemaItinerary->cancelled = $cancelled;

        if (is_callable($creator)) {
            $creator($schemaItinerary);
        }

        return $schemaItinerary;
    }

    /**
     * @param string[]|null $seats
     */
    public static function makeSchemaTrainSegment(
        SchemaTransportLocation $departure,
        SchemaTransportLocation $arrival,
        ?string $scheduleNumber = null,
        ?string $serviceName = null,
        ?SchemaVehicle $trainInfo = null,
        ?string $car = null,
        ?array $seats = null,
        ?string $traveledMiles = null,
        ?string $cabin = null,
        ?string $bookingCode = null,
        ?string $duration = null,
        ?string $meal = null,
        ?bool $smoking = null,
        ?int $stops = null,
        ?callable $creator = null
    ): SchemaTrainSegment {
        $trainSegment = new SchemaTrainSegment();
        $trainSegment->departure = $departure;
        $trainSegment->arrival = $arrival;
        $trainSegment->scheduleNumber = $scheduleNumber;
        $trainSegment->serviceName = $serviceName;
        $trainSegment->trainInfo = $trainInfo;
        $trainSegment->car = $car;
        $trainSegment->seats = $seats;
        $trainSegment->traveledMiles = $traveledMiles;
        $trainSegment->cabin = $cabin;
        $trainSegment->bookingCode = $bookingCode;
        $trainSegment->duration = $duration;
        $trainSegment->meal = $meal;
        $trainSegment->smoking = $smoking;
        $trainSegment->stops = $stops;

        if (is_callable($creator)) {
            $creator($trainSegment);
        }

        return $trainSegment;
    }

    /**
     * @param SchemaTrainSegment[] $segments
     * @param SchemaPerson[]|null $travelers
     * @param SchemaConfNo[]|null $confirmationNumbers
     * @param SchemaParsedNumber[]|null $ticketNumbers
     */
    public static function makeSchemaTrain(
        array $segments,
        ?array $travelers = null,
        ?array $confirmationNumbers = null,
        ?array $ticketNumbers = null,
        ?SchemaProviderInfo $providerInfo = null,
        ?SchemaTravelAgency $travelAgency = null,
        ?SchemaPricingInfo $pricingInfo = null,
        ?string $status = null,
        ?\DateTime $reservationDate = null,
        ?string $cancellationPolicy = null,
        ?string $notes = null,
        ?bool $cancelled = null,
        ?callable $creator = null
    ): SchemaTrain {
        $schemaItinerary = new SchemaTrain();
        $schemaItinerary->segments = $segments;
        $schemaItinerary->travelers = $travelers;
        $schemaItinerary->confirmationNumbers = $confirmationNumbers;
        $schemaItinerary->ticketNumbers = $ticketNumbers;
        $schemaItinerary->providerInfo = $providerInfo;
        $schemaItinerary->travelAgency = $travelAgency;
        $schemaItinerary->pricingInfo = $pricingInfo;
        $schemaItinerary->status = $status;
        $schemaItinerary->reservationDate = $reservationDate ? $reservationDate->format('Y-m-dTH:i:s') : null;
        $schemaItinerary->cancellationPolicy = $cancellationPolicy;
        $schemaItinerary->notes = $notes;
        $schemaItinerary->cancelled = $cancelled;

        if (is_callable($creator)) {
            $creator($schemaItinerary);
        }

        return $schemaItinerary;
    }

    public static function makeSchemaTransferSegment(
        SchemaTransferLocation $departure,
        SchemaTransferLocation $arrival,
        ?SchemaCar $vehicleInfo = null,
        ?int $adults = null,
        ?int $kids = null,
        ?string $traveledMiles = null,
        ?string $duration = null,
        ?callable $creator = null
    ): SchemaTransferSegment {
        $transferSegment = new SchemaTransferSegment();
        $transferSegment->departure = $departure;
        $transferSegment->arrival = $arrival;
        $transferSegment->vehicleInfo = $vehicleInfo;
        $transferSegment->adults = $adults;
        $transferSegment->kids = $kids;
        $transferSegment->traveledMiles = $traveledMiles;
        $transferSegment->duration = $duration;

        if (is_callable($creator)) {
            $creator($transferSegment);
        }

        return $transferSegment;
    }

    /**
     * @param SchemaTransferSegment[] $segments
     * @param SchemaPerson[]|null $travelers
     * @param SchemaConfNo[]|null $confirmationNumbers
     */
    public static function makeSchemaTransfer(
        array $segments,
        ?array $travelers = null,
        ?array $confirmationNumbers = null,
        ?SchemaProviderInfo $providerInfo = null,
        ?SchemaTravelAgency $travelAgency = null,
        ?SchemaPricingInfo $pricingInfo = null,
        ?string $status = null,
        ?\DateTime $reservationDate = null,
        ?string $cancellationPolicy = null,
        ?string $notes = null,
        ?bool $cancelled = null,
        ?callable $creator = null
    ): SchemaTransfer {
        $schemaItinerary = new SchemaTransfer();
        $schemaItinerary->segments = $segments;
        $schemaItinerary->travelers = $travelers;
        $schemaItinerary->confirmationNumbers = $confirmationNumbers;
        $schemaItinerary->providerInfo = $providerInfo;
        $schemaItinerary->travelAgency = $travelAgency;
        $schemaItinerary->pricingInfo = $pricingInfo;
        $schemaItinerary->status = $status;
        $schemaItinerary->reservationDate = $reservationDate ? $reservationDate->format('Y-m-dTH:i:s') : null;
        $schemaItinerary->cancellationPolicy = $cancellationPolicy;
        $schemaItinerary->notes = $notes;
        $schemaItinerary->cancelled = $cancelled;

        if (is_callable($creator)) {
            $creator($schemaItinerary);
        }

        return $schemaItinerary;
    }
}
