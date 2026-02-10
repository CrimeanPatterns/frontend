<?php

namespace AwardWallet\MainBundle\Service\Tripit\Converter;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\Tripit\Serializer\CarObject;

/**
 * @NoDI()
 */
class Car extends BaseConverter
{
    private CarObject $data;

    public function __construct(CarObject $data)
    {
        $this->data = $data;
    }

    public function run()
    {
        /** @var \AwardWallet\Schema\Itineraries\CarRental $carRental */
        $carRental = $this->toObject($this->fields());

        if ($this->data->getSupplierConfNum() !== null) {
            $carRental->confirmationNumbers[] = $this->toObject($this->confirmationNumber());
        }

        if ($this->data->getDriver() !== null) {
            $carRental->providerInfo->accountNumbers[] = $this->toObject($this->accountNumber());
        }

        return $carRental;
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

    private function accountNumber(): array
    {
        return [
            'class' => \AwardWallet\Schema\Itineraries\ParsedNumber::class,
            'data' => [
                'number' => $this->data->getDriver()->getFrequentTravelerNum(),
            ],
        ];
    }

    private function fields(): array
    {
        $startAddress = $this->data->getStartLocationAddress();
        $endAddress = $this->data->getEndLocationAddress();

        if ($this->data->getStartDateTime()->getTime() === null) {
            $this->data->getStartDateTime()->setTime('12:00:00');
        }

        if ($this->data->getEndDateTime()->getTime() === null) {
            $this->data->getEndDateTime()->setTime('12:00:00');
        }

        $carRental = [
            'class' => \AwardWallet\Schema\Itineraries\CarRental::class,
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
                'cancellationPolicy' => $this->data->getRestrictions(),
                'pickup' => [
                    'class' => \AwardWallet\Schema\Itineraries\CarRentalLocation::class,
                    'data' => [
                        'address' => [
                            'class' => \AwardWallet\Schema\Itineraries\Address::class,
                            'data' => [
                                'text' => $startAddress->getAddress(),
                                'city' => $startAddress->getCity(),
                                'stateName' => $startAddress->getState(),
                                'countryCode' => $startAddress->getCountry(),
                                'postalCode' => $startAddress->getZip(),
                                'lat' => $startAddress->getLatitude(),
                                'lng' => $startAddress->getLongitude(),
                                'timezoneId' => $this->data->getStartDateTime()->getTimezone(),
                            ],
                        ],
                        'localDateTime' => $this->getDateTimeRFC3339($this->data->getStartDateTime()),
                        'openingHours' => $this->data->getStartLocationHours(),
                        'phone' => $this->data->getStartLocationPhone(),
                    ],
                ],
                'dropoff' => [
                    'class' => \AwardWallet\Schema\Itineraries\CarRentalLocation::class,
                    'data' => [
                        'address' => [
                            'class' => \AwardWallet\Schema\Itineraries\Address::class,
                            'data' => [
                                'text' => $endAddress->getAddress(),
                                'city' => $endAddress->getCity(),
                                'stateName' => $endAddress->getState(),
                                'countryCode' => $endAddress->getCountry(),
                                'postalCode' => $endAddress->getZip(),
                                'lat' => $endAddress->getLatitude(),
                                'lng' => $endAddress->getLongitude(),
                                'timezoneId' => $this->data->getEndDateTime()->getTimezone(),
                            ],
                        ],
                        'localDateTime' => $this->getDateTimeRFC3339($this->data->getEndDateTime()),
                        'openingHours' => $this->data->getEndLocationHours(),
                        'phone' => $this->data->getEndLocationPhone(),
                    ],
                ],
                'car' => [
                    'class' => \AwardWallet\Schema\Itineraries\Car::class,
                    'data' => [
                        'type' => $this->data->getCarDescription(),
                        'model' => $this->data->getCarType(),
                    ],
                ],
                'rentalCompany' => $this->data->getBookingSiteName(),
            ],
        ];

        if ($this->data->getDriver() !== null) {
            $carRental['data']['driver'] = [
                'class' => \AwardWallet\Schema\Itineraries\Person::class,
                'data' => [
                    'name' => $this->data->getDriver()->getFirstName() . ' ' . $this->data->getDriver()->getLastName(),
                ],
            ];
        }

        return $carRental;
    }
}
