<?php

namespace AwardWallet\MainBundle\Timeline\Item;

use AwardWallet\MainBundle\Entity\Reservation;

class Checkout extends AbstractReservation
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
            $reservation->getUTCEndDate(),
            null,
            $reservation->getCheckoutdate(),
            $reservation,
            $reservation->getConfirmationNumber(true),
            null,
            null,
            $geotag,
            $map,
            !empty($reservation->getChangedate())
        );
    }

    public function getPrefix(): string
    {
        return Reservation::getSegmentMap()[1];
    }

    public function getType(): string
    {
        return Type::CHECKOUT;
    }
}
