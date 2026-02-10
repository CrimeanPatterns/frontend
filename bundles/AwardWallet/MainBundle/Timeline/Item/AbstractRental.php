<?php

namespace AwardWallet\MainBundle\Timeline\Item;

use AwardWallet\Common\Entity\Geotag;
use AwardWallet\MainBundle\Entity\Rental;

abstract class AbstractRental extends AbstractItinerary implements CoupleInterface, CanCreatePlanInterface
{
    use CoupleTrait;
    use CanCreatePlanTrait;

    public function __construct(Rental $rental, \DateTime $utcStartDate, \DateTime $localDate, ?Geotag $geotag)
    {
        $map = null;

        if ($geotag && $geotag->getLng() && $geotag->getLat()) {
            $map = new Map([$geotag->getDMSformat()], false);
        }
        parent::__construct(
            $rental->getId(),
            $utcStartDate,
            null,
            $localDate,
            $rental,
            $rental->getConfirmationNumber(true),
            $rental->getAccount(),
            $rental->getProvider(),
            $geotag,
            $map,
            !empty($rental->getChangedate())
        );
    }

    public function getIcon(): string
    {
        return Icon::CAR;
    }
}
