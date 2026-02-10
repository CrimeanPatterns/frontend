<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter;

use AwardWallet\MainBundle\Entity\Trip as EntityTrip;
use AwardWallet\MainBundle\Entity\Tripsegment as EntityTripSegment;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Segments\SegmentMatcherInterface;
use AwardWallet\MainBundle\Loyalty\AccountSaving\SavingOptions;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\Validator;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;

abstract class AbstractSegmentConverter extends AbstractConverter implements ItinerarySegmentSchema2EntityConverterInterface
{
    protected SegmentMatcherInterface $segmentMatcher;

    private Validator $sourcesValidator;

    public function __construct(
        LoggerFactory $loggerFactory,
        BaseConverter $baseConverter,
        Helper $helper,
        SegmentMatcherInterface $segmentMatcher,
        Validator $sourcesValidator
    ) {
        parent::__construct($loggerFactory, $baseConverter, $helper);

        $this->segmentMatcher = $segmentMatcher;
        $this->sourcesValidator = $sourcesValidator;
    }

    protected function updateSegments(
        EntityTrip $trip,
        SchemaItinerary $schemaItinerary,
        SavingOptions $options
    ) {
        $matches = [];
        $unmatchedSchemaSegments = $schemaItinerary->segments ?? [];
        /** @var EntityTripSegment[] $unmatchedEntitySegments */
        $unmatchedEntitySegments = $trip->getSegments()->toArray();

        foreach ($schemaItinerary->segments ?? [] as $schemaSegment) {
            $bestMatch = $this->findBestMatch($schemaSegment, $unmatchedEntitySegments);

            if (!is_null($bestMatch)) {
                $matches[] = ['schemaSegment' => $schemaSegment, 'entitySegment' => $bestMatch];
                $unmatchedSchemaSegments = array_filter($unmatchedSchemaSegments, fn ($nextSegment) => $nextSegment !== $schemaSegment);
                $unmatchedEntitySegments = array_filter($unmatchedEntitySegments, fn ($nextSegment) => $nextSegment !== $bestMatch);
            }
        }

        // update matched
        foreach ($matches as $match) {
            /** @var EntityTripSegment $entitySegment */
            $entitySegment = $match['entitySegment'];
            $schemaSegment = $match['schemaSegment'];

            // must be unhidden before update
            // to allow update hide cancelled segments
            if ($entitySegment->isHiddenByUpdater() || $options->isInitializedByUser()) {
                $entitySegment->unhide();
            }
            $this->convertSegment($schemaItinerary, $schemaSegment, $trip, $entitySegment, $options);
        }

        // create new
        if (empty($schemaItinerary->cancelled)) { // remove after implementation of filtering on email side
            foreach ($unmatchedSchemaSegments as $newSchemaSegment) {
                $trip->addSegment(
                    $this->convertSegment(
                        $schemaItinerary,
                        $newSchemaSegment,
                        $trip,
                        null,
                        $options
                    )
                );
            }
        }

        // remove obsolete
        foreach ($unmatchedEntitySegments as $unmatchedEntitySegment) {
            // Do not remove segments in partial updates except when source is the same
            $sources = $this->sourcesValidator->getLiveSources($unmatchedEntitySegment->getSources());
            $unmatchedEntitySegment->setSources($sources);

            if (
                !$unmatchedEntitySegment->isUndeleted()
                && (
                    !$options->isPartialUpdate()
                    || (count($sources) === 1 && isset($sources[$options->getSource()->getId()]))
                    || (
                        count($matches) === 0
                        && $this->isSameRouteWithDifferentStops($trip, $schemaItinerary, $options)
                    )
                )
            ) {
                $unmatchedEntitySegment->cancel();
            }
        }
    }

    private function findBestMatch($schemaSegment, array $candidates): ?EntityTripSegment
    {
        $currentConfidence = 0;
        $bestMatch = null;

        foreach ($candidates as $candidate) {
            $confidence = $this->segmentMatcher->match($candidate, $schemaSegment, SegmentMatcherInterface::SAME_TRIP);

            if ($confidence > $currentConfidence) {
                $bestMatch = $candidate;
                $currentConfidence = $confidence;
            }
        }

        return $bestMatch;
    }

    private function isSameRouteWithDifferentStops(EntityTrip $trip, SchemaItinerary $schemaItinerary, SavingOptions $options): bool
    {
        $tripSegments = array_values($trip->getSegmentsSorted());
        $tripSegmentCount = count($tripSegments);

        if ($tripSegmentCount === 0) {
            return false;
        }

        $tripStartAirport = $tripSegments[0]->getDepcode();
        $tripStartTime = $tripSegments[0]->getUTCStartDate();
        $tripEndAirport = $tripSegments[$tripSegmentCount - 1]->getArrcode();
        $tripEndTime = $tripSegments[$tripSegmentCount - 1]->getUTCEndDate();

        $schemaSegments = array_values($schemaItinerary->segments ?? []);
        $schemaTripSegmentCount = count($schemaSegments);

        if ($schemaTripSegmentCount === 0) {
            return false;
        }

        $schemaTripStartSegment = $this->convertSegment(
            $schemaItinerary,
            $schemaSegments[0],
            $trip,
            null,
            $options
        );
        $schemaTripEndSegment = $this->convertSegment(
            $schemaItinerary,
            $schemaSegments[$schemaTripSegmentCount - 1],
            $trip,
            null,
            $options
        );
        $schemaStartAirport = $schemaTripStartSegment->getDepcode();
        $schemaStartTime = $schemaTripStartSegment->getUTCStartDate();
        $schemaEndAirport = $schemaTripEndSegment->getArrcode();
        $schemaEndTime = $schemaTripEndSegment->getUTCEndDate();

        return
            $tripStartAirport
            && $schemaStartAirport
            && $tripEndAirport
            && $schemaEndAirport
            && $tripStartTime
            && $schemaStartTime
            && $tripEndTime
            && $schemaEndTime
            && $tripStartAirport === $schemaStartAirport
            && $tripEndAirport === $schemaEndAirport
            && abs($tripStartTime->getTimestamp() - $schemaStartTime->getTimestamp()) < 86400 // +/- 1 day
            && abs($tripEndTime->getTimestamp() - $schemaEndTime->getTimestamp()) < 86400 // +/- 1 day
        ;
    }
}
