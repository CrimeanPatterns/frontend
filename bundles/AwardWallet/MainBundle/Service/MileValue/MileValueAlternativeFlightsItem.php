<?php

namespace AwardWallet\MainBundle\Service\MileValue;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class MileValueAlternativeFlightsItem
{
    public $TripID;
    public $AlternativeCost;
    public $MileValue;
    public $CabinClass;
    public $ClassOfService;
    public $MileRoute;
    public $CashRoute;
    public $RouteType;
    public $TravelersCount;
    public $CustomPick;
    public $CustomAlternativeCost;
    public $CustomMileValue;

    public $foundPrices;
    public $airlines;

    public function setFoundPrice(?FoundPrices $foundPrices): self
    {
        $this->foundPrices = $foundPrices;

        return $this;
    }

    public function getFoundPrices(): ?FoundPrices
    {
        return $this->foundPrices;
    }

    public function setAirlines(array $airlinesList): self
    {
        $this->airlines = $airlinesList;

        return $this;
    }

    public function getAirlines(): array
    {
        return $this->airlines;
    }

    public function setMileValueFields(array $data)
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }

        return $this;
    }
}
