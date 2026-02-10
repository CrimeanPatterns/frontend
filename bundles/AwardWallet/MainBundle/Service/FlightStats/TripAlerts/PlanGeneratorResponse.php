<?php

namespace AwardWallet\MainBundle\Service\FlightStats\TripAlerts;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\API\Model\ImportFlight;

/**
 * @NoDI()
 */
class PlanGeneratorResponse
{
    /**
     * @var ImportFlight[]
     */
    public $flights;
    /**
     * @var array
     */
    public $validSegments;
    /**
     * @var array
     */
    public $invalidSegments;

    public function __construct($flights = [], $validSegments = [], $invalidSegments = [])
    {
        $this->flights = $flights;
        $this->validSegments = $validSegments;
        $this->invalidSegments = $invalidSegments;
    }
}
