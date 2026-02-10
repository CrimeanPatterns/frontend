<?php

namespace AwardWallet\MainBundle\Service\Tripit\Converter;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\Tripit\Serializer\RailObject;
use AwardWallet\MainBundle\Service\Tripit\Serializer\RailSegmentObject;
use AwardWallet\MainBundle\Service\Tripit\Serializer\TravelerObject;

/**
 * @NoDI()
 */
class Rail extends BaseConverter
{
    private RailObject $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function run()
    {
        /** @var \AwardWallet\Schema\Itineraries\Train $train */
        $train = $this->toObject($this->fields());

        if ($this->data->getBookingSiteConfNum() !== null) {
            $train->confirmationNumbers[] = $this->toObject($this->confirmationNumber());
        }

        foreach ($this->data->getTraveler() as $traveler) {
            $train->travelers[] = $this->toObject($this->traveler($traveler));
        }

        foreach ($this->data->getSegment() as $segment) {
            $train->segments[] = $this->toObject($this->segment($segment));
        }

        return $train;
    }

    private function confirmationNumber(): array
    {
        return [
            'class' => \AwardWallet\Schema\Itineraries\ConfNo::class,
            'data' => [
                'number' => $this->data->getBookingSiteConfNum(),
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

    private function segment(RailSegmentObject $data): array
    {
        $startAddress = $data->getStartStationAddress();
        $endAddress = $data->getEndStationAddress();

        return [
            'class' => \AwardWallet\Schema\Itineraries\TrainSegment::class,
            'data' => [
                'departure' => [
                    'class' => \AwardWallet\Schema\Itineraries\TransportLocation::class,
                    'data' => [
                        'name' => $startAddress->getAddress(),
                        'localDateTime' => $this->getDateTimeRFC3339($data->getStartDateTime()),
                        'address' => [
                            'class' => \AwardWallet\Schema\Itineraries\Address::class,
                            'data' => [
                                'text' => $startAddress->getAddress(),
                                'city' => $startAddress->getCity(),
                                'stateName' => $startAddress->getState(),
                                'countryCode' => $startAddress->getCountry(),
                                'lat' => $startAddress->getLatitude(),
                                'lng' => $startAddress->getLongitude(),
                                'timezoneId' => $data->getStartDateTime()->getTimezone(),
                            ],
                        ],
                    ],
                ],
                'arrival' => [
                    'class' => \AwardWallet\Schema\Itineraries\TransportLocation::class,
                    'data' => [
                        'name' => $endAddress->getAddress(),
                        'localDateTime' => $this->getDateTimeRFC3339($data->getEndDateTime()),
                        'address' => [
                            'class' => \AwardWallet\Schema\Itineraries\Address::class,
                            'data' => [
                                'text' => $endAddress->getAddress(),
                                'city' => $endAddress->getCity(),
                                'stateName' => $endAddress->getState(),
                                'countryCode' => $endAddress->getCountry(),
                                'lat' => $endAddress->getLatitude(),
                                'lng' => $endAddress->getLongitude(),
                                'timezoneId' => $data->getEndDateTime()->getTimezone(),
                            ],
                        ],
                    ],
                ],
                'seats' => $data->getSeats() !== null ? [$data->getSeats()] : null,
            ],
        ];
    }

    private function fields(): array
    {
        return [
            'class' => \AwardWallet\Schema\Itineraries\Train::class,
            'data' => [
                'pricingInfo' => [
                    'class' => \AwardWallet\Schema\Itineraries\PricingInfo::class,
                    'data' => [
                        'total' => $this->getNumberFromString($this->data->getTotalCost()),
                    ],
                ],
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
