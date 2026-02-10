<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Segments;

use AwardWallet\MainBundle\Entity\Tripsegment as EntitySegment;

interface SegmentMatcherInterface
{
    public const SAME_TRIP = 'same_trip';
    public const ANY = 'any';

    /**
     * @return float Confidence level (0 - certainly not, 1 - certainly yes, 0.5 - 50/50)
     */
    public function match(EntitySegment $entitySegment, $schemaSegment, string $scope): float;
}
