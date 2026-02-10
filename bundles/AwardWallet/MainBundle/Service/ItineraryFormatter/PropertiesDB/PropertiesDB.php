<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesDB;

use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesList as P;

class PropertiesDB
{
    /**
     * @var PropertyInfo[]
     */
    protected array $propertiesEntriesMap = [];

    public function build()
    {
        $this->addProperty(P::RESERVATION_DATE)
            ->addTags([Tags::DATE, Tags::COMMON]);

        $this->addProperty(P::CONFIRMATION_NUMBER)
            ->setPrivate()
            ->addTags([Tags::COMMON]);

        $this->addProperty(P::CONFIRMATION_NUMBERS)
            ->setPrivate()
            ->addTags([Tags::COMMON]);

        $this->addProperty(P::DEPARTURE_NAME)
            ->addTags([Tags::TRIP]);

        $this->addProperty(P::DEPARTURE_DATE)
            ->addTags([Tags::DATE, Tags::TRIP]);

        $this->addProperty(P::DEPARTURE_TERMINAL)
            ->addTags([Tags::TRIP]);
        $this->addProperty(P::DEPARTURE_GATE)
            ->setPrivate()
            ->addTags([Tags::TRIP]);

        $this->addProperty(P::DEPARTURE_AIRPORT_CODE)
            ->addTags([Tags::TRIP]);
        $this->addProperty(P::ARRIVAL_NAME)
            ->addTags([Tags::TRIP]);
        $this->addProperty(P::ARRIVAL_DATE)
            ->addTags([Tags::DATE, Tags::TRIP]);

        $this->addProperty(P::ARRIVAL_TERMINAL)
            ->addTags([Tags::TRIP]);
        $this->addProperty(P::ARRIVAL_GATE)
            ->addTags([Tags::TRIP])
            ->setPrivate();

        $this->addProperty(P::ARRIVAL_AIRPORT_CODE)
            ->addTags([Tags::TRIP]);
        $this->addProperty(P::RETRIEVE_FROM)
            ->addTags([Tags::COMMON]);
        $this->addProperty(P::AIRLINE_NAME)
            ->addTags([Tags::TRIP]);
        $this->addProperty(P::OPERATING_AIRLINE_NAME)
            ->addTags([Tags::TRIP]);
        $this->addProperty(P::FLIGHT_NUMBER)
            ->addTags([Tags::TRIP]);
        $this->addProperty(P::AIRCRAFT)
            ->addTags([Tags::TRIP]);
        $this->addProperty(P::ACCOUNT_NUMBERS)
            ->addTags([Tags::COMMON])
            ->setPrivate();

        $this->addProperty(P::TRAVEL_AGENCY_ACCOUNT_NUMBERS)
            ->addTags([Tags::COMMON])
            ->setPrivate();

        $this->addProperty(P::COST)
            ->setPrivate()
            ->addTags([Tags::PRICING_INFO, Tags::COMMON]);

        $this->addProperty(P::CURRENCY)
            ->setPrivate()
            ->addTags([Tags::PRICING_INFO, Tags::COMMON, Tags::INTERNAL]);

        $this->addProperty(P::FEES)
            ->addTags([Tags::COMMON, Tags::PRICING_INFO]);
        $this->addProperty(P::DISCOUNT)
            ->setPrivate()
            ->addTags([Tags::PRICING_INFO, Tags::COMMON]);

        $this->addProperty(P::TOTAL_CHARGE)
            ->setPrivate()
            ->addTags([Tags::PRICING_INFO, Tags::COMMON]);

        $this->addProperty(P::COMMENT)
            ->addTags([Tags::COMMON]);

        $this->addProperty(P::SPENT_AWARDS)
            ->addTags([Tags::COMMON])
            ->setPrivate();

        $this->addProperty(P::EARNED_AWARDS)
            ->addTags([Tags::COMMON])
            ->setPrivate();

        $this->addProperty(P::TRAVEL_AGENCY_EARNED_AWARDS)
            ->addTags([Tags::COMMON]);
        $this->addProperty(P::CRUISE_NAME)
            ->addTags([Tags::TRIP]);
        $this->addProperty(P::DECK)
            ->addTags([Tags::TRIP]);
        $this->addProperty(P::SHIP_CABIN_NUMBER)
            ->addTags([Tags::TRIP]);
        $this->addProperty(P::SHIP_CODE)
            ->addTags([Tags::TRIP]);
        $this->addProperty(P::SHIP_NAME)
            ->addTags([Tags::TRIP]);
        $this->addProperty(P::TICKET_NUMBERS)
            ->addTags([Tags::TRIP])
            ->setPrivate();

        $this->addProperty(P::SHIP_CABIN_CLASS)
            ->addTags([Tags::TRIP]);
        $this->addProperty(P::IS_SMOKING)
            ->addTags([Tags::TRIP]);
        $this->addProperty(P::STOPS_COUNT)
            ->addTags([Tags::NUMBER, Tags::TRIP]);

        $this->addProperty(P::FARE_BASIS)
            ->addTags([Tags::TRIP]);
        $this->addProperty(P::TRAIN_SERVICE_NAME)
            ->addTags([Tags::TRIP]);
        $this->addProperty(P::TRAIN_CAR_NUMBER)
            ->addTags([Tags::TRIP]);
        $this->addProperty(P::ADULTS_COUNT)
            ->addTags([Tags::NUMBER, Tags::TRIP]);
        $this->addProperty(P::KIDS_COUNT)
            ->addTags([Tags::NUMBER, Tags::TRIP, Tags::RESERVATION]);

        $this->addProperty(P::TRAVELER_NAMES)
            ->addTags([Tags::COMMON]);
        $this->addProperty(P::ACCOMMODATIONS)
            ->addTags([Tags::TRIP]);
        $this->addProperty(P::VESSEL)
            ->addTags([Tags::TRIP]);
        $this->addProperty(P::PETS)
            ->addTags([Tags::TRIP]);
        $this->addProperty(P::VEHICLES)
            ->addTags([Tags::TRIP]);
        $this->addProperty(P::TRAVELED_MILES)
            ->addTags([Tags::TRIP])
            ->setPrivate();

        $this->addProperty(P::MEAL)
            ->addTags([Tags::TRIP]);
        $this->addProperty(P::BOOKING_CLASS)
            ->addTags([Tags::TRIP])
            ->setPrivate();

        $this->addProperty(P::FLIGHT_CABIN_CLASS)
            ->addTags([Tags::TRIP])
            ->setPrivate();

        $this->addProperty(P::SEATS)
            ->addTags([Tags::TRIP])
            ->setPrivate();

        $this->addProperty(P::DURATION)
            ->addTags([Tags::TRIP]);
        $this->addProperty(P::BAGGAGE_CLAIM)
            ->addTags([Tags::TRIP]);
        $this->addProperty(P::STATUS)
            ->addTags([Tags::TRIP]);

        $this->addProperty(P::HOTEL_NAME)
            ->addTags([Tags::RESERVATION]);
        $this->addProperty(P::CHECK_IN_DATE)
            ->addTags([Tags::DATE, Tags::RESERVATION]);

        $this->addProperty(P::CHECK_OUT_DATE)
            ->addTags([Tags::DATE, Tags::RESERVATION]);

        $this->addProperty(P::ADDRESS)
            ->addTags([Tags::RESERVATION, Tags::RESTAURANT]);
        $this->addProperty(P::GUEST_COUNT)
            ->addTags([Tags::NUMBER, Tags::RESERVATION, Tags::RESTAURANT]);

        $this->addProperty(P::ROOM_COUNT)
            ->addTags([Tags::NUMBER, Tags::RESERVATION]);

        $this->addProperty(P::FREE_NIGHTS)
            ->addTags([Tags::NUMBER, Tags::RESERVATION]);

        $this->addProperty(P::ROOM_LONG_DESCRIPTION)
            ->addTags([Tags::RESERVATION]);
        $this->addProperty(P::ROOM_SHORT_DESCRIPTION)
            ->addTags([Tags::RESERVATION]);
        $this->addProperty(P::ROOM_RATE)
            ->addTags([Tags::RESERVATION])
            ->setPrivate();

        $this->addProperty(P::ROOM_RATE_DESCRIPTION)
            ->addTags([Tags::RESERVATION])
            ->setPrivate();

        $this->addProperty(P::PHONE)
            ->addTags([Tags::RESERVATION, Tags::RESTAURANT, Tags::PARKING]);
        $this->addProperty(P::FAX)
            ->addTags([Tags::RESERVATION]);
        $this->addProperty(P::CANCELLATION_POLICY)
            ->addTags([Tags::RESERVATION]);
        $this->addProperty(P::CANCELLATION_DEADLINE)
            ->addTags([Tags::RESERVATION, Tags::INTERNAL]);
        $this->addProperty(P::NON_REFUNDABLE)
            ->addTags([Tags::RESERVATION]);

        $this->addProperty(P::PICK_UP_LOCATION)
            ->addTags([Tags::RENTAL]);
        $this->addProperty(P::PICK_UP_DATE)
            ->addTags([Tags::DATE, Tags::RENTAL]);

        $this->addProperty(P::PICK_UP_HOURS)
            ->addTags([Tags::RENTAL]);
        $this->addProperty(P::PICK_UP_PHONE)
            ->addTags([Tags::RENTAL]);
        $this->addProperty(P::PICK_UP_FAX)
            ->addTags([Tags::RENTAL]);
        $this->addProperty(P::DROP_OFF_LOCATION)
            ->addTags([Tags::RENTAL]);
        $this->addProperty(P::DROP_OFF_DATE)
            ->addTags([Tags::DATE, Tags::RENTAL]);

        $this->addProperty(P::DROP_OFF_HOURS)
            ->addTags([Tags::RENTAL]);
        $this->addProperty(P::DROP_OFF_PHONE)
            ->addTags([Tags::RENTAL]);
        $this->addProperty(P::DROP_OFF_FAX)
            ->addTags([Tags::RENTAL]);
        $this->addProperty(P::RENTAL_COMPANY)
            ->addTags([Tags::RENTAL]);
        $this->addProperty(P::CAR_TYPE)
            ->addTags([Tags::RENTAL]);
        $this->addProperty(P::CAR_MODEL)
            ->addTags([Tags::RENTAL]);
        $this->addProperty(P::CAR_IMAGE_URL)
            ->addTags([Tags::RENTAL]);
        $this->addProperty(P::DISCOUNT_DETAILS)
            ->addTags([Tags::RENTAL]);
        $this->addProperty(P::PRICED_EQUIPMENT)
            ->addTags([Tags::RENTAL]);

        $this->addProperty(P::START_DATE)
            ->addTags([Tags::DATE, Tags::RESTAURANT, Tags::PARKING]);

        $this->addProperty(P::END_DATE)
            ->addTags([Tags::DATE, Tags::RESTAURANT, Tags::PARKING]);

        $this->addProperty(P::EVENT_NAME)
            ->addTags([Tags::RESTAURANT]);
        $this->addProperty(P::NOTES)
            ->addTags([Tags::COMMON])
            ->setPrivate();

        $this->addProperty(P::PARKING_COMPANY)
            ->addTags([Tags::PARKING]);
        $this->addProperty(P::LOCATION)
            ->addTags([Tags::PARKING]);
        $this->addProperty(P::LICENSE_PLATE)
            ->addTags([Tags::PARKING]);
        $this->addProperty(P::SPOT_NUMBER)
            ->addTags([Tags::PARKING]);
        $this->addProperty(P::CAR_DESCRIPTION)
            ->addTags([Tags::PARKING]);
        $this->addProperty(P::RATE_TYPE)
            ->addTags([Tags::PARKING]);

        $this->addProperty(P::FEES_LIST)
            ->addTags([Tags::PRICING_INFO, Tags::COMMON, Tags::INTERNAL]);
    }

    /**
     * @return PropertyInfo[]
     */
    public function getProperties(): array
    {
        if (!$this->propertiesEntriesMap) {
            $this->build();
        }

        return $this->propertiesEntriesMap;
    }

    private function addProperty(string $name): PropertyInfo
    {
        return $this->propertiesEntriesMap[$name] = new PropertyInfo($name);
    }
}
