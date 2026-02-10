<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter\Constants;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesList;

/**
 * @NoDI()
 */
class WhiteList
{
    // changing any properties will cause a reminder
    public const LIST = [
        // Common
        PropertiesList::ACCOUNT_NUMBERS,
        PropertiesList::TOTAL_CHARGE,
        PropertiesList::STATUS,

        // Trip
        PropertiesList::TRAVELER_NAMES,
        PropertiesList::FLIGHT_NUMBER,
        PropertiesList::DEPARTURE_DATE,
        PropertiesList::DEPARTURE_AIRPORT_CODE,
        PropertiesList::DEPARTURE_NAME,
        PropertiesList::ARRIVAL_AIRPORT_CODE,
        PropertiesList::ARRIVAL_NAME,
        PropertiesList::BOOKING_CLASS,
        PropertiesList::FARE_BASIS,
        PropertiesList::SEATS,
        PropertiesList::STOPS_COUNT,
        PropertiesList::DEPARTURE_GATE,

        PropertiesList::DEPARTURE_ADDRESS,
        PropertiesList::ARRIVAL_ADDRESS,

        PropertiesList::SHIP_NAME,
        PropertiesList::SHIP_CODE,
        PropertiesList::CRUISE_NAME,
        PropertiesList::DECK,
        PropertiesList::SHIP_CABIN_NUMBER,
        PropertiesList::SHIP_CABIN_CLASS,

        // Reservation
        PropertiesList::CHECK_IN_DATE,
        PropertiesList::CHECK_OUT_DATE,
        PropertiesList::TRAVELER_NAMES,
        PropertiesList::ROOM_COUNT,
        PropertiesList::ROOM_RATE,
        PropertiesList::TOTAL_CHARGE,
        PropertiesList::GUEST_COUNT,
        PropertiesList::FREE_NIGHTS,

        // Rental
        PropertiesList::PICK_UP_DATE,
        PropertiesList::PICK_UP_LOCATION,
        PropertiesList::PICK_UP_HOURS,
        PropertiesList::DROP_OFF_DATE,
        PropertiesList::DROP_OFF_LOCATION,
        PropertiesList::DROP_OFF_HOURS,
        PropertiesList::CAR_TYPE,
        PropertiesList::CAR_MODEL,
        PropertiesList::DISCOUNT,

        // Restaurant
        PropertiesList::START_DATE,
        PropertiesList::END_DATE,
        PropertiesList::EVENT_NAME,
        PropertiesList::ADDRESS,

        // Parking
        PropertiesList::START_DATE,
        PropertiesList::LOCATION,
        PropertiesList::END_DATE,
    ];
}
