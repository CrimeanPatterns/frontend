<?php

namespace AwardWallet\MainBundle\Timeline\Item;

use AwardWallet\MainBundle\Entity\Parking;
use AwardWallet\MainBundle\Entity\Parking as ParkingEntity;

class ParkingEnd extends AbstractParking
{
    public function __construct(ParkingEntity $parking)
    {
        parent::__construct($parking, $parking->getUTCEndDate(), $parking->getEndDatetime());
    }

    public function getPrefix(): string
    {
        return Parking::getSegmentMap()[1];
    }

    public function getType(): string
    {
        return Type::PARKING_END;
    }
}
