<?php

namespace AwardWallet\MainBundle\Service\Tripit\Converter;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\Tripit\Serializer\ParkingObject;

/**
 * @NoDI()
 */
class Parking extends BaseConverter
{
    private ParkingObject $data;

    public function __construct(ParkingObject $data)
    {
        $this->data = $data;
    }

    public function run()
    {
        /** @var \AwardWallet\Schema\Itineraries\Parking $parking */
        $parking = $this->toObject($this->fields());

        if ($this->data->getSupplierConfNum() !== null) {
            $parking->confirmationNumbers[] = $this->toObject($this->confirmationNumber());
        }

        return $parking;
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

    private function fields(): array
    {
        $address = $this->data->getAddress();

        if ($this->data->getEndDateTime()->getDate() === null) {
            $this->data->getEndDateTime()->setDate($this->data->getStartDateTime()->getDate());
        }

        if ($this->data->getStartDateTime()->getTime() === null) {
            $this->data->getStartDateTime()->setTime('12:00:00');
        }

        if ($this->data->getEndDateTime()->getTime() === null) {
            $this->data->getEndDateTime()->setTime('12:00:00');
        }

        return [
            'class' => \AwardWallet\Schema\Itineraries\Parking::class,
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
                'address' => [
                    'class' => \AwardWallet\Schema\Itineraries\Address::class,
                    'data' => [
                        'text' => $address->getAddress(),
                        'city' => $address->getCity(),
                        'stateName' => $address->getState(),
                        'postalCode' => $address->getZip(),
                        'countryCode' => $address->getCountry(),
                        'lat' => $address->getLatitude(),
                        'lng' => $address->getLongitude(),
                        'timezoneId' => $this->data->getStartDateTime()->getTimezone(),
                    ],
                ],
                'startDateTime' => $this->getDateTimeRFC3339($this->data->getStartDateTime()),
                'endDateTime' => $this->getDateTimeRFC3339($this->data->getEndDateTime()),
                'phone' => $this->data->getSupplierPhone(),
                'companyName' => $this->data->getSupplierName(),
            ],
        ];
    }
}
