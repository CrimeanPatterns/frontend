<?php

namespace AwardWallet\MainBundle\Service\ItineraryComparator\Property;

class ReservationDate extends DateTimeProperty
{
    public array $parts = ['year', 'mon', 'mday'];
}
