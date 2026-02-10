<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\Tripit;

use AwardWallet\MainBundle\Service\Tripit\TripitConverter;
use AwardWallet\Tests\Unit\BaseContainerTest;

/**
 * @group frontend-unit
 */
class TripitConverterTest extends BaseContainerTest
{
    private const CATEGORY_ACTIVITY = 'ActivityObject';
    private const CATEGORY_AIR = 'AirObject';
    private const CATEGORY_CAR = 'CarObject';
    private const CATEGORY_CRUISE = 'CruiseObject';
    private const CATEGORY_LODGING = 'LodgingObject';
    private const CATEGORY_PARKING = 'ParkingObject';
    private const CATEGORY_RAIL = 'RailObject';
    private const CATEGORY_RESTAURANT = 'RestaurantObject';
    private const CATEGORY_TRANSPORT = 'TransportObject';

    private ?TripitConverter $converter;

    public function _before()
    {
        parent::_before();
        $this->converter = new TripitConverter(
            $this->container->get('jms_serializer'),
            $this->container->get('validator'),
            $this->container->get('logger')
        );
    }

    /**
     * Конвертирует полученные резервации в объекты `Itinerary`.
     *
     * @param string $object название объекта, который приходит из API TripIt
     * @param string $file имя файла, содержащего json, соответствующий объекту `Itinerary`
     * @dataProvider itineraryProvider
     */
    public function testCreateItinerary(string $object, string $file)
    {
        $itinerary = json_decode(file_get_contents(__DIR__ . "/Fixtures/{$file}.json"), true);
        $data = [$object => self::getTripsArray()[$object]];
        $result = json_encode($this->converter->convert($data));

        $this->assertEquals($itinerary, json_decode($result, true));
    }

    /**
     * Проверяет на наличие обязательных параметров в полученных резервациях.
     *
     * @param string $object название объекта, который приходит из API TripIt
     * @dataProvider itineraryProvider
     */
    public function testValidationItinerary(string $object)
    {
        $data = [$object => self::getIncorrectTripsArray()[$object]];
        $result = json_encode($this->converter->convert($data));

        $this->assertEquals([], json_decode($result, true));
    }

    public function itineraryProvider(): array
    {
        return [
            'Event show' => [self::CATEGORY_ACTIVITY, 'eventShow'],
            'Flight' => [self::CATEGORY_AIR, 'flight'],
            'Car rental' => [self::CATEGORY_CAR, 'carRental'],
            'Cruise' => [self::CATEGORY_CRUISE, 'cruise'],
            'Hotel reservation' => [self::CATEGORY_LODGING, 'hotelReservation'],
            'Parking' => [self::CATEGORY_PARKING, 'parking'],
            'Train' => [self::CATEGORY_RAIL, 'train'],
            'Event restaurant' => [self::CATEGORY_RESTAURANT, 'eventRestaurant'],
            'Ferry, Bus' => [self::CATEGORY_TRANSPORT, 'transportation'],
        ];
    }

    private static function getTripsArray(): array
    {
        return [
            self::CATEGORY_ACTIVITY => [
                'id' => 10,
                'trip_id' => 1,
                'booking_date' => '2023-09-01',
                'booking_site_conf_num' => 'MRKX10000',
                'booking_site_name' => 'Ticketweb',
                'booking_site_phone' => '1-866-400-0000',
                'booking_site_url' => 'http://m.ticketweb.com/',
                'supplier_conf_num' => '568500000000',
                'supplier_name' => 'Together As One Tour',
                'supplier_phone' => '',
                'supplier_url' => '',
                'total_cost' => '90',
                'StartDateTime' => [
                    'date' => '2023-09-10',
                    'time' => '20:00:00',
                    'timezone' => 'America/Toronto',
                    'utc_offset' => '-04:00',
                ],
                'EndDateTime' => [
                    'date' => '2023-09-10',
                    'time' => '23:00:00',
                    'timezone' => 'America/Toronto',
                    'utc_offset' => '-04:00',
                ],
                'Address' => [
                    'address' => '473 Adelaide St W, Toronto, ON M5V 1T1',
                    'city' => 'Toronto',
                    'state' => 'ON',
                    'zip' => 'M5V 1T1',
                    'country' => 'CA',
                    'latitude' => '43.645162',
                    'longitude' => '-79.399886',
                ],
                'Participant' => [
                    [
                        'first_name' => 'Tomoya',
                        'last_name' => 'Okazaki',
                    ],
                ],
            ],
            self::CATEGORY_AIR => [
                'id' => 20,
                'trip_id' => 2,
                'booking_date' => '2023-08-02',
                'booking_site_conf_num' => 'DMP000',
                'booking_site_name' => 'Air Serbia',
                'booking_site_phone' => '+381 11 300 00 00',
                'booking_site_url' => 'https://www.airserbia.com/',
                'supplier_conf_num' => 'DMP000',
                'supplier_name' => '',
                'supplier_phone' => '',
                'supplier_url' => '',
                'Segment' => [
                    [
                        'StartDateTime' => [
                            'date' => '2023-09-05',
                            'time' => '02:00:00',
                            'timezone' => 'Europe/Moscow',
                            'utc_offset' => '+03:00',
                        ],
                        'EndDateTime' => [
                            'date' => '2023-09-05',
                            'time' => '04:30:00',
                            'timezone' => 'Europe/Belgrade',
                            'utc_offset' => '+02:00',
                        ],
                        'start_airport_code' => 'SVO',
                        'start_airport_latitude' => '55.966324',
                        'start_airport_longitude' => '37.416574',
                        'start_city_name' => 'Moscow',
                        'start_country_code' => 'RU',
                        'start_terminal' => 'C - International',
                        'end_airport_code' => 'BEG',
                        'end_airport_latitude' => '44.819444',
                        'end_airport_longitude' => '20.306944',
                        'end_city_name' => 'Belgrade',
                        'end_country_code' => 'RS',
                        'end_terminal' => '2',
                        'marketing_airline' => 'Air Serbia',
                        'marketing_airline_code' => 'JU',
                        'marketing_flight_number' => '659',
                        'aircraft' => '319',
                        'aircraft_display_name' => 'Airbus A319',
                        'distance' => '1,071 mi',
                        'duration' => '3h, 05m',
                    ],
                    [
                        'StartDateTime' => [
                            'date' => '2023-09-11',
                            'time' => '20:40:00',
                            'timezone' => 'Europe/Belgrade',
                            'utc_offset' => '+02:00',
                        ],
                        'EndDateTime' => [
                            'date' => '2023-09-12',
                            'time' => '00:50:00',
                            'timezone' => 'Europe/Moscow',
                            'utc_offset' => '+03:00',
                        ],
                        'start_airport_code' => 'BEG',
                        'start_airport_latitude' => '44.819444',
                        'start_airport_longitude' => '20.306944',
                        'start_city_name' => 'Belgrade',
                        'start_country_code' => 'RS',
                        'start_terminal' => '2',
                        'end_airport_code' => 'SVO',
                        'end_airport_latitude' => '55.966324',
                        'end_airport_longitude' => '37.416574',
                        'end_city_name' => 'Moscow',
                        'end_country_code' => 'RU',
                        'end_terminal' => 'C - International',
                        'marketing_airline' => 'Air Serbia',
                        'marketing_airline_code' => 'JU',
                        'marketing_flight_number' => '658',
                        'aircraft' => '319',
                        'aircraft_display_name' => 'Airbus A319',
                        'distance' => '1,071 mi',
                        'duration' => '3h, 10m',
                    ],
                ],
                'Traveler' => [
                    [
                        'first_name' => 'Kyou',
                        'last_name' => 'Fujibayashi',
                        'ticket_num' => '1152100000001',
                    ],
                    [
                        'first_name' => 'Ryou',
                        'last_name' => 'Fujibayashi',
                        'ticket_num' => '1152100000002',
                    ],
                ],
            ],
            self::CATEGORY_CAR => [
                'id' => 30,
                'trip_id' => 3,
                'booking_date' => '2023-09-03',
                'booking_site_conf_num' => '1694000000',
                'booking_site_name' => 'Europcar',
                'booking_site_phone' => '1-877-900-0000',
                'booking_site_url' => 'https://www.europcar.com/',
                'supplier_conf_num' => '1165000000',
                'supplier_name' => 'Europcar',
                'supplier_phone' => '',
                'supplier_url' => '',
                'restrictions' => 'Payment at the rental location: Modifications to your booking are free of charge up to the time of pick-up (though new rental prices may apply)',
                'total_cost' => 'EUR 550.00',
                'StartDateTime' => [
                    'date' => '2023-10-05',
                    'time' => '11:00:00',
                    'timezone' => 'Europe/Paris',
                    'utc_offset' => '+02:00',
                ],
                'EndDateTime' => [
                    'date' => '2023-10-12',
                    'time' => '11:00:00',
                    'timezone' => 'Europe/Paris',
                    'utc_offset' => '+02:00',
                ],
                'StartLocationAddress' => [
                    'address' => 'Aeroport De Marseille Provence 13700 Marignane',
                    'city' => 'Marignane',
                    'state' => 'Provence-alpes-côte D\'azur',
                    'zip' => '13700',
                    'country' => 'FR',
                    'latitude' => '43.438423',
                    'longitude' => '5.214414',
                ],
                'EndLocationAddress' => [
                    'address' => 'Nice Cote d\'Azur Airport Car Rental Center T2 AV D.Daurat 06281 Nice',
                    'city' => 'Nice',
                    'state' => 'Provence-alpes-côte D\'azur',
                    'zip' => '06281',
                    'country' => 'FR',
                    'latitude' => '43.659769',
                    'longitude' => '7.214821',
                ],
                'Driver' => [
                    'first_name' => 'Tomoyo',
                    'last_name' => 'Sakagami',
                    'frequent_traveler_num' => '119100000',
                    'frequent_traveler_supplier' => 'Europcar',
                ],
                'start_location_hours' => '12:00 AM - 7:29 AM with extra price 7:30 AM - 11:59 PM',
                'start_location_name' => 'Marseille Provence Airport',
                'start_location_phone' => '+33 (0) 825000000',
                'end_location_hours' => '7:30 AM - 11:59 PM',
                'end_location_name' => 'Nice Cote d\'Azur Airport',
                'end_location_phone' => '+33 (0) 825800000',
                'car_description' => 'Compact, 5 Doors, 5 Seats, Luggage Capacity: 3, Automatic Transmission, Minimum age: 18 years',
                'car_type' => 'VW T-CROSS 1.0 TSI 110CH AUTO (CDAR) or similar',
            ],
            self::CATEGORY_CRUISE => [
                'id' => 40,
                'trip_id' => 4,
                'booking_date' => '2023-06-13',
                'booking_site_conf_num' => '14700-00',
                'booking_site_name' => 'Silversea',
                'booking_site_phone' => '+377 9700 0000',
                'booking_site_url' => 'http://www.silversea.com/',
                'supplier_conf_num' => 'SM220700000',
                'supplier_name' => 'Silversea',
                'supplier_phone' => '',
                'supplier_url' => '',
                'Agency' => [
                    'agency_name' => 'Frosch Mann Travels',
                    'agency_phone' => '+1 7045000000',
                ],
                'Segment' => [
                    [
                        'StartDateTime' => [
                            'date' => '2023-07-14',
                            'time' => '19:00:00',
                            'timezone' => 'America/Anchorage',
                            'utc_offset' => '-08:00',
                        ],
                        'LocationAddress' => [
                            'address' => 'Seward (Anchorage, Alaska), USA',
                            'city' => 'Anchorage',
                            'state' => 'AK',
                            'country' => 'US',
                            'latitude' => '60.961790',
                            'longitude' => '-149.431623',
                        ],
                        'location_name' => 'Seward (Anchorage, Alaska), USA',
                    ],
                    [
                        'StartDateTime' => [
                            'date' => '2023-07-15',
                            'time' => '13:30:00',
                            'timezone' => 'America/Anchorage',
                            'utc_offset' => '-08:00',
                        ],
                        'EndDateTime' => [
                            'date' => '2023-07-15',
                            'time' => '17:30:00',
                            'timezone' => 'America/Anchorage',
                            'utc_offset' => '-08:00',
                        ],
                        'LocationAddress' => [
                            'address' => 'Cruise Hubbard Glacier, Alaska, USA',
                            'city' => 'Yakutat City And Borough',
                            'state' => 'AK',
                            'country' => 'US',
                            'latitude' => '60.313889',
                            'longitude' => '-139.370833',
                        ],
                        'location_name' => 'Cruise Hubbard Glacier, Alaska, USA',
                        'detail_type_code' => 'P',
                    ],
                    [
                        'StartDateTime' => [
                            'date' => '2023-07-16',
                            'time' => '09:30:00',
                            'timezone' => 'America/Juneau',
                            'utc_offset' => '-08:00',
                        ],
                        'EndDateTime' => [
                            'date' => '2023-07-16',
                            'time' => '22:00:00',
                            'timezone' => 'America/Juneau',
                            'utc_offset' => '-08:00',
                        ],
                        'LocationAddress' => [
                            'address' => '58.300493, -134.420131',
                        ],
                        'location_name' => 'Juneau (Alaska), USA',
                        'detail_type_code' => 'P',
                    ],
                    [
                        'StartDateTime' => [
                            'date' => '2023-07-17',
                            'time' => '07:00:00',
                            'timezone' => 'America/Juneau',
                            'utc_offset' => '-08:00',
                        ],
                        'EndDateTime' => [
                            'date' => '2023-07-17',
                            'time' => '16:30:00',
                            'timezone' => 'America/Juneau',
                            'utc_offset' => '-08:00',
                        ],
                        'LocationAddress' => [
                            'address' => 'Skagway (Alaska), USA',
                            'city' => 'Skagway',
                            'state' => 'AK',
                            'zip' => '99840',
                            'country' => 'US',
                            'latitude' => '59.457178',
                            'longitude' => '-135.314535',
                        ],
                        'location_name' => 'Skagway (Alaska), USA',
                        'detail_type_code' => 'P',
                    ],
                    [
                        'StartDateTime' => [
                            'date' => '2023-07-18',
                            'time' => '09:00:00',
                            'timezone' => 'America/Sitka',
                            'utc_offset' => '-08:00',
                        ],
                        'EndDateTime' => [
                            'date' => '2023-07-18',
                            'time' => '16:00:00',
                            'timezone' => 'America/Sitka',
                            'utc_offset' => '-08:00',
                        ],
                        'LocationAddress' => [
                            'address' => 'Sitka (Alaska), USA',
                            'city' => 'Sitka',
                            'state' => 'AK',
                            'country' => 'US',
                            'latitude' => '57.053219',
                            'longitude' => '-135.334551',
                        ],
                        'location_name' => 'Sitka (Alaska), USA',
                        'detail_type_code' => 'P',
                    ],
                    [
                        'StartDateTime' => [
                            'date' => '2023-07-19',
                            'time' => '08:00:00',
                            'timezone' => 'America/Sitka',
                            'utc_offset' => '-08:00',
                        ],
                        'EndDateTime' => [
                            'date' => '2023-07-19',
                            'time' => '15:30:00',
                            'timezone' => 'America/Sitka',
                            'utc_offset' => '-08:00',
                        ],
                        'LocationAddress' => [
                            'address' => 'Ketchikan, USA',
                            'city' => 'Ketchikan',
                            'state' => 'AK',
                            'zip' => '99901',
                            'country' => 'US',
                            'latitude' => '55.342222',
                            'longitude' => '-131.646111',
                        ],
                        'location_name' => 'Ketchikan, USA',
                        'detail_type_code' => 'P',
                    ],
                    [
                        'StartDateTime' => [
                            'date' => '2023-07-20',
                            'timezone' => 'America/Metlakatla',
                        ],
                        'EndDateTime' => [
                            'date' => '2023-07-20',
                            'timezone' => 'America/Metlakatla',
                        ],
                        'LocationAddress' => [
                            'address' => 'Cruise Inside Passage, USA',
                            'city' => 'Ketchikan Gateway Borough',
                            'state' => 'AK',
                            'country' => 'US',
                            'latitude' => '54.800685',
                            'longitude' => '-131.440430',
                        ],
                        'location_name' => 'Cruise Inside Passage, USA',
                        'detail_type_code' => 'P',
                    ],
                    [
                        'StartDateTime' => [
                            'date' => '2023-07-21',
                            'time' => '07:30:00',
                            'timezone' => 'America/Vancouver',
                            'utc_offset' => '-07:00',
                        ],
                        'LocationAddress' => [
                            'address' => 'Vancouver, Canada',
                            'city' => 'Vancouver',
                            'state' => 'BC',
                            'country' => 'CA',
                            'latitude' => '49.249660',
                            'longitude' => '-123.119340',
                        ],
                        'location_name' => 'Vancouver, Canada',
                    ],
                ],
                'Traveler' => [
                    [
                        'first_name' => 'Tomoya',
                        'last_name' => 'Okazaki',
                    ],
                    [
                        'first_name' => 'Nagisa',
                        'last_name' => 'Furukawa',
                    ],
                ],
                'cabin_number' => '701',
                'cabin_type' => 'Royal-1Br',
                'ship_name' => 'Silver Muse',
            ],
            self::CATEGORY_LODGING => [
                'id' => 50,
                'trip_id' => 5,
                'booking_date' => '2023-08-05',
                'booking_site_conf_num' => '96440000',
                'booking_site_name' => 'Marriott',
                'booking_site_phone' => '1-877-460-0000',
                'booking_site_url' => 'https://www.marriott.com/',
                'supplier_conf_num' => '96440000',
                'supplier_name' => 'Aloft Reno-Tahoe International Airport',
                'supplier_phone' => '+1-775-500-0000',
                'supplier_url' => '',
                'restrictions' => 'You may cancel your reservation for no charge before 11:59 PM local hotel time on Friday, August 26, 2023 (1 day[s] before arrival).',
                'total_cost' => '270.00 USD',
                'StartDateTime' => [
                    'date' => '2023-08-27',
                    'time' => '14:00:00',
                    'timezone' => 'America/Los_Angeles',
                    'utc_offset' => '-07:00',
                ],
                'EndDateTime' => [
                    'date' => '2023-08-28',
                    'time' => '12:00:00',
                    'timezone' => 'America/Los_Angeles',
                    'utc_offset' => '-07:00',
                ],
                'Address' => [
                    'address' => '2015 Terminal Way Reno Nevada 89502 USA',
                    'city' => 'Reno',
                    'state' => 'NV',
                    'zip' => '89502',
                    'country' => 'US',
                    'latitude' => '39.505745',
                    'longitude' => '-119.778692',
                ],
                'Guest' => [
                    'first_name' => 'Fuuko',
                    'last_name' => 'Ibuki',
                    'frequent_traveler_num' => 'XXXXX3000',
                    'frequent_traveler_supplier' => 'Aloft Reno-Tahoe International Airport',
                ],
                'number_guests' => '2',
                'number_rooms' => '1',
                'room_type' => '1 King Bed, Aloft Room',
            ],
            self::CATEGORY_PARKING => [
                'id' => 60,
                'trip_id' => 6,
                'booking_date' => '2023-08-06',
                'booking_site_conf_num' => '9780000',
                'booking_site_name' => 'AirportParkingReservations.com',
                'booking_site_phone' => '',
                'booking_site_url' => 'http://www.airportparkingreservations.com/',
                'supplier_conf_num' => '9780000',
                'supplier_name' => 'SmartPark JFK',
                'supplier_phone' => '535-0000',
                'supplier_url' => '',
                'total_cost' => '$113.00',
                'StartDateTime' => [
                    'date' => '2023-08-31',
                    'time' => '06:00:00',
                    'timezone' => 'America/New_York',
                    'utc_offset' => '-04:00',
                ],
                'EndDateTime' => [
                    'date' => '2023-09-03',
                    'time' => '23:30:00',
                    'timezone' => 'America/New_York',
                    'utc_offset' => '-04:00',
                ],
                'Address' => [
                    'address' => '123 - 10 South Conduit Avenue, South Ozone Park, NY 11420',
                    'city' => 'Queens',
                    'state' => 'NY',
                    'zip' => '11420',
                    'country' => 'US',
                    'latitude' => '40.664455',
                    'longitude' => '-73.818156',
                ],
                'location_name' => 'SmartPark JFK',
                'location_phone' => '535-0000',
            ],
            self::CATEGORY_RAIL => [
                'id' => 70,
                'trip_id' => 7,
                'booking_date' => '2023-09-07',
                'booking_site_conf_num' => '23SVI00000',
                'booking_site_name' => 'LNER',
                'booking_site_phone' => '03457 200 000',
                'booking_site_url' => 'https://www.lner.co.uk/',
                'supplier_conf_num' => '',
                'supplier_name' => '',
                'supplier_phone' => '',
                'supplier_url' => '',
                'total_cost' => '£26.00',
                'Segment' => [
                    'StartDateTime' => [
                        'date' => '2023-09-20',
                        'time' => '12:50:00',
                        'timezone' => 'Europe/London',
                        'utc_offset' => '+01:00',
                    ],
                    'EndDateTime' => [
                        'date' => '2023-09-20',
                        'time' => '14:40:00',
                        'timezone' => 'Europe/London',
                        'utc_offset' => '+01:00',
                    ],
                    'StartStationAddress' => [
                        'address' => 'London Kings Cross',
                        'city' => 'Greater London',
                        'state' => 'England',
                        'country' => 'GB',
                        'latitude' => '51.534749',
                        'longitude' => '-0.124585',
                    ],
                    'EndStationAddress' => [
                        'address' => 'York',
                        'city' => 'York',
                        'state' => 'England',
                        'country' => 'GB',
                        'latitude' => '53.957630',
                        'longitude' => '-1.082710',
                    ],
                    'start_station_name' => 'London Kings Cross',
                    'end_station_name' => 'York',
                    'seats' => '41',
                    'service_class' => 'Advance Single',
                ],
                'Traveler' => [
                    'first_name' => 'Kotomi',
                    'last_name' => 'Ichinose',
                ],
            ],
            self::CATEGORY_RESTAURANT => [
                'id' => 80,
                'trip_id' => 8,
                'booking_date' => '2023-09-08',
                'booking_site_conf_num' => '',
                'booking_site_name' => 'OpenTable',
                'booking_site_phone' => '',
                'booking_site_url' => 'http://www.opentable.com/',
                'supplier_conf_num' => '45300',
                'supplier_name' => '1837 Bar & Brasserie at Guinness Storehouse',
                'supplier_phone' => '086 820 0000',
                'supplier_url' => '',
                'DateTime' => [
                    'date' => '2023-09-19',
                    'time' => '15:00:00',
                    'timezone' => 'Europe/Dublin',
                    'utc_offset' => '+01:00',
                ],
                'Address' => [
                    'address' => 'Saint James\'s Gate, Dublin, Co Dublin D08 VF8H',
                    'city' => 'Dublin',
                    'state' => 'County Dublin',
                    'country' => 'IE',
                    'latitude' => '53.343344',
                    'longitude' => '-6.286993',
                ],
                'ReservationHolder' => [
                    'first_name' => 'Miyazawa',
                    'last_name' => 'Yukine',
                ],
                'number_patrons' => '3',
            ],
            self::CATEGORY_TRANSPORT => [
                [
                    'id' => 90,
                    'trip_id' => 9,
                    'booking_date' => '2023-09-09',
                    'booking_site_conf_num' => 'DFP160000000',
                    'booking_site_name' => 'Direct Ferries',
                    'booking_site_phone' => '',
                    'booking_site_url' => 'http://www.directferries.com/',
                    'supplier_conf_num' => 'GR23010000000',
                    'supplier_name' => 'Caronte & Tourist',
                    'supplier_phone' => '',
                    'supplier_url' => '',
                    'total_cost' => 'EUR 41.5',
                    'Segment' => [
                        'StartDateTime' => [
                            'date' => '2023-09-16',
                            'time' => '10:15:00',
                            'timezone' => 'Europe/Rome',
                            'utc_offset' => '+02:00',
                        ],
                        'EndDateTime' => [
                            'date' => '2023-09-16',
                            'time' => '10:35:00',
                            'timezone' => 'Europe/Rome',
                            'utc_offset' => '+02:00',
                        ],
                        'StartLocationAddress' => [
                            'address' => 'Villa San Giovanni',
                            'city' => 'Villa San Giovanni',
                            'state' => '78',
                            'country' => 'IT',
                            'latitude' => '38.219910',
                            'longitude' => '15.636890',
                        ],
                        'EndLocationAddress' => [
                            'address' => 'Messina: Caronte & Tourist',
                            'city' => 'Messina',
                            'state' => 'Sicily',
                            'country' => 'IT',
                            'latitude' => '38.193734',
                            'longitude' => '15.554206',
                        ],
                        'start_location_name' => 'Villa San Giovanni',
                        'end_location_name' => 'Messina: Caronte & Tourist',
                        'detail_type_code' => 'F',
                        'carrier_name' => 'Caronte & Tourist',
                    ],
                    'Traveler' => [
                        [
                            'first_name' => 'Youhei',
                            'last_name' => 'Sunohara',
                        ],
                        [
                            'first_name' => 'Mei',
                            'last_name' => 'Sunohara',
                        ],
                    ],
                ],
                [
                    'id' => 100,
                    'trip_id' => 10,
                    'booking_date' => '2023-09-10',
                    'booking_site_conf_num' => '310 620 0000',
                    'booking_site_name' => 'FlixBus',
                    'booking_site_phone' => '+1 (855) 620-0000',
                    'booking_site_url' => 'https://www.flixbus.com/',
                    'supplier_conf_num' => '310 620 0000',
                    'supplier_name' => 'FlixBus',
                    'supplier_phone' => '+1 (855) 620-0000',
                    'supplier_url' => 'https://www.flixbus.com/',
                    'total_cost' => 'EUR 19.90',
                    'Segment' => [
                        'StartDateTime' => [
                            'date' => '2023-10-13',
                            'time' => '14:00:00',
                            'timezone' => 'Europe/Berlin',
                            'utc_offset' => '+02:00',
                        ],
                        'EndDateTime' => [
                            'date' => '2023-10-13',
                            'time' => '18:40:00',
                            'timezone' => 'Europe/Prague',
                            'utc_offset' => '+02:00',
                        ],
                        'StartLocationAddress' => [
                            'address' => 'Masurenallee 4-6, 14057 Berlin',
                            'city' => 'Berlin',
                            'state' => 'BE',
                            'zip' => '14057',
                            'country' => 'DE',
                            'latitude' => '52.507126',
                            'longitude' => '13.279384',
                        ],
                        'EndLocationAddress' => [
                            'address' => '(Praha, ÚAN Florenc) Křižíkova 2110/2b, 186 00 Praha',
                            'city' => 'Praha',
                            'state' => 'Hlavní Město Praha',
                            'zip' => '186 00',
                            'country' => 'CZ',
                            'latitude' => '50.089377',
                            'longitude' => '14.439707',
                        ],
                        'start_location_name' => 'Berlin central bus station',
                        'end_location_name' => 'Prague (Central Bus Station Florenc)',
                        'detail_type_code' => 'G',
                        'vehicle_description' => 'Bus 060',
                    ],
                    'Traveler' => [
                        'first_name' => 'Kouko',
                        'last_name' => 'Ibuki',
                    ],
                ],
            ],
        ];
    }

    /**
     * Массив некорректных резерваций, в которых присутствуют не все обязательные параметры.
     */
    private static function getIncorrectTripsArray(): array
    {
        $data = self::getTripsArray();
        unset($data[self::CATEGORY_ACTIVITY]['StartDateTime']);
        unset($data[self::CATEGORY_ACTIVITY]['Address']['address']);
        unset($data[self::CATEGORY_AIR]['Segment'][0]['EndDateTime']);
        unset($data[self::CATEGORY_AIR]['Segment'][0]['end_airport_code']);
        unset($data[self::CATEGORY_AIR]['Segment'][1]['EndDateTime']);
        unset($data[self::CATEGORY_AIR]['Segment'][1]['end_airport_code']);
        unset($data[self::CATEGORY_CAR]['EndDateTime']);
        unset($data[self::CATEGORY_CAR]['EndLocationAddress']['address']);
        unset($data[self::CATEGORY_CRUISE]['Segment'][1]['StartDateTime']);
        unset($data[self::CATEGORY_CRUISE]['Segment'][1]['LocationAddress']['address']);
        unset($data[self::CATEGORY_CRUISE]['Segment'][1]['location_name']);
        unset($data[self::CATEGORY_CRUISE]['Segment'][2]['StartDateTime']);
        unset($data[self::CATEGORY_CRUISE]['Segment'][2]['LocationAddress']['address']);
        unset($data[self::CATEGORY_CRUISE]['Segment'][2]['location_name']);
        unset($data[self::CATEGORY_LODGING]['EndDateTime']);
        unset($data[self::CATEGORY_LODGING]['Address']['address']);
        unset($data[self::CATEGORY_PARKING]['EndDateTime']);
        unset($data[self::CATEGORY_RAIL]['Segment']['EndDateTime']);
        unset($data[self::CATEGORY_RAIL]['Segment']['StartStationAddress']['address']);
        unset($data[self::CATEGORY_RAIL]['Segment']['EndStationAddress']['address']);
        unset($data[self::CATEGORY_RESTAURANT]['DateTime']);
        unset($data[self::CATEGORY_RESTAURANT]['Address']['address']);
        // Ferry
        unset($data[self::CATEGORY_TRANSPORT][0]['Segment']['EndDateTime']['time']);
        unset($data[self::CATEGORY_TRANSPORT][0]['Segment']['EndLocationAddress']['address']);
        unset($data[self::CATEGORY_TRANSPORT][0]['Segment']['end_location_name']);
        // Bus
        unset($data[self::CATEGORY_TRANSPORT][1]['Segment']['EndDateTime']['time']);
        unset($data[self::CATEGORY_TRANSPORT][1]['Segment']['EndLocationAddress']['address']);
        unset($data[self::CATEGORY_TRANSPORT][1]['Segment']['end_location_name']);

        return $data;
    }
}
