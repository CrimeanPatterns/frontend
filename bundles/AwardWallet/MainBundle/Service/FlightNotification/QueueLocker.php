<?php

namespace AwardWallet\MainBundle\Service\FlightNotification;

use AwardWallet\MainBundle\Entity\Tripsegment;

class QueueLocker
{
    private \Memcached $memcached;

    public function __construct(\Memcached $memcached)
    {
        $this->memcached = $memcached;
    }

    public function isAcquired(Tripsegment $tripSegment, OffsetStatus $offsetStatus): bool
    {
        return $this->memcached->get($this->getCacheKey($tripSegment, $offsetStatus)) !== false;
    }

    public function acquire(Tripsegment $tripSegment, OffsetStatus $offsetStatus): bool
    {
        return $this->memcached->set(
            $this->getCacheKey($tripSegment, $offsetStatus),
            time(),
            max($offsetStatus->getSendingDelay(), 0) + ($offsetStatus->getOffset() - $offsetStatus->getDeadline()) + 60
        );
    }

    public function release(Tripsegment $tripSegment, OffsetStatus $offsetStatus): bool
    {
        return $this->memcached->delete($this->getCacheKey($tripSegment, $offsetStatus));
    }

    private function getCacheKey(Tripsegment $tripSegment, OffsetStatus $offsetStatus): string
    {
        return sprintf(
            'flight_notif_v2_%d_%d_%s',
            $tripSegment->getId(),
            $offsetStatus->getOffset(),
            $tripSegment->getDepartureDate()->format('Y-m-d H:i:s')
        );
    }
}
