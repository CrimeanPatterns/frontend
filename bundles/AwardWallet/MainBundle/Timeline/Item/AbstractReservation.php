<?php

namespace AwardWallet\MainBundle\Timeline\Item;

abstract class AbstractReservation extends AbstractItinerary implements CoupleInterface, CanCreatePlanInterface
{
    use CoupleTrait;
    use CanCreatePlanTrait;

    public function getIcon(): string
    {
        return Icon::HOTEL;
    }
}
