<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

class Rental extends AbstractItinerary
{
    private ?GeoTag $pickupGeoTag = null;

    private ?GeoTag $dropoffGeoTag = null;

    /**
     * @param User|UserAgent|null $user
     */
    public function __construct(
        ?string $number,
        string $pickupLocation,
        \DateTimeInterface $pickupDate,
        string $dropoffLocation,
        \DateTimeInterface $dropoffDate,
        $user = null,
        array $fields = []
    ) {
        parent::__construct(array_merge($fields, [
            'Number' => $number,
            'PickupLocation' => $pickupLocation,
            'PickupDatetime' => $pickupDate->format('Y-m-d H:i:s'),
            'DropoffLocation' => $dropoffLocation,
            'DropoffDatetime' => $dropoffDate->format('Y-m-d H:i:s'),
        ]));

        $this->user = $user;
    }

    public function getPickupGeoTag(): ?GeoTag
    {
        return $this->pickupGeoTag;
    }

    public function setPickupGeoTag(?GeoTag $pickupGeoTag): self
    {
        $this->pickupGeoTag = $pickupGeoTag;

        return $this;
    }

    public function getDropoffGeoTag(): ?GeoTag
    {
        return $this->dropoffGeoTag;
    }

    public function setDropoffGeoTag(?GeoTag $dropoffGeoTag): self
    {
        $this->dropoffGeoTag = $dropoffGeoTag;

        return $this;
    }
}
