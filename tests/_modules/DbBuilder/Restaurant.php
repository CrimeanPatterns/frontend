<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

use AwardWallet\MainBundle\Entity\Restaurant as RestaurantEntity;

class Restaurant extends AbstractItinerary
{
    private ?GeoTag $geoTag = null;

    /**
     * @param User|UserAgent|null $user
     */
    public function __construct(
        ?string $confNumber,
        string $name,
        \DateTimeInterface $startDate,
        ?\DateTimeInterface $endDate = null,
        int $type = RestaurantEntity::EVENT_RESTAURANT,
        $user = null,
        array $fields = []
    ) {
        parent::__construct(array_merge($fields, [
            'ConfNo' => $confNumber,
            'Name' => $name,
            'EventType' => $type,
            'StartDate' => $startDate->format('Y-m-d H:i:s'),
            'EndDate' => $endDate ? $endDate->format('Y-m-d H:i:s') : null,
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
