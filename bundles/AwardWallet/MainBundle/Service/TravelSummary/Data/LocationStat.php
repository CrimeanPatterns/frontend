<?php

namespace AwardWallet\MainBundle\Service\TravelSummary\Data;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI
 */
class LocationStat implements \JsonSerializable
{
    /**
     * @var int
     */
    private $countries;
    /**
     * @var int
     */
    private $cities;
    /**
     * @var int
     */
    private $continents;
    /**
     * @var array двухбуквенные коды стран
     */
    private $countryCodes = [];

    public function __construct(
        int $countries,
        int $cities,
        int $continents
    ) {
        $this->countries = $countries;
        $this->cities = $cities;
        $this->continents = $continents;
    }

    public function getCountries(): int
    {
        return $this->countries;
    }

    public function getCities(): int
    {
        return $this->cities;
    }

    public function getContinents(): int
    {
        return $this->continents;
    }

    public function getCountryCodes(): array
    {
        return $this->countryCodes;
    }

    public function setCountryCodes(array $countryCodes): self
    {
        $this->countryCodes = $countryCodes;

        return $this;
    }

    public function jsonSerialize()
    {
        return [
            'countries' => $this->countries,
            'cities' => $this->cities,
            'continents' => $this->continents,
        ];
    }
}
