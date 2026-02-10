<?php

namespace AwardWallet\MainBundle\Service\FlightNotification;

use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Loyalty\AccountSaving\ItineraryUpdateEvent;

class FlightListener
{
    private Producer $producer;

    public function __construct(Producer $producer)
    {
        $this->producer = $producer;
    }

    public function onItineraryUpdate(ItineraryUpdateEvent $event)
    {
        $itinerary = $event->getItinerary();

        if (!($itinerary instanceof Trip)) {
            return;
        }

        $now = new \DateTime();

        foreach ($itinerary->getSegments() as $segment) {
            $this->producer->publish($segment, $now);
        }
    }
}
