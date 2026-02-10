<?php

namespace AwardWallet\MainBundle\Timeline\Item;

use AwardWallet\MainBundle\Entity\Rental;

class Dropoff extends AbstractRental
{
    public function __construct(Rental $rental)
    {
        parent::__construct($rental, $rental->getUTCEndDate(), $rental->getDropoffdatetime(), $rental->getDropoffgeotagid());
    }

    public function getPrefix(): string
    {
        return Rental::getSegmentMap()[1];
    }

    public function getType(): string
    {
        return Type::DROPOFF;
    }
}
