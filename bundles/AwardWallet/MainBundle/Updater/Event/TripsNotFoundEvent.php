<?php

namespace AwardWallet\MainBundle\Updater\Event;

class TripsNotFoundEvent extends AbstractAccountEvent
{
    public function __construct($accountId)
    {
        parent::__construct($accountId, 'trips_not_found');
    }
}
