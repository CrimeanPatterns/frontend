<?php

namespace AwardWallet\MainBundle\Service\Tripit\Converter;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\Tripit\Serializer\BusObject;
use AwardWallet\MainBundle\Service\Tripit\Serializer\BusSegmentObject;
use AwardWallet\MainBundle\Service\Tripit\Serializer\TravelerObject;

/**
 * @NoDI()
 */
class Bus extends BaseConverter
{
    private BusObject $data;

    public function __construct(BusObject $data)
    {
        $this->data = $data;
    }

    public function run()
    {
        /** @var \AwardWallet\Schema\Itineraries\Bus $bus */
        $bus = $this->toObject($this->fields());

        if ($this->data->getSupplierConfNum() !== null) {
            $bus->confirmationNumbers[] = $this->toObject($this->confirmationNumber());
        }

        foreach ($this->data->getTraveler() as $traveler) {
            $bus->travelers[] = $this->toObject($this->traveler($traveler));
        }

        foreach ($this->data->getSegment() as $segment) {
            $bus->segments[] = $this->toObject($this->segment($segment));
        }

        return $bus;
    }

    private function confirmationNumber(): array
    {
        return [
            'class' => \AwardWallet\Schema\Itineraries\ConfNo::class,
            'data' => [
                'number' => $this->data->getSupplierConfNum(),
            ],
        ];
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

    private function segment(BusSegmentObject $data): array
    {
        return [
            'class' => \AwardWallet\Schema\Itineraries\BusSegment::class,
            'data' => [
                'scheduleNumber' => $data->getVehicleDescription(),
                'departure' => [
                    'class' => \AwardWallet\Schema\Itineraries\TransportLocation::class,
                    'data' => [
                        'name' => $data->getStartLocationName(),
                        'localDateTime' => $this->getDateTimeRFC3339($data->getStartDateTime()),
                        'address' => [
                            'class' => \AwardWallet\Schema\Itineraries\Address::class,
                            'data' => [
                                'text' => $data->getStartLocationAddress()->getAddress(),
                                'city' => $data->getStartLocationAddress()->getCity(),
                                'stateName' => $data->getStartLocationAddress()->getState(),
                                'countryCode' => $data->getStartLocationAddress()->getCountry(),
                                'postalCode' => $data->getStartLocationAddress()->getZip(),
                                'lat' => $data->getStartLocationAddress()->getLatitude(),
                                'lng' => $data->getStartLocationAddress()->getLongitude(),
                                'timezoneId' => $data->getStartDateTime()->getTimezone(),
                            ],
                        ],
                    ],
                ],
                'arrival' => [
                    'class' => \AwardWallet\Schema\Itineraries\TransportLocation::class,
                    'data' => [
                        'name' => $data->getEndLocationName(),
                        'localDateTime' => $this->getDateTimeRFC3339($data->getEndDateTime()),
                        'address' => [
                            'class' => \AwardWallet\Schema\Itineraries\Address::class,
                            'data' => [
                                'text' => $data->getEndLocationAddress()->getAddress(),
                                'city' => $data->getEndLocationAddress()->getCity(),
                                'stateName' => $data->getEndLocationAddress()->getState(),
                                'countryCode' => $data->getEndLocationAddress()->getCountry(),
                                'postalCode' => $data->getEndLocationAddress()->getZip(),
                                'lat' => $data->getEndLocationAddress()->getLatitude(),
                                'lng' => $data->getEndLocationAddress()->getLongitude(),
                                'timezoneId' => $data->getEndDateTime()->getTimezone(),
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function fields(): array
    {
        $bus = [
            'class' => \AwardWallet\Schema\Itineraries\Bus::class,
            'data' => [
                'travelAgency' => [
                    'class' => \AwardWallet\Schema\Itineraries\TravelAgency::class,
                    'data' => [
                        'providerInfo' => [
                            'class' => \AwardWallet\Schema\Itineraries\ProviderInfo::class,
                            'data' => [
                                'name' => $this->data->getBookingSiteName(),
                            ],
                        ],
                    ],
                ],
                'pricingInfo' => [
                    'class' => \AwardWallet\Schema\Itineraries\PricingInfo::class,
                    'data' => [
                        'total' => $this->getNumberFromString($this->data->getTotalCost()),
                    ],
                ],
            ],
        ];

        if ($this->data->getBookingSiteConfNum() !== null) {
            $bus['data']['travelAgency']['data']['confirmationNumbers'][] = $this->toObject([
                'class' => \AwardWallet\Schema\Itineraries\ConfNo::class,
                'data' => [
                    'number' => $this->data->getBookingSiteConfNum(),
                ],
            ]);
        }

        return $bus;
    }
}
