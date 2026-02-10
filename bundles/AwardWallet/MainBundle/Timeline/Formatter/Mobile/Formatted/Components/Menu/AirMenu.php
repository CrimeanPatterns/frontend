<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Menu;

class AirMenu extends BaseMenu
{
    /**
     * @var FlightStatus
     */
    public $flightStatus;

    /**
     * @var AlternativeFlight[]
     */
    public $alternativeFlights;

    /**
     * @var string
     */
    public $boardingPassUrl;
}
