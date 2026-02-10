<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\Itineraries;

use AwardWallet\MainBundle\Entity\Repositories\TripRepository;
use AwardWallet\MainBundle\Entity\Repositories\TripsegmentRepository;
use AwardWallet\MainBundle\Entity\Trip as EntityFlight;
use AwardWallet\MainBundle\Entity\Tripsegment as EntityFlightSegment;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Itineraries\FlightMatcher;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Segments\FlightSegmentMatcher;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Segments\SegmentMatcherInterface;
use AwardWallet\MainBundle\Loyalty\AccountSaving\ProcessingReport;
use AwardWallet\MainBundle\Loyalty\AccountSaving\SavingOptions;
use AwardWallet\MainBundle\Service\DoctrineRetryHelper;
use AwardWallet\MainBundle\Service\FlightInfo\FlightInfo;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\FlightConverter;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\ItinerarySegmentSchema2EntityConverterInterface;
use AwardWallet\MainBundle\Service\Overlay\Blender;
use AwardWallet\MainBundle\Timeline\Diff\ItineraryTracker;
use AwardWallet\Schema\Itineraries\Flight;
use AwardWallet\Schema\Itineraries\FlightSegment as SchemaFlightSegment;
use AwardWallet\Schema\Itineraries\Itinerary;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class FlightProcessor extends ItineraryProcessor
{
    /**
     * @var TripsegmentRepository
     */
    private $segmentRepository;

    /**
     * @var FlightSegmentMatcher
     */
    private $segmentMatcher;

    /**
     * @var Blender
     */
    private $overlayBlender;
    /**
     * @var FlightInfo
     */
    private $flightInfo;

    /**
     * FlightProcessor constructor.
     */
    public function __construct(
        TripRepository $repository,
        FlightConverter $converter,
        FlightMatcher $matcher,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        ItineraryTracker $tracker,
        TripsegmentRepository $segmentRepository,
        FlightSegmentMatcher $segmentMatcher,
        Blender $overlayBlender,
        FlightInfo $flightInfo,
        NamesMatcher $namesMatcher,
        EventDispatcherInterface $eventDispatcher,
        DoctrineRetryHelper $doctrineRetryHelper
    ) {
        parent::__construct(
            Flight::class,
            $repository,
            $converter,
            $matcher,
            $entityManager,
            $logger,
            $tracker,
            $namesMatcher,
            $eventDispatcher,
            $doctrineRetryHelper
        );
        $this->segmentRepository = $segmentRepository;
        $this->segmentMatcher = $segmentMatcher;
        $this->overlayBlender = $overlayBlender;
        $this->flightInfo = $flightInfo;
    }

    public function process($schemaItinerary, SavingOptions $options): ProcessingReport
    {
        if (!$schemaItinerary instanceof $this->supportedClass) {
            return new ProcessingReport([], [], []);
        }
        /** @var Flight $schemaItinerary */
        $this->filterWaitlisted($schemaItinerary);
        $report = parent::process($schemaItinerary, $options);
        /** @var EntityFlight[] $processedFlights */
        $processedFlights = array_merge($report->getAdded(), $report->getUpdated());

        foreach ($processedFlights as $processedFlight) {
            foreach ($processedFlight->getSegments() as $segment) {
                $this->flightInfo->applyToTripsegment($segment);
                $this->overlayBlender->applyToTripSegment($segment);
            }
        }

        // If we failed to process the flight itinerary, then at least update segments individually
        if (empty($processedFlights)) {
            foreach ($schemaItinerary->segments as $schemaSegment) {
                $this->processSegment($schemaItinerary, $schemaSegment, $options);
            }
        }

        return $report;
    }

    private function processSegment(Itinerary $schema, SchemaFlightSegment $schemaSegment, SavingOptions $options): void
    {
        $candidates = $this->segmentRepository->findMatchingCandidatesForFlight($options->getOwner()->getUser(), $schemaSegment);
        $bestMatch = $this->findBestMatch($schemaSegment, $candidates);

        if (null === $bestMatch) {
            return;
        }

        /** @var ItinerarySegmentSchema2EntityConverterInterface $segmentConverter */
        $segmentConverter = $this->converter;
        $segmentConverter->convertSegment(
            $schema,
            $schemaSegment,
            $bestMatch->getTripid(),
            $bestMatch,
            $options
        );
        $this->flightInfo->applyToTripsegment($bestMatch);
        $this->overlayBlender->applyToTripSegment($bestMatch);
    }

    private function findBestMatch(SchemaFlightSegment $schemaSegment, array $candidates): ?EntityFlightSegment
    {
        $currentConfidence = 0;
        $bestMatch = null;

        foreach ($candidates as $candidate) {
            $confidence = $this->segmentMatcher->match($candidate, $schemaSegment, SegmentMatcherInterface::ANY);

            if ($confidence > $currentConfidence) {
                $bestMatch = $candidate;
                $currentConfidence = $confidence;
            }
        }

        return $bestMatch;
    }

    /** refs #22343 do not show `waitlisted` segments */
    private function filterWaitlisted(Flight $flight)
    {
        $filtered = [];

        foreach ($flight->segments as $seg) {
            /** @var SchemaFlightSegment $seg */
            if (!in_array(strtolower($seg->status), ['waitlisted', 'waitlist'])) {
                $filtered[] = $seg;
            }
        }
        $flight->segments = $filtered;
    }
}
