<?php

namespace AwardWallet\MainBundle\Timeline\Item;

use AwardWallet\MainBundle\Entity\Rental;

class Taxi extends AbstractItinerary implements CanCreatePlanInterface
{
    use CanCreatePlanTrait;

    public function __construct(Rental $rental)
    {
        parent::__construct(
            $rental->getId(),
            $rental->getUTCStartDate(),
            $rental->getUTCEndDate(),
            $rental->getPickupdatetime(),
            $rental,
            $rental->getConfirmationNumber(true),
            $rental->getAccount(),
            $rental->getProvider(),
            $rental->getPickupgeotagid(),
            null,
            !empty($rental->getChangedate())
        );
    }

    public function getPrefix(): string
    {
        return Rental::getSegmentMap()[0];
    }

    public function getType(): string
    {
        return Type::TAXI_RIDE;
    }

    public function getIcon(): string
    {
        return Icon::TAXI;
    }
}
