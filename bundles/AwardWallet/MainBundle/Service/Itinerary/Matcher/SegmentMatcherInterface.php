<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Matcher;

use AwardWallet\MainBundle\Entity\Tripsegment as EntitySegment;

interface SegmentMatcherInterface
{
    /**
     * @return float Confidence level (0 - certainly not, 1 - certainly yes, 0.5 - 50/50)
     */
    public function match(EntitySegment $entitySegment, $schemaSegment): float;
}
