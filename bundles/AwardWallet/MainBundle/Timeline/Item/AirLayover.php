<?php

namespace AwardWallet\MainBundle\Timeline\Item;

class AirLayover extends Layover
{
    protected ?string $airportCode;

    protected ?string $arrivalTerminal;

    protected ?string $departureTerminal;

    public function __construct(AbstractItinerary $left, AbstractItinerary $right)
    {
        parent::__construct($left, $right);

        $this->airportCode = $left->getSource()->getArrcode();
        $this->arrivalTerminal = $left->getSource()->getArrivalTerminal();
        $this->departureTerminal = $right->getSource()->getDepartureTerminal();
    }

    public function getAirportCode(): ?string
    {
        return $this->airportCode;
    }

    public function getArrivalTerminal(): ?string
    {
        return $this->arrivalTerminal;
    }

    public function getDepartureTerminal(): ?string
    {
        return $this->departureTerminal;
    }
}
