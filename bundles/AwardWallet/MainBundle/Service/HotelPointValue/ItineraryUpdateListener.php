<?php

namespace AwardWallet\MainBundle\Service\HotelPointValue;

use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\Itineraries\ItinerarySavedEvent;

class ItineraryUpdateListener
{
    private PointValueCalculator $calculator;

    public function __construct(PointValueCalculator $calculator)
    {
        $this->calculator = $calculator;
    }

    public function onItinerarySaved(ItinerarySavedEvent $event): void
    {
        $itinerary = $event->getItinerary();

        if (!($itinerary instanceof Reservation)) {
            return;
        }

        $this->calculator->updateItinerary($itinerary, true);
    }
}
