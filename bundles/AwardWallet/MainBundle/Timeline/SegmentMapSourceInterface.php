<?php

namespace AwardWallet\MainBundle\Timeline;

use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;

interface SegmentMapSourceInterface
{
    /**
     * @return SegmentMapItem[]
     */
    public function getTimelineMapItems(Usr $user, ?Useragent $useragent = null): array;
}
