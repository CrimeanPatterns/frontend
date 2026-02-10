<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;

/**
 * @NoDI()
 */
class PropertiesList implements TranslationContainerInterface
{
    public const ACCOMMODATIONS = 'Accommodations';
    public const ACCOUNT_NUMBERS = 'AccountNumbers';
    public const ADDRESS = 'Address';
    public const ADULTS_COUNT = 'AdultsCount';
    public const AIRCRAFT = 'Aircraft';
    public const AIRLINE_NAME = 'AirlineName';
    public const ARRIVAL_ADDRESS = 'ArrAddress'; // todo: add formatter, add to PropertiesDB
    public const ARRIVAL_AIRPORT_CODE = 'ArrCode';
    public const ARRIVAL_DATE = 'ArrDate';
    public const ARRIVAL_GATE = 'ArrivalGate';
    public const ARRIVAL_NAME = 'ArrName';
    public const ARRIVAL_TERMINAL = 'ArrivalTerminal';
    public const BAGGAGE_CLAIM = 'BaggageClaim';
    public const BOOKING_CLASS = 'BookingClass';
    public const CANCELLATION_DEADLINE = 'CancellationDeadline';
    public const CANCELLATION_POLICY = 'CancellationPolicy';
    public const CAR_DESCRIPTION = 'CarDescription';
    public const CAR_IMAGE_URL = 'CarImageUrl';
    public const CAR_MODEL = 'CarModel';
    public const CAR_TYPE = 'CarType';
    public const CHECK_IN_DATE = 'CheckInDate';
    public const CHECK_OUT_DATE = 'CheckOutDate';
    public const COMMENT = 'Comment';
    public const CONFIRMATION_NUMBER = 'ConfirmationNumber';
    public const CONFIRMATION_NUMBERS = 'ConfirmationNumbers';
    public const COST = 'Cost';
    public const CRUISE_NAME = 'CruiseName';
    public const CURRENCY = 'Currency';
    public const DECK = 'Deck';
    public const DEPARTURE_ADDRESS = 'DepAddress'; // todo: add formatter, add to PropertiesDB
    public const DEPARTURE_AIRPORT_CODE = 'DepCode';
    public const DEPARTURE_DATE = 'DepDate';
    public const DEPARTURE_GATE = 'DepartureGate';
    public const DEPARTURE_NAME = 'DepName';
    public const DEPARTURE_TERMINAL = 'DepartureTerminal';
    public const DINER_NAME = 'DinerName';
    public const DISCOUNT = 'Discount';
    public const DISCOUNT_DETAILS = 'DiscountDetails';
    public const DROP_OFF_DATE = 'DropOffDate';
    public const DROP_OFF_FAX = 'DropOffFax';
    public const DROP_OFF_HOURS = 'DropOffHours';
    public const DROP_OFF_LOCATION = 'DropOffLocation';
    public const DROP_OFF_PHONE = 'DropOffPhone';
    public const DURATION = 'Duration';
    public const EARNED_AWARDS = 'EarnedAwards';
    public const END_DATE = 'EndDate';
    public const EVENT_NAME = 'EventName';
    public const FARE_BASIS = 'ServiceClasses';
    public const FAX = 'Fax';
    public const FEES = 'Fees';
    public const FEES_LIST = 'FeesList';
    public const FLIGHT_CABIN_CLASS = 'CabinClass';
    public const FLIGHT_NUMBER = 'FlightNumber';
    public const FREE_NIGHTS = 'FreeNights';
    public const GUEST_COUNT = 'GuestCount';
    public const HOTEL_NAME = 'HotelName';
    public const IS_SMOKING = 'Smoking';
    public const KIDS_COUNT = 'KidsCount';
    public const LICENSE_PLATE = 'LicensePlate';
    public const LOCATION = 'Location';
    public const MEAL = 'Meal';
    public const NON_REFUNDABLE = 'NonRefundable';
    public const NOTES = 'Notes';
    public const FILES = 'Files';
    public const OPERATING_AIRLINE_NAME = 'OperatingAirlineName';
    public const PARKING_COMPANY = 'ParkingCompany';
    public const PETS = 'Pets';
    public const PHONE = 'Phone';
    public const PICK_UP_DATE = 'PickUpDate';
    public const PICK_UP_FAX = 'PickUpFax';
    public const PICK_UP_HOURS = 'PickUpHours';
    public const PICK_UP_LOCATION = 'PickUpLocation';
    public const PICK_UP_PHONE = 'PickUpPhone';
    public const PRICED_EQUIPMENT = 'PricedEquipment';
    public const RATE_TYPE = 'RateType';
    public const RENTAL_COMPANY = 'RentalCompany';
    public const RESERVATION_DATE = 'ReservationDate';
    public const RETRIEVE_FROM = 'RetrieveFrom';
    public const ROOM_COUNT = 'Rooms';
    public const ROOM_LONG_DESCRIPTION = 'RoomLongDescriptions';
    public const ROOM_RATE = 'RoomRate';
    public const ROOM_RATE_DESCRIPTION = 'RoomRateDescription';
    public const ROOM_SHORT_DESCRIPTION = 'RoomShortDescriptions';
    public const SEATS = 'Seats';
    public const SHIP_CABIN_CLASS = 'ShipCabinClass';
    public const SHIP_CABIN_NUMBER = 'CabinNumber';
    public const SHIP_CODE = 'ShipCode';
    public const SHIP_NAME = 'ShipName';
    public const SPENT_AWARDS = 'SpentAwards';
    public const SPOT_NUMBER = 'SpotNumber';
    public const START_DATE = 'StartDate';
    public const STATUS = 'Status';
    public const STOPS_COUNT = 'Stops';
    public const TAX = 'Tax'; // todo: add formatter, add to PropertiesDB
    public const TICKET_NUMBERS = 'TicketNumbers';
    public const TOTAL_CHARGE = 'Total';
    public const TRAIN_CAR_NUMBER = 'CarNumber';
    public const TRAIN_SERVICE_NAME = 'ServiceName';
    public const TRAVELED_MILES = 'TravelledMiles';
    public const TRAVELER_NAMES = 'TravelerNames';
    public const TRAVEL_AGENCY_ACCOUNT_NUMBERS = 'TravelAgencyAccountNumbers';
    public const TRAVEL_AGENCY_EARNED_AWARDS = 'TravelAgencyEarnedAwards';
    public const VEHICLES = 'Vehicles';
    public const VESSEL = 'Vessel';

    public static $tripPropertiesOrder = [
        self::CONFIRMATION_NUMBER,
        self::CONFIRMATION_NUMBERS,
        self::TICKET_NUMBERS,
        self::DEPARTURE_NAME,
        self::DEPARTURE_ADDRESS,
        self::DEPARTURE_DATE,
        self::DEPARTURE_TERMINAL,
        self::DEPARTURE_GATE,
        self::ARRIVAL_NAME,
        self::ARRIVAL_ADDRESS,
        self::ARRIVAL_DATE,
        self::ARRIVAL_TERMINAL,
        self::ARRIVAL_GATE,
        self::RETRIEVE_FROM,
        self::AIRLINE_NAME,
        self::FLIGHT_NUMBER,
        self::CRUISE_NAME,
        self::TRAIN_SERVICE_NAME,
        self::TRAIN_CAR_NUMBER,
        self::DECK,
        self::SHIP_CABIN_NUMBER,
        self::SHIP_CABIN_CLASS,
        self::SHIP_CODE,
        self::SHIP_NAME,
        self::TRAVELER_NAMES,
        self::RESERVATION_DATE,
        self::STATUS,
        self::BAGGAGE_CLAIM,
        self::BOOKING_CLASS,
        self::FLIGHT_CABIN_CLASS,
        self::MEAL,
        self::AIRCRAFT,
        self::DURATION,
        self::STOPS_COUNT,
        self::IS_SMOKING,
        self::SEATS,
        self::ADULTS_COUNT,
        self::KIDS_COUNT,
        self::ACCOUNT_NUMBERS,
        self::TRAVEL_AGENCY_ACCOUNT_NUMBERS,
        self::TRAVELED_MILES,
        self::ACCOMMODATIONS,
        self::VESSEL,
        self::PETS,
        self::VEHICLES,
        self::CURRENCY,
        self::COST,
        self::FEES,
        self::DISCOUNT,
        self::SPENT_AWARDS,
        self::EARNED_AWARDS,
        self::TRAVEL_AGENCY_EARNED_AWARDS,
        self::TOTAL_CHARGE,
        self::COMMENT,
    ];

    public static $rentalPropertiesOrder = [
        self::CONFIRMATION_NUMBER,
        self::CONFIRMATION_NUMBERS,
        self::PICK_UP_LOCATION,
        self::PICK_UP_DATE,
        self::PICK_UP_HOURS,
        self::PICK_UP_PHONE,
        self::PICK_UP_FAX,
        self::DROP_OFF_LOCATION,
        self::DROP_OFF_DATE,
        self::DROP_OFF_HOURS,
        self::DROP_OFF_PHONE,
        self::DROP_OFF_FAX,
        self::RETRIEVE_FROM,
        self::RENTAL_COMPANY,
        self::STATUS,
        self::TRAVELER_NAMES,
        self::CAR_TYPE,
        self::CAR_MODEL,
        self::CAR_IMAGE_URL,
        self::ACCOUNT_NUMBERS,
        self::TRAVEL_AGENCY_ACCOUNT_NUMBERS,
        self::RESERVATION_DATE,
        self::CURRENCY,
        self::COST,
        self::FEES,
        self::DISCOUNT,
        self::SPENT_AWARDS,
        self::EARNED_AWARDS,
        self::TRAVEL_AGENCY_EARNED_AWARDS,
        self::TOTAL_CHARGE,
        self::DISCOUNT_DETAILS,
        self::PRICED_EQUIPMENT,
        self::COMMENT,
    ];

    public static $reservationPropertiesOrder = [
        self::CONFIRMATION_NUMBER,
        self::HOTEL_NAME,
        self::CHECK_IN_DATE,
        self::CHECK_OUT_DATE,
        self::RETRIEVE_FROM,
        self::CONFIRMATION_NUMBERS,
        self::ADDRESS,
        self::STATUS,
        self::GUEST_COUNT,
        self::TRAVELER_NAMES,
        self::KIDS_COUNT,
        self::ROOM_COUNT,
        self::FREE_NIGHTS,
        self::ROOM_LONG_DESCRIPTION,
        self::ROOM_SHORT_DESCRIPTION,
        self::ROOM_RATE,
        self::ROOM_RATE_DESCRIPTION,
        self::ACCOUNT_NUMBERS,
        self::TRAVEL_AGENCY_ACCOUNT_NUMBERS,
        self::PHONE,
        self::FAX,
        self::CANCELLATION_POLICY,
        self::RESERVATION_DATE,
        self::CURRENCY,
        self::COST,
        self::FEES,
        self::DISCOUNT,
        self::SPENT_AWARDS,
        self::EARNED_AWARDS,
        self::TRAVEL_AGENCY_EARNED_AWARDS,
        self::TOTAL_CHARGE,
        self::NON_REFUNDABLE,
        self::COMMENT,
    ];

    public static $restaurantPropertiesOrder = [
        self::CONFIRMATION_NUMBER,
        self::CONFIRMATION_NUMBERS,
        self::START_DATE,
        self::END_DATE,
        self::EVENT_NAME,
        self::ADDRESS,
        self::PHONE,
        self::STATUS,
        self::RETRIEVE_FROM,
        self::GUEST_COUNT,
        self::TRAVELER_NAMES,
        self::DINER_NAME,
        self::ACCOUNT_NUMBERS,
        self::TRAVEL_AGENCY_ACCOUNT_NUMBERS,
        self::RESERVATION_DATE,
        self::CURRENCY,
        self::COST,
        self::FEES,
        self::DISCOUNT,
        self::SPENT_AWARDS,
        self::EARNED_AWARDS,
        self::TRAVEL_AGENCY_EARNED_AWARDS,
        self::TOTAL_CHARGE,
        self::COMMENT,
    ];

    public static $parkingPropertiesOrder = [
        self::CONFIRMATION_NUMBER,
        self::CONFIRMATION_NUMBERS,
        self::LOCATION,
        self::START_DATE,
        self::END_DATE,
        self::PHONE,
        self::LICENSE_PLATE,
        self::SPOT_NUMBER,
        self::RETRIEVE_FROM,
        self::PARKING_COMPANY,
        self::STATUS,
        self::TRAVELER_NAMES,
        self::CAR_DESCRIPTION,
        self::ACCOUNT_NUMBERS,
        self::TRAVEL_AGENCY_ACCOUNT_NUMBERS,
        self::RESERVATION_DATE,
        self::CURRENCY,
        self::COST,
        self::FEES,
        self::DISCOUNT,
        self::SPENT_AWARDS,
        self::EARNED_AWARDS,
        self::TRAVEL_AGENCY_EARNED_AWARDS,
        self::TOTAL_CHARGE,
        self::RATE_TYPE,
        self::COMMENT,
    ];

    public static function getTranslationMessages()
    {
        return [
            // # Common properties ##
            (new Message('itineraries.account-numbers', 'trips'))->setDesc('Account #'), // "Номера аккаунтов LP, которые были использованы при резервации, через \", \". Используется для привязки к людям на сайте"
            (new Message('itineraries.travel-agency-account-numbers', 'trips'))->setDesc('Travel Agency Account #'), // "Номера аккаунтов Travel Agency, которые были использованы при резервации, через \", \". Используется для привязки к людям на сайте"
            (new Message('itineraries.address', 'trips'))->setDesc('Address'), // "Адрес"
            (new Message('itineraries.booking-class', 'trips'))->setDesc('Booking class'), // "Класс бронирования"
            (new Message('itineraries.cancellation-policy', 'trips'))->setDesc('Cancellation Policy'), // "Например Cancel 1 day(s) prior to arrival to avoid a penalty"
            (new Message('itineraries.comment', 'trips'))->setDesc('Comments'),
            (new Message('itineraries.confirmation-number', 'trips'))->setDesc('Confirmation #'),
            (new Message('itineraries.confirmation-numbers', 'trips'))->setDesc('Confirmation Numbers'), // "Все номера резерваций на сайте провайдера в пределах 1 сегмента"
            (new Message('itineraries.cost', 'trips'))->setDesc('Cost'), // "Сумма до налогов"
            (new Message('itineraries.currency', 'trips'))->setDesc('Currency'), // "Код валюты, default USD"
            (new Message('itineraries.discount', 'trips'))->setDesc('Discount'), // "Сумма скидки одним числом"
            (new Message('itineraries.earned-awards', 'trips'))->setDesc('Earned Awards'), // "Бонусы, заработанные с этой резервации, 100 miles, 5 nights, etc."
            (new Message('itineraries.travel-agency-earned-awards', 'trips'))->setDesc('Travel Agency Earned Awards'), // "Бонусы, заработанные с этой резервации у Travel Agency, 100 miles, 5 nights, etc."
            (new Message('itineraries.fax', 'trips'))->setDesc('Fax'), // "Факс"
            (new Message('itineraries.notes', 'trips'))->setDesc('Notes'),
            (new Message('itineraries.phone', 'trips'))->setDesc('Phone'), // "Номер телефона"
            (new Message('itineraries.reservation-date', 'trips'))->setDesc('Reservation Date'), // "Дата создания резервации"
            (new Message('itineraries.retrieved-from', 'trips'))->setDesc('Retrieved From'), // "От куда получена резервация (провайдер)"
            (new Message('itineraries.spent-awards', 'trips'))->setDesc('Spent Awards'), // "Бонусы, потраченные на эту резервацию, 100 miles, 5 nights, etc."
            (new Message('itineraries.status', 'trips'))->setDesc('Status'), // "Статус: cancelled, confirmed, etc."
            (new Message('itineraries.total-charge', 'trips'))->setDesc('Total Charge'), // "Сумма"
            (new Message('itineraries.trip.fees', 'trips'))->setDesc('Fees'),
            (new Message('itineraries.timeline.owner', 'trips'))->setDesc('Travel Timeline Of'),
            (new Message('itineraries.travel-agency.phones.title', 'trips'))->setDesc('Travel Agency'),

            // # Parking ##
            (new Message('driver-name', 'trips'))->setDesc('Driver Name'), // "Имя на кого зарезервировано"
            (new Message('license-plate', 'trips'))->setDesc('License Plate'), // "Госномер машины"
            (new Message('parking-spot-number', 'trips'))->setDesc('Spot Number'), // "Номер места на парковке"
            (new Message('address', 'trips'))->setDesc('Address'), // "Адрес парковки"
            (new Message('parking-company', 'trips'))->setDesc('Parking Company'), // "Название фирмы (если отличается от названия провайдера)"
            (new Message('car-description', 'trips'))->setDesc('Car Description'), // "Описание машины"
            (new Message('itineraries.parking.start-date', 'trips'))->setDesc('Start'),
            (new Message('itineraries.parking.end-date', 'trips'))->setDesc('End'),
            (new Message('itineraries.parking.rate-type', 'trips'))->setDesc('Rate'), // Rate type

            // # Rental ##
            (new Message('itineraries.rental.car-model', 'trips'))->setDesc('Car Model'), // "например Ford Focus or similar"
            (new Message('itineraries.rental.car-type', 'trips'))->setDesc('Car Type'), // "например Mid-Size Economy"
            (new Message('itineraries.rental.dropoff-datetime', 'trips'))->setDesc('Dropoff Date'), // "Дата возврата автомобиля"
            (new Message('itineraries.rental.dropoff-fax', 'trips'))->setDesc('Dropoff Fax'), // "fax number of drop-off location"
            (new Message('itineraries.rental.dropoff-hours', 'trips'))->setDesc('Dropoff Hours'), // "working hours of drop-off location"
            (new Message('itineraries.rental.dropoff-location', 'trips'))->setDesc('Dropoff Address'), // "Место возврата автомобиля"
            (new Message('itineraries.rental.dropoff-phone', 'trips'))->setDesc('Dropoff Phone'), // "Телефон места возврата"
            (new Message('itineraries.rental.pickup-datetime', 'trips'))->setDesc('Pickup Date'), // "Дата взятия автомобиля"
            (new Message('itineraries.rental.pickup-fax', 'trips'))->setDesc('Pickup Fax'), // "fax number of pick-up location"
            (new Message('itineraries.rental.pickup-hours', 'trips'))->setDesc('Pickup Hours'), // "working hours of pick-up location"
            (new Message('itineraries.rental.pickup-location', 'trips'))->setDesc('Pickup Address'), // "Место взятия автомобиля"
            (new Message('itineraries.rental.pickup-phone', 'trips'))->setDesc('Pickup Phone'), // "Телефон места взятия"
            (new Message('itineraries.rental.rental-company', 'trips'))->setDesc('Rental Company'), // "Название фирмы (если отличается от названия провайдера)"
            (new Message('itineraries.rental.renter-name', 'trips'))->setDesc('Renter Name'), // "Имя на кого зарезервировано или имя водителя"
            (new Message('itineraries.rental.phones.title', 'trips'))->setDesc('Car Rental'),
            (new Message('itineraries.taxi.phones.title', 'trips'))->setDesc('Taxi'),
            (new Message('itineraries.rental.discount-details', 'trips'))->setDesc('Discount'), // An array of various discounts
            (new Message('itineraries.rental.priced-equipment', 'trips'))->setDesc('Equipment'), // An array of additional equipment

            // # Reservation ##
            (new Message('itineraries.reservation.check-in-date', 'trips'))->setDesc('Check-in'), // "Дата заселения"
            (new Message('itineraries.reservation.check-out-date', 'trips'))->setDesc('Check out'), // "Дата выселения"
            (new Message('itineraries.reservation.guest-names', 'trips'))->setDesc('Guest Names'), // "Имена гостей"
            (new Message('itineraries.reservation.guests', 'trips'))->setDesc('Guests'), // "Число взрослых гостей"
            (new Message('itineraries.reservation.hotel-name', 'trips'))->setDesc('Hotel Name'), // "Название отеля, оно определяет непосредственнно здание отеля по этому адресу."
            (new Message('itineraries.reservation.rate', 'trips'))->setDesc('Rate'), // "Например 109.00 EUR / night"
            (new Message('itineraries.reservation.rate-type', 'trips'))->setDesc('Rate Type'), // "Например 2 QUEEN BEDS NONSMOKING"
            (new Message('itineraries.reservation.room-type', 'trips'))->setDesc('Room Type'), // "Например 2 QUEEN BEDS NONSMOKING"
            (new Message('itineraries.reservation.room-type-description', 'trips'))->setDesc('Description'), // "Например Non-Smoking Room Confirmed"
            (new Message('itineraries.reservation.room_count', 'trips'))->setDesc('Room Count'), // "Число комнат"
            (new Message('itineraries.reservation.free_nights', 'trips'))->setDesc('Free Nights'), // "Бесплатные ночи"
            (new Message('itineraries.reservation.phones.title', 'trips'))->setDesc('Hotel'),
            (new Message('itineraries.reservation.non-refundable', 'trips'))->setDesc('Refundable'),

            // # Restaurant ##
            (new Message('itineraries.restaurant.diner-name', 'trips'))->setDesc('Diner Name'), // "Имя на кого зарезервировано"
            (new Message('itineraries.restaurant.end-date', 'trips'))->setDesc('End'), // "Дата завершения, существительное"
            (new Message('itineraries.restaurant.event-name', 'trips'))->setDesc('Event Name'), // "Название события"
            (new Message('itineraries.restaurant.guests', 'trips'))->setDesc('Guests'), // "На скольких человек зарезервировано"
            (new Message('itineraries.restaurant.start-date', 'trips'))->setDesc('Start'), // "Дата начала, существительное"
            (new Message('itineraries.restaurant.phones.title', 'trips'))->setDesc('Dining'),

            // #### Trips #####

            // # Common trips properties ##
            (new Message('itineraries.trip.arr-address', 'trips'))->setDesc('Arrival Address'), // "Адрес точки прибытия"
            (new Message('itineraries.trip.arr-date', 'trips'))->setDesc('Arrival date'), // "Дата и время прибытия"
            (new Message('itineraries.trip.arr-name', 'trips'))->setDesc('To'), // "Название точки прибытия"
            (new Message('itineraries.trip.cabin', 'trips'))->setDesc('Cabin'), // "Салон или класс билета (например, Economy)"
            (new Message('itineraries.trip.dep-address', 'trips'))->setDesc('Departure Address'), // "Адрес точки отправления"
            (new Message('itineraries.trip.dep-date', 'trips'))->setDesc('Departure date'), // "Дата и время отправления"
            (new Message('itineraries.trip.dep-name', 'trips'))->setDesc('From'), // "Название точки отправления"
            (new Message('itineraries.trip.duration', 'trips'))->setDesc('Duration'), // "Длительность поездки"
            (new Message('itineraries.trip.meal', 'trips'))->setDesc('Meal'), // "about ferry trips"
            (new Message('itineraries.trip.passengers', 'trips'))->setDesc('Passengers'), // "Пассажиры"
            (new Message('itineraries.trip.seats', 'trips'))->setDesc('Seats'), // 'Номера сидений'
            (new Message('itineraries.trip.smoking', 'trips'))->setDesc('Smoking'), // "Для курящих ли место"
            (new Message('itineraries.trip.stops', 'trips'))->setDesc('Stops'), // "Существительное. Кол-во остановок в пути"
            (new Message('itineraries.trip.tax', 'trips'))->setDesc('Tax'), // "Налоги"
            (new Message('itineraries.trip.travelled-miles', 'trips'))->setDesc('Travelled Miles'), // "Сколько миль длина поездки"
            (new Message('itineraries.trip.ticket_numbers', 'trips'))->setDesc('Ticket Numbers'), // "Номера билетов"
            (new Message('itineraries.trip.kids', 'trips'))->setDesc('Kids'), // "Число детей"
            (new Message('itineraries.trip.adults', 'trips'))->setDesc('Adults'), // "Число взрослых"
            (new Message('itineraries.trip.transfer.phones.title', 'trips'))->setDesc('Transfer'),

            // # Air ##
            (new Message('itineraries.trip.air.aircraft', 'trips'))->setDesc('Aircraft'), // "Самолет (i.e. boeing-747)"
            (new Message('itineraries.trip.air.airline-name', 'trips'))->setDesc('Airline'), // "Перевозчик"
            (new Message('itineraries.trip.air.arrival-terminal', 'trips'))->setDesc('Arrival Terminal'), // "Номер терминала прилета"
            (new Message('itineraries.trip.air.baggage-claim', 'trips'))->setDesc('Baggage Claim'),
            (new Message('itineraries.trip.air.base-fare', 'trips'))->setDesc('Base Fare'), // "this is about flight cost"
            (new Message('itineraries.trip.air.departure-terminal', 'trips'))->setDesc('Departure Terminal'), // "Номер терминала вылета"
            (new Message('itineraries.trip.air.flight-number', 'trips'))->setDesc('Flight Number'), // "Номер рейса. Например: указано \"Flight KQ203\", FlightNumber будет 203"
            (new Message('itineraries.trip.air.departure-gate', 'trips'))->setDesc('Gate'),
            (new Message('itineraries.trip.air.arrival-gate', 'trips'))->setDesc('Arrival Gate'),
            (new Message('itineraries.trip.air.fare_basis', 'trips'))->setDesc('Fare Basis Code'),
            (new Message('itineraries.trip.air.operator', 'trips'))->setDesc('Operator'),
            (new Message('itineraries.trip.air.phones.title', 'trips'))->setDesc('Airline'),

            // # Bus ##
            (new Message('itineraries.trip.bus.aircraft', 'trips'))->setDesc('Bus'), // "Автобус (i.e. Mercedes)"
            (new Message('itineraries.trip.bus.airline-name', 'trips'))->setDesc('Carrier'), // "Перевозчик"
            (new Message('itineraries.trip.bus.flight-number', 'trips'))->setDesc('Number'), // "Номер рейса/автобуса"
            (new Message('itineraries.trip.bus.phones.title', 'trips'))->setDesc('Bus'),

            // # Cruise ##
            (new Message('itineraries.trip.cruise.airline-name', 'trips'))->setDesc('Cruise line'), // "Перевозчик"
            (new Message('itineraries.trip.cruise.cruise-name', 'trips'))->setDesc('Cruise Name'),
            (new Message('itineraries.trip.cruise.deck', 'trips'))->setDesc('Deck'), // "about cruises"
            (new Message('itineraries.trip.cruise.flight-number', 'trips'))->setDesc('Number'), // "Номер круиза"
            (new Message('itineraries.trip.cruise.room-class', 'trips'))->setDesc('Room Class'), // "about cruises"
            (new Message('itineraries.trip.cruise.room-number', 'trips'))->setDesc('Room Number'), // "about cruises"
            (new Message('itineraries.trip.cruise.ship-code', 'trips'))->setDesc('Ship Code'), // "about cruises"
            (new Message('itineraries.trip.cruise.ship-name', 'trips'))->setDesc('Ship Name'), // "about cruises"
            (new Message('itineraries.trip.cruise.phones.title', 'trips'))->setDesc('Cruise'),

            // # Ferry ##
            (new Message('itineraries.trip.ferry.airline-name', 'trips'))->setDesc('Ferry'), // "Перевозчик"
            (new Message('itineraries.trip.ferry.flight-number', 'trips'))->setDesc('Number'), // "Номер парома"
            (new Message('itineraries.trip.ferry.phones.title', 'trips'))->setDesc('Ferry'),
            (new Message('itineraries.trip.ferry.accommodations', 'trips'))->setDesc('Accommodations'), // "Assigned passenger accommodations on a ferry"
            (new Message('itineraries.trip.ferry.vessel', 'trips'))->setDesc('Vessel'), // "Name or type of the vessel/ferry"
            (new Message('itineraries.trip.ferry.pets', 'trips'))->setDesc('Pets'), // "Pets listed on the ticket"
            (new Message('itineraries.trip.ferry.vehicles', 'trips'))->setDesc('Vehicles'), // "Vehicles listed on the reservation"

            // # Train ##
            (new Message('itineraries.trip.train.airline-name', 'trips'))->setDesc('Trains'), // "Перевозчик"
            (new Message('itineraries.trip.train.flight-number', 'trips'))->setDesc('Train Number'), // "Номер рейса/поезда"
            (new Message('itineraries.trip.train.service_name', 'trips'))->setDesc('Service Name'), // "Short name of the particular service or route"
            (new Message('itineraries.trip.train.car_number', 'trips'))->setDesc('Car Number'), // "Номер вагона"
            (new Message('itineraries.trip.train.phones.title', 'trips'))->setDesc('Train'),
        ];
    }

    public static function getTranslationKeyForProperty(string $property, string $type): string
    {
        $map = [
            'default' => [
                self::RESERVATION_DATE => 'itineraries.reservation-date',
                self::CONFIRMATION_NUMBER => 'itineraries.confirmation-number',
                self::CONFIRMATION_NUMBERS => 'itineraries.confirmation-numbers',
                self::DEPARTURE_NAME => 'itineraries.trip.dep-name',
                self::DEPARTURE_ADDRESS => 'itineraries.trip.dep-address',
                self::DEPARTURE_DATE => 'itineraries.trip.dep-date',
                self::ARRIVAL_NAME => 'itineraries.trip.arr-name',
                self::ARRIVAL_ADDRESS => 'itineraries.trip.arr-address',
                self::ARRIVAL_DATE => 'itineraries.trip.arr-date',
                self::RETRIEVE_FROM => 'itineraries.retrieved-from',
                self::AIRLINE_NAME => 'itineraries.retrieved-from',
                self::FLIGHT_NUMBER => 'itineraries.trip.air.flight-number',
                self::ACCOUNT_NUMBERS => 'itineraries.account-numbers',
                self::TRAVEL_AGENCY_ACCOUNT_NUMBERS => 'itineraries.travel-agency-account-numbers',
                self::COST => 'itineraries.cost',
                self::CURRENCY => 'itineraries.currency',
                self::TAX => 'itineraries.trip.tax',
                self::DISCOUNT => 'itineraries.discount',
                self::TOTAL_CHARGE => 'itineraries.total-charge',
                self::SPENT_AWARDS => 'itineraries.spent-awards',
                self::EARNED_AWARDS => 'itineraries.earned-awards',
                self::TRAVEL_AGENCY_EARNED_AWARDS => 'itineraries.travel-agency-earned-awards',
                self::TICKET_NUMBERS => 'itineraries.trip.ticket_numbers',
                self::IS_SMOKING => 'itineraries.trip.smoking',
                self::STOPS_COUNT => 'itineraries.trip.stops',
                self::ADULTS_COUNT => 'itineraries.trip.adults',
                self::KIDS_COUNT => 'itineraries.trip.kids',
                self::TRAVELED_MILES => 'itineraries.trip.travelled-miles',
                self::MEAL => 'itineraries.trip.meal',
                self::BOOKING_CLASS => 'itineraries.booking-class',
                self::FLIGHT_CABIN_CLASS => 'itineraries.trip.cabin',
                self::SEATS => 'itineraries.trip.seats',
                self::DURATION => 'itineraries.trip.duration',
                self::STATUS => 'itineraries.status',
                self::CANCELLATION_POLICY => 'itineraries.cancellation-policy',
                self::FEES => 'itineraries.trip.fees',
                self::ADDRESS => 'itineraries.address',
                self::PHONE => 'itineraries.phone',
                self::NOTES => 'itineraries.notes',
                self::COMMENT => 'itineraries.comment',
            ],
            'flight' => [
                self::COST => 'itineraries.trip.air.base-fare',
                self::AIRLINE_NAME => 'itineraries.trip.air.airline-name',
                self::FLIGHT_NUMBER => 'itineraries.trip.air.flight-number',
                self::AIRCRAFT => 'itineraries.trip.air.aircraft',
                self::ARRIVAL_TERMINAL => 'itineraries.trip.air.arrival-terminal',
                self::ARRIVAL_GATE => 'itineraries.trip.air.arrival-gate',
                self::DEPARTURE_TERMINAL => 'itineraries.trip.air.departure-terminal',
                self::DEPARTURE_GATE => 'itineraries.trip.air.departure-gate',
                self::FARE_BASIS => 'itineraries.trip.air.fare_basis',
                self::BAGGAGE_CLAIM => 'itineraries.trip.air.baggage-claim',
                self::TRAVELER_NAMES => 'itineraries.trip.passengers',
                self::OPERATING_AIRLINE_NAME => 'itineraries.trip.air.operator',
            ],
            'bus_ride' => [
                self::AIRCRAFT => 'itineraries.trip.bus.aircraft',
                self::AIRLINE_NAME => 'itineraries.trip.bus.airline-name',
                self::FLIGHT_NUMBER => 'itineraries.trip.bus.flight-number',
                self::TRAVELER_NAMES => 'itineraries.trip.passengers',
            ],
            'cruise' => [
                self::AIRLINE_NAME => 'itineraries.trip.cruise.airline-name',
                self::FLIGHT_NUMBER => 'itineraries.trip.cruise.flight-number',
                self::CRUISE_NAME => 'itineraries.trip.cruise.cruise-name',
                self::DECK => 'itineraries.trip.cruise.deck',
                self::SHIP_CABIN_NUMBER => 'itineraries.trip.cruise.room-number',
                self::SHIP_CODE => 'itineraries.trip.cruise.ship-code',
                self::SHIP_NAME => 'itineraries.trip.cruise.ship-name',
                self::SHIP_CABIN_CLASS => 'itineraries.trip.cruise.room-class',
                self::TRAVELER_NAMES => 'itineraries.trip.passengers',
            ],
            'ferry_ride' => [
                self::AIRLINE_NAME => 'itineraries.trip.ferry.airline-name',
                self::FLIGHT_NUMBER => 'itineraries.trip.ferry.flight-number',
                self::TRAVELER_NAMES => 'itineraries.trip.passengers',
                self::ACCOMMODATIONS => 'itineraries.trip.ferry.accommodations',
                self::VESSEL => 'itineraries.trip.ferry.vessel',
                self::PETS => 'itineraries.trip.ferry.pets',
                self::VEHICLES => 'itineraries.trip.ferry.vehicles',
            ],
            'train_ride' => [
                self::AIRLINE_NAME => 'itineraries.trip.train.airline-name',
                self::FLIGHT_NUMBER => 'itineraries.trip.train.flight-number',
                self::TRAIN_SERVICE_NAME => 'itineraries.trip.train.service_name',
                self::TRAIN_CAR_NUMBER => 'itineraries.trip.train.car_number',
                self::TRAVELER_NAMES => 'itineraries.trip.passengers',
            ],
            'transfer' => [
                self::TRAVELER_NAMES => 'itineraries.trip.passengers',
            ],
            'hotel_reservation' => [
                self::ROOM_COUNT => 'itineraries.reservation.room_count',
                self::FREE_NIGHTS => 'itineraries.reservation.free_nights',
                self::ROOM_LONG_DESCRIPTION => 'itineraries.reservation.room-type-description',
                self::ROOM_SHORT_DESCRIPTION => 'itineraries.reservation.room-type',
                self::ROOM_RATE => 'itineraries.reservation.rate',
                self::ROOM_RATE_DESCRIPTION => 'itineraries.reservation.rate-type',
                self::TRAVELER_NAMES => 'itineraries.reservation.guest-names',
                self::GUEST_COUNT => 'itineraries.reservation.guests',
                self::HOTEL_NAME => 'itineraries.reservation.hotel-name',
                self::CHECK_IN_DATE => 'itineraries.reservation.check-in-date',
                self::CHECK_OUT_DATE => 'itineraries.reservation.check-out-date',
                self::FAX => 'itineraries.fax',
                self::NON_REFUNDABLE => 'itineraries.reservation.non-refundable',
            ],
            'rental' => [
                self::CAR_MODEL => 'itineraries.rental.car-model',
                self::CAR_TYPE => 'itineraries.rental.car-type',
                self::PICK_UP_FAX => 'itineraries.rental.pickup-fax',
                self::DROP_OFF_FAX => 'itineraries.rental.dropoff-fax',
                self::TRAVELER_NAMES => 'itineraries.rental.renter-name',
                self::PICK_UP_HOURS => 'itineraries.rental.pickup-hours',
                self::DROP_OFF_HOURS => 'itineraries.rental.dropoff-hours',
                self::RENTAL_COMPANY => 'itineraries.rental.rental-company',
                self::PICK_UP_LOCATION => 'itineraries.rental.pickup-location',
                self::PICK_UP_DATE => 'itineraries.rental.pickup-datetime',
                self::PICK_UP_PHONE => 'itineraries.rental.pickup-phone',
                self::DROP_OFF_LOCATION => 'itineraries.rental.dropoff-location',
                self::DROP_OFF_DATE => 'itineraries.rental.dropoff-datetime',
                self::DROP_OFF_PHONE => 'itineraries.rental.dropoff-phone',
                self::DISCOUNT_DETAILS => 'itineraries.rental.discount-details',
                self::PRICED_EQUIPMENT => 'itineraries.rental.priced-equipment',
            ],
            'event' => [
                self::DINER_NAME => 'itineraries.restaurant.diner-name',
                self::TRAVELER_NAMES => 'itineraries.reservation.guests',
                self::GUEST_COUNT => 'itineraries.restaurant.guests',
                self::START_DATE => 'itineraries.restaurant.start-date',
                self::END_DATE => 'itineraries.restaurant.end-date',
                self::EVENT_NAME => 'itineraries.restaurant.event-name',
            ],
            'parking' => [
                self::LICENSE_PLATE => 'license-plate',
                self::SPOT_NUMBER => 'parking-spot-number',
                self::TRAVELER_NAMES => 'driver-name',
                self::LOCATION => 'address',
                self::PARKING_COMPANY => 'parking-company',
                self::CAR_DESCRIPTION => 'car-description',
                self::START_DATE => 'itineraries.parking.start-date',
                self::END_DATE => 'itineraries.parking.end-date',
                self::RATE_TYPE => 'itineraries.parking.rate-type',
            ],
        ];
        $map['taxi_ride'] = $map['rental'];
        $defaultTranslationKey = $map['default'][$property] ?? null;
        $customTranslationKey = isset($map[$type]) && isset($map[$type][$property]) ? $map[$type][$property] : null;

        return $customTranslationKey ?? $defaultTranslationKey ?? $property;
    }
}
