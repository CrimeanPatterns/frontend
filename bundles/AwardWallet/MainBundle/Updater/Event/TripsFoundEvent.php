<?php

namespace AwardWallet\MainBundle\Updater\Event;

class TripsFoundEvent extends AbstractAccountEvent
{
    public $trips;

    public $tripIds;

    public function __construct($accountId, $trips, $ids = [])
    {
        parent::__construct($accountId, 'trips_found');
        $this->trips = $trips;
        $this->tripIds = $ids;
    }
}
