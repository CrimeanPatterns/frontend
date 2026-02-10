<?php

namespace AwardWallet\MainBundle\Service\Tripit\Converter;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\Tripit\Serializer\CruiseObject;
use AwardWallet\MainBundle\Service\Tripit\Serializer\CruiseSegmentObject;
use AwardWallet\MainBundle\Service\Tripit\Serializer\TravelerObject;

/**
 * @NoDI()
 */
class Cruise extends BaseConverter
{
    private CruiseObject $data;

    public function __construct(CruiseObject $data)
    {
        $this->data = $data;
    }

    public function run()
    {
        /** @var \AwardWallet\Schema\Itineraries\Cruise $cruise */
        $cruise = $this->toObject($this->fields());

        if ($this->data->getBookingSiteConfNum() !== null) {
            $cruise->travelAgency->confirmationNumbers[] = $this->toObject($this->confirmationNumber());
        }

        foreach ($this->data->getTraveler() as $traveler) {
            $cruise->travelers[] = $this->toObject($this->traveler($traveler));
        }

        $previousSegment = null;

        foreach ($this->data->getSegment() as $key => $segment) {
            /** @var CruiseSegmentObject $segment */
            if ($key === 0 && $segment->getEndDateTime() === null) {
                $segment->setEndDateTime($segment->getStartDateTime());
            }

            if ($key !== 0) {
                $cruise->segments[] = $this->toObject($this->segment($segment, $previousSegment));
            }
            $previousSegment = $segment;
        }

        return $cruise;
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

    /**
     * Генерирует сегмент круиза. TripIt API возвращает не полноценные сегменты, а список остановок,
     * отсортированных по дате.
     *
     * @param CruiseSegmentObject $current текущая остановка круиза
     * @param CruiseSegmentObject $previous предыдущая остановка круиза
     */
    private function segment(CruiseSegmentObject $current, CruiseSegmentObject $previous): array
    {
        if ($previous->getEndDateTime()->getDate() === null) {
            $previous->getEndDateTime()->setDate($previous->getStartDateTime()->getDate());
        }

        if ($previous->getEndDateTime()->getTime() === null) {
            $previous->getEndDateTime()->setTime('20:00:00');
        }

        if ($current->getStartDateTime()->getTime() === null) {
            $current->getStartDateTime()->setTime('08:00:00');
        }

        return [
            'class' => \AwardWallet\Schema\Itineraries\CruiseSegment::class,
            'data' => [
                'departure' => [
                    'class' => \AwardWallet\Schema\Itineraries\TransportLocation::class,
                    'data' => [
                        'name' => $previous->getLocationName(),
                        'localDateTime' => $this->getDateTimeRFC3339($previous->getEndDateTime()),
                        'address' => [
                            'class' => \AwardWallet\Schema\Itineraries\Address::class,
                            'data' => [
                                'text' => $previous->getLocationAddress()->getAddress(),
                                'city' => $previous->getLocationAddress()->getCity(),
                                'stateName' => $previous->getLocationAddress()->getState(),
                                'countryCode' => $previous->getLocationAddress()->getCountry(),
                                'postalCode' => $previous->getLocationAddress()->getZip(),
                                'lat' => $previous->getLocationAddress()->getLatitude(),
                                'lng' => $previous->getLocationAddress()->getLongitude(),
                                'timezoneId' => $previous->getEndDateTime()->getTimezone(),
                            ],
                        ],
                    ],
                ],
                'arrival' => [
                    'class' => \AwardWallet\Schema\Itineraries\TransportLocation::class,
                    'data' => [
                        'name' => $current->getLocationName(),
                        'localDateTime' => $this->getDateTimeRFC3339($current->getStartDateTime()),
                        'address' => [
                            'class' => \AwardWallet\Schema\Itineraries\Address::class,
                            'data' => [
                                'text' => $current->getLocationAddress()->getAddress(),
                                'city' => $current->getLocationAddress()->getCity(),
                                'stateName' => $current->getLocationAddress()->getState(),
                                'countryCode' => $current->getLocationAddress()->getCountry(),
                                'postalCode' => $current->getLocationAddress()->getZip(),
                                'lat' => $current->getLocationAddress()->getLatitude(),
                                'lng' => $current->getLocationAddress()->getLongitude(),
                                'timezoneId' => $current->getStartDateTime()->getTimezone(),
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function fields(): array
    {
        return [
            'class' => \AwardWallet\Schema\Itineraries\Cruise::class,
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
                'reservationDate' => $this->data->getBookingDate(),
                'cruiseDetails' => [
                    'class' => \AwardWallet\Schema\Itineraries\CruiseDetails::class,
                    'data' => [
                        'class' => $this->data->getCabinType(),
                        'room' => $this->data->getCabinNumber(),
                        'ship' => $this->data->getShipName(),
                        'voyageNumber' => $this->data->getSupplierConfNum(),
                    ],
                ],
            ],
        ];
    }
}
