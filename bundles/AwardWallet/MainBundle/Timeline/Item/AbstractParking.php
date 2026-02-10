<?php

namespace AwardWallet\MainBundle\Timeline\Item;

use AwardWallet\MainBundle\Entity\Parking as ParkingEntity;

abstract class AbstractParking extends AbstractItinerary implements CoupleInterface, CanCreatePlanInterface
{
    use CoupleTrait;
    use CanCreatePlanTrait;

    public function __construct(ParkingEntity $parking, \DateTime $utcStartDate, \DateTime $localDate)
    {
        $geotag = $parking->getGeoTagID();
        $map = null;

        if ($geotag && $geotag->getLng() && $geotag->getLat()) {
            $map = new Map([$geotag->getDMSformat()], false);
        }
        parent::__construct(
            $parking->getId(),
            $utcStartDate,
            null,
            $localDate,
            $parking,
            $parking->getConfirmationNumber(true),
            $parking->getAccount(),
            $parking->getProvider(),
            $geotag,
            $map,
            !empty($parking->getChangedate())
        );
    }

    public function getIcon(): string
    {
        return Icon::PARKING;
    }
}
