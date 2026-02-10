<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

class Reservation extends AbstractItinerary
{
    private ?GeoTag $geoTag = null;

    /**
     * @param User|UserAgent|null $user
     */
    public function __construct(
        ?string $confNumber,
        string $hotelName,
        \DateTimeInterface $checkInDate,
        \DateTimeInterface $checkOutDate,
        $user = null,
        array $fields = []
    ) {
        parent::__construct(array_merge($fields, [
            'ConfirmationNumber' => $confNumber,
            'HotelName' => $hotelName,
            'CheckInDate' => $checkInDate->format('Y-m-d H:i:s'),
            'CheckOutDate' => $checkOutDate->format('Y-m-d H:i:s'),
        ]));

        $this->user = $user;
    }

    public function getGeoTag(): ?GeoTag
    {
        return $this->geoTag;
    }

    public function setGeoTag(?GeoTag $geoTag): self
    {
        $this->geoTag = $geoTag;

        return $this;
    }
}
