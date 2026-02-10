<?php

namespace AwardWallet\MainBundle\Timeline\Item;

class CruiseTrip extends AbstractTrip
{
    public function getDeparture(): string
    {
        return $this->getSource()->getDepname();
    }

    public function getArrival(): string
    {
        return $this->getSource()->getArrname();
    }

    public function getCruiseName(): ?string
    {
        return $this->getSource()->getAirlineName();
    }

    public function getIcon(): string
    {
        return Icon::BOAT;
    }

    public function getType(): string
    {
        return Type::CRUISE_TRIP;
    }
}
