<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

class Parking extends AbstractItinerary
{
    private ?GeoTag $geoTag = null;

    /**
     * @param User|UserAgent|null $user
     */
    public function __construct(
        ?string $number,
        ?string $providerName,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        $user = null,
        array $fields = []
    ) {
        parent::__construct(array_merge($fields, [
            'Number' => $number,
            'ProviderName' => $providerName,
            'StartDatetime' => $startDate->format('Y-m-d H:i:s'),
            'EndDatetime' => $endDate->format('Y-m-d H:i:s'),
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
