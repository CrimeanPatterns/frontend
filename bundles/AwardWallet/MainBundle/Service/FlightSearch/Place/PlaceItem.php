<?php

namespace AwardWallet\MainBundle\Service\FlightSearch\Place;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class PlaceItem
{
    private int $type;
    private int $id;
    private string $code;
    private string $name;
    private string $info;
    private string $value;
    private string $query;

    private string $countryCode = '';
    private string $countryName = '';
    private string $stateCode = '';
    private string $stateName = '';
    private string $cityCode = '';
    private string $cityName = '';
    private string $airCode = '';

    public function __construct(
        int $type,
        int $id,
        string $name,
        string $code = '',
        string $info = '',
        string $value = ''
    ) {
        $this->type = $type;
        $this->id = $id;
        $this->name = $name;
        $this->code = $code;
        $this->info = $info;
        $this->value = $value;
        $this->query = $type . '-' . $id;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getInfo(): string
    {
        return $this->info;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getCountryName(): ?string
    {
        return $this->countryName;
    }

    public function setCountryName(string $countryName): self
    {
        $this->countryName = $countryName;

        return $this;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function setCountryCode(string $countryCode): self
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    public function getStateName(): ?string
    {
        return $this->stateName;
    }

    public function setStateName(string $stateName): self
    {
        $this->stateName = $stateName;

        return $this;
    }

    public function getStateCode(): ?string
    {
        return $this->stateCode;
    }

    public function setStateCode(string $stateCode): self
    {
        $this->stateCode = $stateCode;

        return $this;
    }

    public function getCityName(): ?string
    {
        return $this->cityName;
    }

    public function setCityName(string $cityName): self
    {
        $this->cityName = $cityName;

        return $this;
    }

    public function getCityCode(): ?string
    {
        return $this->cityCode;
    }

    public function setCityCode(string $cityCode): self
    {
        $this->cityCode = $cityCode;

        return $this;
    }

    public function getAirCode(): ?string
    {
        return $this->airCode;
    }

    public function setAirCode(string $airCode): self
    {
        $this->airCode = $airCode;

        return $this;
    }

    public function getQuery(): ?string
    {
        if (empty($this->type) || empty($this->id)) {
            return null;
        }

        return $this->type . '-' . $this->id;
    }
}
