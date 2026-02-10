<?php

namespace AwardWallet\MainBundle\Service\Lounge\DTO;

use AwardWallet\MainBundle\Entity\Airline;
use AwardWallet\MainBundle\Entity\Alliance;

class CarrierDTO
{
    /**
     * @var Airline[]
     */
    private array $airlines = [];

    /**
     * @var Alliance[]
     */
    private array $alliances = [];

    public function getAirlines(): array
    {
        return array_values($this->airlines);
    }

    public function addAirline(Airline $airline): self
    {
        $this->airlines[$airline->getAirlineid()] = $airline;

        return $this;
    }

    public function getAlliances(): array
    {
        return array_values($this->alliances);
    }

    public function addAlliance(Alliance $alliance): self
    {
        $this->alliances[$alliance->getAllianceid()] = $alliance;

        return $this;
    }
}
