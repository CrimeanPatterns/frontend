<?php

namespace AwardWallet\MainBundle\Service\FlightStats\TripAlerts;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Entity\Tripsegment;

/**
 * @NoDI()
 */
class AlertTarget
{
    /**
     * @var Tripsegment
     */
    public $tripSegment;

    public $data;

    /**
     * AlertTarget constructor.
     */
    public function __construct(Tripsegment $tripSegment, $data = null)
    {
        $this->tripSegment = $tripSegment;
        $this->data = $data;
    }
}
