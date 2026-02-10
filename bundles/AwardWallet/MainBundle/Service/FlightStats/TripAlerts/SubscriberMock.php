<?php

namespace AwardWallet\MainBundle\Service\FlightStats\TripAlerts;

class SubscriberMock extends Subscriber
{
    public function subscribe(array $flights, $userId)
    {
        return true;
    }
}
