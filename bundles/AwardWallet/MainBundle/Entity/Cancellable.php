<?php

namespace AwardWallet\MainBundle\Entity;

interface Cancellable
{
    /**
     * Cancel this itinerary.
     */
    public function cancel(): void;
}
