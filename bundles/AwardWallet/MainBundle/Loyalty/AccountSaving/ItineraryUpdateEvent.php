<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving;

use AwardWallet\MainBundle\Entity\Itinerary;
use Symfony\Contracts\EventDispatcher\Event;

class ItineraryUpdateEvent extends Event
{
    private Itinerary $itinerary;

    public function __construct(Itinerary $itinerary)
    {
        $this->itinerary = $itinerary;
    }

    public function getItinerary(): Itinerary
    {
        return $this->itinerary;
    }
}
