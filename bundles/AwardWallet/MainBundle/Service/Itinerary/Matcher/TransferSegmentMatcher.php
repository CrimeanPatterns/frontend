<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Matcher;

use AwardWallet\MainBundle\Entity\Tripsegment as EntitySegment;
use AwardWallet\Schema\Itineraries\TransferSegment as SchemaTransferSegment;

class TransferSegmentMatcher extends AbstractSegmentMatcher
{
    /**
     * @param SchemaTransferSegment $schemaSegment
     */
    public function match(EntitySegment $entitySegment, $schemaSegment): float
    {
        if (!$schemaSegment instanceof SchemaTransferSegment) {
            throw new \InvalidArgumentException(sprintf('Expected %s, got %s', SchemaTransferSegment::class, get_class($schemaSegment)));
        }

        return $this->baseMatch(
            $entitySegment,
            $schemaSegment->departure->airportCode ?? null,
            $schemaSegment->arrival->airportCode ?? null,
            $schemaSegment->departure->name ?? null,
            $schemaSegment->arrival->name ?? null,
            $schemaSegment->departure->address->text ?? $schemaSegment->departure->name ?? null,
            $schemaSegment->arrival->address->text ?? $schemaSegment->arrival->name ?? null,
            $schemaSegment->departure->localDateTime ?? null,
            $schemaSegment->arrival->localDateTime ?? null
        )->maxConfidence();
    }
}
