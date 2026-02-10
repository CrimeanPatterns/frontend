<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Timeline\Diff\Changes;

/**
 * @NoDI()
 */
class ItineraryWrapper
{
    /**
     * @var Itinerary|Tripsegment|object
     */
    private $input;

    private Itinerary $itinerary;

    private Changes $changes;

    private ?\DateTime $minChangeDate;

    /**
     * @param Itinerary|Tripsegment $input
     */
    public function __construct(object $input, Itinerary $itinerary, Changes $changes, ?\DateTime $minChangeDate = null)
    {
        $this->input = $input;
        $this->itinerary = $itinerary;
        $this->changes = $changes;
        $this->minChangeDate = $minChangeDate;
    }

    /**
     * @return Itinerary|Tripsegment
     */
    public function getSource(): object
    {
        return $this->input;
    }

    public function getItinerary(): Itinerary
    {
        return $this->itinerary;
    }

    public function getChanges(): Changes
    {
        return $this->changes;
    }

    public function getMinChangeDate(): ?\DateTime
    {
        return $this->minChangeDate;
    }
}
