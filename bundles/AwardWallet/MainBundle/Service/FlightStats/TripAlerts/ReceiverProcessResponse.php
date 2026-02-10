<?php

namespace AwardWallet\MainBundle\Service\FlightStats\TripAlerts;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Loyalty\Resources\Itineraries\FlightPoint;
use AwardWallet\MainBundle\Loyalty\Resources\Itineraries\FlightSegment;

/**
 * @NoDI()
 */
class ReceiverProcessResponse
{
    /**
     * @var FlightSegment
     */
    public $segment;

    /**
     * @var callable
     * @return Push[]
     */
    public $onGetPushes;

    /**
     * @var bool
     */
    public $hideSegment;

    /**
     * @var bool
     */
    public $sendToHidden = false;

    public function __construct()
    {
        $this->segment = new FlightSegment();
        $this->segment->departure = new FlightPoint();
        $this->segment->arrival = new FlightPoint();
    }
}
