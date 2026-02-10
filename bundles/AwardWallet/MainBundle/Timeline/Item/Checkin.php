<?php

namespace AwardWallet\MainBundle\Timeline\Item;

use AwardWallet\MainBundle\Entity\Reservation;

class Checkin extends AbstractReservation
{
    public function __construct(Reservation $reservation)
    {
        $geotag = $reservation->getGeotagid();
        $map = null;

        if ($geotag && $geotag->getLng() && $geotag->getLat()) {
            $map = new Map([$geotag->getDMSformat()], false);
        }
        parent::__construct(
            $reservation->getId(),
            $reservation->getUTCStartDate(),
            null,
            $reservation->getCheckindate(),
            $reservation,
            $reservation->getConfirmationNumber(true),
            $reservation->getAccount(),
            $reservation->getProvider(),
            $geotag,
            $map,
            !empty($reservation->getChangedate())
        );
    }

    public function getPrefix(): string
    {
        return Reservation::getSegmentMap()[0];
    }

    public function getType(): string
    {
        return Type::CHECKIN;
    }
}
