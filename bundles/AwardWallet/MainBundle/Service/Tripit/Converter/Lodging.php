<?php

namespace AwardWallet\MainBundle\Service\Tripit\Converter;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\Tripit\Serializer\GuestObject;
use AwardWallet\MainBundle\Service\Tripit\Serializer\LodgingObject;

/**
 * @NoDI()
 */
class Lodging extends BaseConverter
{
    private LodgingObject $data;

    public function __construct(LodgingObject $data)
    {
        $this->data = $data;
    }

    public function run()
    {
        /** @var \AwardWallet\Schema\Itineraries\HotelReservation $hotel */
        $hotel = $this->toObject($this->fields());

        if ($this->data->getSupplierConfNum() !== null) {
            $hotel->confirmationNumbers[] = $this->toObject($this->confirmationNumber());
        }

        foreach ($this->data->getGuest() as $guest) {
            $hotel->guests[] = $this->toObject($this->guest($guest));
        }

        $hotel->rooms[] = $this->toObject($this->room());

        return $hotel;
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

    private function guest(GuestObject $data): array
    {
        return [
            'class' => \AwardWallet\Schema\Itineraries\Person::class,
            'data' => [
                'name' => $data->getFirstName() . ' ' . $data->getLastName(),
            ],
        ];
    }

    private function room(): array
    {
        return [
            'class' => \AwardWallet\Schema\Itineraries\Room::class,
            'data' => [
                'type' => $this->data->getRoomType(),
            ],
        ];
    }

    private function fields(): array
    {
        $address = $this->data->getAddress();

        if ($this->data->getStartDateTime()->getTime() === null) {
            $this->data->getStartDateTime()->setTime('15:00:00');
        }

        if ($this->data->getEndDateTime()->getTime() === null) {
            $this->data->getEndDateTime()->setTime('11:00:00');
        }

        return [
            'class' => \AwardWallet\Schema\Itineraries\HotelReservation::class,
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
                'hotelName' => $this->data->getSupplierName(),
                'address' => [
                    'class' => \AwardWallet\Schema\Itineraries\Address::class,
                    'data' => [
                        'text' => $address->getAddress(),
                        'lat' => $address->getLatitude(),
                        'lng' => $address->getLongitude(),
                        'city' => $address->getCity(),
                        'stateName' => $address->getState(),
                        'postalCode' => $address->getZip(),
                        'countryCode' => $address->getCountry(),
                        'timezoneId' => $this->data->getStartDateTime()->getTimezone(),
                    ],
                ],
                'checkInDate' => $this->getDateTimeRFC3339($this->data->getStartDateTime()),
                'checkOutDate' => $this->getDateTimeRFC3339($this->data->getEndDateTime()),
                'phone' => $this->data->getSupplierPhone(),
                'guestCount' => $this->data->getNumberGuests(),
                'roomsCount' => $this->data->getNumberRooms(),
            ],
        ];
    }
}
