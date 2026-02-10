<?php

namespace AwardWallet\MainBundle\Timeline;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Timeline\Item\ItineraryInterface;

interface SegmentIterSourceInterface extends SegmentSourceInterface
{
    /**
     * @return iterable<ItineraryInterface>
     */
    public function getTimelineItemsIter(Usr $user, ?QueryOptions $queryOptions = null): iterable;
}
