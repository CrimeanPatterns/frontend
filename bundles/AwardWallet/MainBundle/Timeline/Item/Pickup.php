<?php

namespace AwardWallet\MainBundle\Timeline\Item;

use AwardWallet\MainBundle\Entity\Rental;

class Pickup extends AbstractRental
{
    public function __construct(Rental $rental)
    {
        parent::__construct($rental, $rental->getUTCStartDate(), $rental->getPickupdatetime(), $rental->getPickupgeotagid());
    }

    public function getPrefix(): string
    {
        return Rental::getSegmentMap()[0];
    }

    public function getType(): string
    {
        return Type::PICKUP;
    }
}
