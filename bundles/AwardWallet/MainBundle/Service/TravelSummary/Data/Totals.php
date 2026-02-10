<?php

namespace AwardWallet\MainBundle\Service\TravelSummary\Data;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI
 */
class Totals implements \JsonSerializable
{
    /**
     * @var ?int
     */
    private $airlines;
    /**
     * @var ?int
     */
    private $countries;
    /**
     * @var ?int
     */
    private $airports;

    public function __construct(
        ?int $airlines,
        ?int $countries,
        ?int $airports
    ) {
        $this->airlines = $airlines ?? 0;
        $this->countries = $countries ?? 0;
        $this->airports = $airports ?? 0;
    }

    public function getAirlines(): ?int
    {
        return $this->airlines;
    }

    public function getCountries(): ?int
    {
        return $this->countries;
    }

    public function getAirports(): ?int
    {
        return $this->airports;
    }

    public function jsonSerialize()
    {
        return [
            'airlines' => $this->airlines,
            'countries' => $this->countries,
            'airports' => $this->airports,
        ];
    }
}
