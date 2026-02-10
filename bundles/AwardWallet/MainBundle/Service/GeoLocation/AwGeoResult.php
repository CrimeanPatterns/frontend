<?php

namespace AwardWallet\MainBundle\Service\GeoLocation;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class AwGeoResult
{
    private ?int $countryId;
    private ?int $stateId;
    private ?string $cityName;
    /**
     * @var ?array{float, float}
     */
    private ?array $point;

    public function __construct(?int $countryId, ?int $stateId, ?string $cityName, ?array $point)
    {
        $this->countryId = $countryId;
        $this->stateId = $stateId;
        $this->cityName = $cityName;
        $this->point = $point;
    }

    public function getCountryId(): ?int
    {
        return $this->countryId;
    }

    public function getStateId(): ?int
    {
        return $this->stateId;
    }

    public function getCityName(): ?string
    {
        return $this->cityName;
    }

    public function getPoint(): ?array
    {
        return $this->point;
    }
}
