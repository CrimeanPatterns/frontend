<?php

namespace AwardWallet\MainBundle\Service\Tripit\Converter;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Entity\Restaurant as RestaurantEntity;
use AwardWallet\MainBundle\Service\Tripit\Serializer\GuestObject;
use AwardWallet\MainBundle\Service\Tripit\Serializer\RestaurantObject;

/**
 * @NoDI()
 */
class Restaurant extends BaseConverter
{
    private RestaurantObject $data;

    public function __construct(RestaurantObject $data)
    {
        $this->data = $data;
    }

    public function run()
    {
        /** @var \AwardWallet\Schema\Itineraries\Event $restaurant */
        $restaurant = $this->toObject($this->fields());

        if ($this->data->getSupplierConfNum() !== null) {
            $restaurant->confirmationNumbers[] = $this->toObject($this->confirmationNumber());
        }

        foreach ($this->data->getReservationHolder() as $guest) {
            $restaurant->guests[] = $this->toObject($this->guest($guest));
        }

        return $restaurant;
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

    private function fields(): array
    {
        $address = $this->data->getAddress();

        if ($this->data->getDateTime()->getTime() === null) {
            $this->data->getDateTime()->setTime('12:00:00');
        }

        return [
            'class' => \AwardWallet\Schema\Itineraries\Event::class,
            'data' => [
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
                        'countryCode' => $address->getCountry(),
                        'lat' => $address->getLatitude(),
                        'lng' => $address->getLongitude(),
                        'timezoneId' => $this->data->getDateTime()->getTimezone(),
                    ],
                ],
                'eventName' => $this->data->getRestaurantName(),
                'eventType' => RestaurantEntity::EVENT_RESTAURANT,
                'startDateTime' => $this->getDateTimeRFC3339($this->data->getDateTime()),
                'phone' => $this->data->getSupplierPhone(),
                'guestCount' => $this->data->getNumberPatrons(),
            ],
        ];
    }
}
