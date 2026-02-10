<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\Itineraries;

use AwardWallet\MainBundle\Entity\Itinerary;
use Symfony\Component\EventDispatcher\Event;

class ItinerarySavedEvent extends Event
{
    public const NAME = 'aw.itinerary.saved';

    /**
     * @var Itinerary
     */
    private $itinerary;

    public function __construct(Itinerary $itinerary)
    {
        $this->itinerary = $itinerary;
    }

    public function getItinerary(): Itinerary
    {
        return $this->itinerary;
    }
}
