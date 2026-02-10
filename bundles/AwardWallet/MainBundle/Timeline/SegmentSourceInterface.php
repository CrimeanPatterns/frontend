<?php

namespace AwardWallet\MainBundle\Timeline;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Timeline\Item\ItineraryInterface;

interface SegmentSourceInterface
{
    /**
     * @return array<ItineraryInterface>
     */
    public function getTimelineItems(Usr $user, ?QueryOptions $queryOptions = null): array;
}
