<?php

namespace AwardWallet\MainBundle\Service\Tripit\Converter;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\Tripit\Serializer\AirObject;
use AwardWallet\MainBundle\Service\Tripit\Serializer\AirSegmentObject;
use AwardWallet\MainBundle\Service\Tripit\Serializer\TravelerObject;

/**
 * @NoDI()
 */
class Air extends BaseConverter
{
    private AirObject $data;

    public function __construct(AirObject $data)
    {
        $this->data = $data;
    }

    public function run()
    {
        /** @var \AwardWallet\Schema\Itineraries\Flight $flight */
        $flight = $this->toObject($this->fields());

        foreach ($this->data->getTraveler() as $traveler) {
            $flight->travelers[] = $this->toObject($this->traveler($traveler));
        }

        foreach ($this->data->getSegment() as $segment) {
            $flight->segments[] = $this->toObject($this->segment($segment));
        }

        return $flight;
    }

    private function traveler(TravelerObject $data): array
    {
        return [
            'class' => \AwardWallet\Schema\Itineraries\Person::class,
            'data' => [
                'name' => $data->getFullName(),
            ],
        ];
    }

    private function segment(AirSegmentObject $data): array
    {
        $marketingCarrier = [
            'class' => \AwardWallet\Schema\Itineraries\MarketingCarrier::class,
            'data' => [
                'airline' => [
                    'class' => \AwardWallet\Schema\Itineraries\Airline::class,
                    'data' => [
                        'name' => $data->getMarketingAirline(),
                        'iata' => $data->getMarketingAirlineCode(),
                    ],
                ],
                'flightNumber' => $data->getMarketingFlightNumber(),
            ],
        ];

        if ($this->data->getSupplierConfNum() !== null) {
            $marketingCarrier['data']['confirmationNumber'] = $this->data->getSupplierConfNum();
        }

        return [
            'class' => \AwardWallet\Schema\Itineraries\FlightSegment::class,
            'data' => [
                'departure' => [
                    'class' => \AwardWallet\Schema\Itineraries\TripLocation::class,
                    'data' => [
                        'airportCode' => $data->getStartAirportCode(),
                        'terminal' => $data->getStartTerminal(),
                        'name' => $data->getStartAirportCode(),
                        'localDateTime' => $this->getDateTimeRFC3339($data->getStartDateTime()),
                        'address' => [
                            'class' => \AwardWallet\Schema\Itineraries\Address::class,
                            'data' => [
                                'text' => $data->getStartAirportCode(),
                                'lat' => $data->getStartAirportLatitude(),
                                'lng' => $data->getStartAirportLongitude(),
                                'city' => $data->getStartCityName(),
                                'countryCode' => $data->getStartCountryCode(),
                                'timezoneId' => $data->getStartDateTime()->getTimezone(),
                            ],
                        ],
                    ],
                ],
                'arrival' => [
                    'class' => \AwardWallet\Schema\Itineraries\TripLocation::class,
                    'data' => [
                        'airportCode' => $data->getEndAirportCode(),
                        'terminal' => $data->getEndTerminal(),
                        'name' => $data->getEndAirportCode(),
                        'localDateTime' => $this->getDateTimeRFC3339($data->getEndDateTime()),
                        'address' => [
                            'class' => \AwardWallet\Schema\Itineraries\Address::class,
                            'data' => [
                                'text' => $data->getEndAirportCode(),
                                'lat' => $data->getEndAirportLatitude(),
                                'lng' => $data->getEndAirportLongitude(),
                                'city' => $data->getEndCityName(),
                                'countryCode' => $data->getEndCountryCode(),
                                'timezoneId' => $data->getEndDateTime()->getTimezone(),
                            ],
                        ],
                    ],
                ],
                'marketingCarrier' => $marketingCarrier,
                'aircraft' => [
                    'class' => \AwardWallet\Schema\Itineraries\Aircraft::class,
                    'data' => [
                        'iataCode' => $data->getAircraft(),
                        'name' => $data->getAircraftDisplayName(),
                    ],
                ],
                'traveledMiles' => $data->getDistance(),
                'duration' => $data->getDuration(),
            ],
        ];
    }

    private function fields(): array
    {
        return [
            'class' => \AwardWallet\Schema\Itineraries\Flight::class,
            'data' => [
                'providerInfo' => [
                    'class' => \AwardWallet\Schema\Itineraries\ProviderInfo::class,
                    'data' => [
                        'name' => $this->data->getBookingSiteName(),
                    ],
                ],
            ],
        ];
    }
}
