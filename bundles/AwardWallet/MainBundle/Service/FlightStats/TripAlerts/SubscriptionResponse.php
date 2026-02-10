<?php

namespace AwardWallet\MainBundle\Service\FlightStats\TripAlerts;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class SubscriptionResponse
{
    /**
     * @var bool
     */
    public $monitorable;
    /**
     * @var bool
     */
    public $subscribeCalled = false;
    /**
     * @var array
     */
    public $validSegments = [];

    /**
     * @var array
     */
    public $invalidSegments = [];
}
