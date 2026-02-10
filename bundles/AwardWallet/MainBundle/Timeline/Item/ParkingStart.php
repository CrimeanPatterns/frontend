<?php

namespace AwardWallet\MainBundle\Timeline\Item;

use AwardWallet\MainBundle\Entity\Parking;
use AwardWallet\MainBundle\Entity\Parking as ParkingEntity;

class ParkingStart extends AbstractParking
{
    public function __construct(ParkingEntity $parking)
    {
        parent::__construct($parking, $parking->getUTCStartDate(), $parking->getStartDatetime());
    }

    public function getPrefix(): string
    {
        return Parking::getSegmentMap()[0];
    }

    public function getType(): string
    {
        return Type::PARKING_START;
    }
}
