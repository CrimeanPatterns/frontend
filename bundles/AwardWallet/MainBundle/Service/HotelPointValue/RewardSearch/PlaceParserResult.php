<?php

namespace AwardWallet\MainBundle\Service\HotelPointValue\RewardSearch;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class PlaceParserResult
{
    private string $placeId;
    private float $lat;
    private float $lng;
    private ?string $countryCode;
    private ?string $stateCode;
    private ?string $city;
    private ?string $address;

    public function __construct(
        string $placeId,
        float $lat,
        float $lng,
        ?string $countryCode = null,
        ?string $stateCode = null,
        ?string $city = null,
        ?string $address = null
    ) {
        $this->placeId = $placeId;
        $this->lat = $lat;
        $this->lng = $lng;
        $this->countryCode = $countryCode;
        $this->stateCode = $stateCode;
        $this->city = $city;
        $this->address = $address;
    }

    public function getPlaceId(): string
    {
        return $this->placeId;
    }

    public function getLat(): float
    {
        return $this->lat;
    }

    public function getLng(): float
    {
        return $this->lng;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function getStateCode(): ?string
    {
        return $this->stateCode;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }
}
