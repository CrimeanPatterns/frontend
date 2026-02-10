<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Matcher;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Trip as EntityTrip;
use AwardWallet\MainBundle\Entity\Tripsegment as EntityTripSegment;
use AwardWallet\MainBundle\Service\GeoLocationMatcher;
use AwardWallet\Schema\Itineraries\BusSegment;
use AwardWallet\Schema\Itineraries\CruiseSegment;
use AwardWallet\Schema\Itineraries\FerrySegment;
use AwardWallet\Schema\Itineraries\FlightSegment;
use AwardWallet\Schema\Itineraries\TrainSegment;
use AwardWallet\Schema\Itineraries\TransferSegment;
use Psr\Log\LoggerInterface;

abstract class AbstractItineraryWithSegmentsMatcher extends AbstractItineraryMatcher
{
    private const CONFIDENCE_THRESHOLD = 0.9;

    private SegmentMatcherInterface $segmentMatcher;

    public function __construct(
        LoggerInterface $logger,
        GeoLocationMatcher $locationMatcher,
        SegmentMatcherInterface $segmentMatcher
    ) {
        parent::__construct($logger, $locationMatcher);

        $this->segmentMatcher = $segmentMatcher;
    }

    /**
     * @param EntityItinerary|EntityTrip $entityItinerary
     * @param BusSegment[]|CruiseSegment[]|FerrySegment[]|FlightSegment[]|TrainSegment[]|TransferSegment[] $schemaSegments
     */
    protected function baseSegmentMatch(EntityItinerary $entityItinerary, array $schemaSegments): MatchResult
    {
        return MatchResult::create()
            ->addResult(
                'baseSegmentMatch.sameSegments',
                $this->isSameSegments($entityItinerary->getSegments()->toArray(), $schemaSegments),
                0.6
            );
    }

    /**
     * @param EntityTripSegment[] $entitySegments
     * @param BusSegment[]|CruiseSegment[]|FerrySegment[]|FlightSegment[]|TrainSegment[]|TransferSegment[] $schemaSegments
     */
    protected function isSameSegments(array $entitySegments, array $schemaSegments): bool
    {
        if (empty($entitySegments) || empty($schemaSegments)) {
            return false;
        }

        $unmatchedSchemas = $schemaSegments;
        $matches = [];

        foreach ($entitySegments as $entitySegment) {
            $bestMatch = $this->getBestMatch($entitySegment, $unmatchedSchemas);

            if (!is_null($bestMatch)) {
                $unmatchedSchemas = array_filter($unmatchedSchemas, function ($schemaSegment) use ($bestMatch) {
                    return $schemaSegment !== $bestMatch['schemaSegment'];
                });
                $matches[] = array_merge($bestMatch, [
                    'entitySegment' => ObjectToArrayConverter::convertTripSegment($entitySegment),
                ]);
            }
        }

        $matchedCount = count($matches);

        if ($matchedCount === 0) {
            return false;
        }

        $this->logger->info(
            sprintf(
                'matched %d segments, ids: %s',
                $matchedCount,
                implode(', ', array_map(
                    function (array $match) {
                        return $match['entitySegment']['id'] ?? '<unknown>';
                    },
                    $matches
                ))
            ),
            ['matches' => $matches]
        );

        return true;
    }

    /**
     * @param BusSegment[]|CruiseSegment[]|FerrySegment[]|FlightSegment[]|TrainSegment[]|TransferSegment[] $schemaSegments
     */
    private function getBestMatch(EntityTripSegment $entitySegment, array $schemaSegments): ?array
    {
        $bestMatch = null;

        foreach ($schemaSegments as $schemaSegment) {
            $confidence = $this->segmentMatcher->match($entitySegment, $schemaSegment);

            if (
                $confidence >= self::CONFIDENCE_THRESHOLD
                && (
                    is_null($bestMatch) || $confidence > $bestMatch['confidence']
                )
            ) {
                $bestMatch = [
                    'schemaSegment' => $schemaSegment,
                    'confidence' => $confidence,
                ];
            }
        }

        return $bestMatch;
    }
}
