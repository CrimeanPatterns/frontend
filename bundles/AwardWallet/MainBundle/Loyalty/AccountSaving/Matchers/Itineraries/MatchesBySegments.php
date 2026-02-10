<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Itineraries;

use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Segments\SegmentMatcherInterface;

trait MatchesBySegments
{
    /**
     * @var SegmentMatcherInterface
     */
    private $segmentMatcher;

    public function getMeanConfidence(array $entitySegments, array $schemaSegments): float
    {
        if (empty($entitySegments) || empty($schemaSegments)) {
            return .0;
        }
        $unmatchedEntities = $entitySegments;
        $unmatchedSchemas = $schemaSegments;
        $matchesCount = 0;
        $confidenceSum = .0;

        foreach ($entitySegments as $entitySegment) {
            $match = $this->getBestMatch($entitySegment, $unmatchedSchemas);

            if (null !== $match) {
                $unmatchedSchemas = array_udiff($unmatchedSchemas, [$match['schemaSegment']], function ($segmentA, $segmentB) {
                    return $segmentA !== $segmentB;
                });
                $unmatchedEntities = array_udiff($unmatchedEntities, [$match['entitySegment']], function (Tripsegment $segmentA, Tripsegment $segmentB) {
                    return $segmentA->getId() <=> $segmentB->getId();
                });
                $confidenceSum += $match['confidence'];
                $matchesCount++;
            }
        }

        return $confidenceSum / (count($unmatchedEntities) + count($unmatchedSchemas) + $matchesCount);
    }

    private function getBestMatch(Tripsegment $entitySegment, array $schemaSegments): ?array
    {
        $currentConfidence = .0;
        $currentMatch = null;

        foreach ($schemaSegments as $schemaSegment) {
            $newConfidence = $this->segmentMatcher->match($entitySegment, $schemaSegment, SegmentMatcherInterface::ANY);

            if ($newConfidence > $currentConfidence) {
                $currentMatch = [
                    'entitySegment' => $entitySegment,
                    'schemaSegment' => $schemaSegment,
                    'confidence' => $newConfidence,
                ];
                $currentConfidence = $newConfidence;
            }
        }

        return $currentMatch;
    }
}
