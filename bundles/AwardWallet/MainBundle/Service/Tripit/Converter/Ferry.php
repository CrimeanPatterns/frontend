<?php

namespace AwardWallet\MainBundle\Service\Tripit\Converter;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\Tripit\Serializer\FerryObject;
use AwardWallet\MainBundle\Service\Tripit\Serializer\FerrySegmentObject;
use AwardWallet\MainBundle\Service\Tripit\Serializer\TravelerObject;

/**
 * @NoDI()
 */
class Ferry extends BaseConverter
{
    private FerryObject $data;

    public function __construct(FerryObject $data)
    {
        $this->data = $data;
    }

    public function run()
    {
        /** @var \AwardWallet\Schema\Itineraries\Ferry $ferry */
        $ferry = $this->toObject($this->fields());

        if ($this->data->getConfirmationNumber() !== null) {
            $ferry->confirmationNumbers[] = $this->toObject($this->confirmationNumber());
        }

        foreach ($this->data->getTraveler() as $traveler) {
            $ferry->travelers[] = $this->toObject($this->traveler($traveler));
        }

        foreach ($this->data->getSegment() as $segment) {
            $ferry->segments[] = $this->toObject($this->segment($segment));
        }

        return $ferry;
    }

    private function confirmationNumber(): array
    {
        return [
            'class' => \AwardWallet\Schema\Itineraries\ConfNo::class,
            'data' => [
                'number' => $this->data->getConfirmationNumber(),
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

    private function segment(FerrySegmentObject $data): array
    {
        return [
            'class' => \AwardWallet\Schema\Itineraries\FerrySegment::class,
            'data' => [
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
                'carrier' => $this->data->getSupplierName(),
            ],
        ];
    }

    private function fields(): array
    {
        return [
            'class' => \AwardWallet\Schema\Itineraries\Ferry::class,
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
    }
}
