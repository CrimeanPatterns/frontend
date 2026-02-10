<?php

namespace AwardWallet\MainBundle\Service\Tripit\Converter;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Entity\Restaurant as RestaurantEntity;
use AwardWallet\MainBundle\Service\Tripit\Serializer\ActivityObject;
use AwardWallet\MainBundle\Service\Tripit\Serializer\GuestObject;

/**
 * @NoDI()
 */
class Activity extends BaseConverter
{
    private ActivityObject $data;

    public function __construct(ActivityObject $data)
    {
        $this->data = $data;
    }

    public function run()
    {
        /** @var \AwardWallet\Schema\Itineraries\Event $activity */
        $activity = $this->toObject($this->fields());

        if ($this->data->getBookingSiteConfNum() !== null) {
            $activity->travelAgency->confirmationNumbers[] = $this->toObject($this->confirmationNumber());
        }

        foreach ($this->data->getParticipant() as $participant) {
            $activity->guests[] = $this->toObject($this->guest($participant));
        }

        return $activity;
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

        if ($this->data->getStartDateTime()->getTime() === null) {
            $this->data->getStartDateTime()->setTime('12:00:00');
        }

        return [
            'class' => \AwardWallet\Schema\Itineraries\Event::class,
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
                'address' => [
                    'class' => \AwardWallet\Schema\Itineraries\Address::class,
                    'data' => [
                        'text' => $address->getAddress(),
                        'city' => $address->getCity(),
                        'stateName' => $address->getState(),
                        'countryCode' => $address->getCountry(),
                        'postalCode' => $address->getZip(),
                        'lat' => $address->getLatitude(),
                        'lng' => $address->getLongitude(),
                        'timezoneId' => $this->data->getStartDateTime()->getTimezone(),
                    ],
                ],
                'eventName' => $this->data->getEventName(),
                'eventType' => RestaurantEntity::EVENT_SHOW,
                'startDateTime' => $this->getDateTimeRFC3339($this->data->getStartDateTime()),
                'endDateTime' => $this->getDateTimeRFC3339($this->data->getEndDateTime()),
            ],
        ];
    }
}
