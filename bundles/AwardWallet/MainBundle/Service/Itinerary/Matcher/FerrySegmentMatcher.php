<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Matcher;

use AwardWallet\MainBundle\Entity\Tripsegment as EntitySegment;
use AwardWallet\Schema\Itineraries\FerrySegment as SchemaFerrySegment;

class FerrySegmentMatcher extends AbstractSegmentMatcher
{
    /**
     * @param SchemaFerrySegment $schemaSegment
     */
    public function match(EntitySegment $entitySegment, $schemaSegment): float
    {
        if (!$schemaSegment instanceof SchemaFerrySegment) {
            throw new \InvalidArgumentException(sprintf('Expected %s, got %s', SchemaFerrySegment::class, get_class($schemaSegment)));
        }

        return $this->baseMatch(
            $entitySegment,
            $schemaSegment->departure->stationCode ?? null,
            $schemaSegment->arrival->stationCode ?? null,
            $schemaSegment->departure->name ?? null,
            $schemaSegment->arrival->name ?? null,
            $schemaSegment->departure->address->text ?? $schemaSegment->departure->name ?? null,
            $schemaSegment->arrival->address->text ?? $schemaSegment->arrival->name ?? null,
            $schemaSegment->departure->localDateTime ?? null,
            $schemaSegment->arrival->localDateTime ?? null
        )->maxConfidence();
    }
}
