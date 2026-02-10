<?php

namespace AwardWallet\MainBundle\Service\Overlay;

use AwardWallet\MainBundle\Entity\Repositories\TripsegmentRepository;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Loyalty\Resources\Itineraries\FlightSegment;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Tags;
use AwardWallet\MainBundle\Service\FlightStats\AirlineConverter;
use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\API\Model\FlightWithStatus;
use AwardWallet\MainBundle\Timeline\Diff\ItineraryTracker;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;

class Writer
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var \Doctrine\DBAL\Driver\Statement
     */
    private $updateOverlayQuery;

    /**
     * @var ItineraryTracker
     */
    private $tracker;

    /**
     * @var \Doctrine\DBAL\Driver\Statement
     */
    private $overlayQuery;

    /**
     * @var CacheManager
     */
    private $cache;

    /**
     * @var TripsegmentRepository
     */
    private $tripSegmentRepository;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var AirlineConverter
     */
    private $airlineConverter;

    public function __construct(
        Connection $connection,
        LoggerInterface $logger,
        SerializerInterface $serializer,
        ItineraryTracker $tracker,
        CacheManager $cache,
        AirlineConverter $airlineConverter,
        TripsegmentRepository $tripSegmentRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->tracker = $tracker;
        $this->cache = $cache;
        $this->airlineConverter = $airlineConverter;
        $this->tripSegmentRepository = $tripSegmentRepository;
        $this->entityManager = $entityManager;

        $this->overlayQuery = $connection->prepare(
            "select Data from Overlay where Kind = 'S' and ID = :id and Source = :source");
        $this->updateOverlayQuery = $connection->prepare("
        insert into Overlay(
            Kind,
            ID,
            Source,
            Data,
            CreateDate,
            UpdateDate,
            ExpirationDate
        )
        values(
            :kind,
            :id,
            :source,
            :data,
            now(),
            now(),
            adddate(now(), 90)
        )
        on duplicate key update
            Data = :data,
            UpdateDate = now(),
            ExpirationDate = adddate(now(), 90) 
        ");
    }

    /**
     * @return array ChangeSet of the TripSegment
     */
    public function updateTripSegment(Tripsegment $segment, FlightSegment $flightSegment)
    {
        $this->logger->info("updating trip segment {$segment->getTripsegmentid()}");
        $oldTripProperties = $this->tracker->getProperties($segment->getTripid()->getIdString());
        $changedProperties = [];
        $isDifferent = function ($oldValue, $newValue) {
            return !empty($newValue) && $newValue !== $oldValue;
        };

        if ($isDifferent($segment->getDepartureDate(), $flightSegment->departure->localDateTime)) {
            if (null !== $segment->getDepartureDate()) {
                $changedProperties[] = 'DepartureDate';
            }
            $segment->setDepartureDate(new \DateTime($flightSegment->departure->localDateTime));
        }

        if ($isDifferent($segment->getArrivalDate(), $flightSegment->arrival->localDateTime)) {
            if (null !== $segment->getArrivalDate()) {
                $changedProperties[] = 'ArrivalDate';
            }
            $segment->setArrivalDate(new \DateTime($flightSegment->arrival->localDateTime));
        }

        if ($isDifferent($segment->getBaggageClaim(), $flightSegment->arrival->baggage)) {
            if (null !== $segment->getBaggageClaim()) {
                $changedProperties[] = 'BaggageClaim';
            }
            $segment->setBaggageClaim($flightSegment->arrival->baggage);
        }

        if ($isDifferent($segment->getDepartureGate(), $flightSegment->departure->gate)) {
            if (null !== $segment->getDepartureGate()) {
                $changedProperties[] = 'DepartureGate';
            }
            $segment->setDepartureGate($flightSegment->departure->gate);
        }

        if ($isDifferent($segment->getArrivalGate(), $flightSegment->arrival->gate)) {
            if (null !== $segment->getArrivalGate()) {
                $changedProperties[] = 'ArrivalGate';
            }
            $segment->setArrivalGate($flightSegment->arrival->gate);
        }

        if ($isDifferent($segment->getDepartureTerminal(), $flightSegment->departure->terminal)) {
            if (null !== $segment->getDepartureTerminal()) {
                $changedProperties[] = 'DepartureTerminal';
            }
            $segment->setDepartureTerminal($flightSegment->departure->terminal);
        }

        if ($isDifferent($segment->getArrivalTerminal(), $flightSegment->arrival->terminal)) {
            if (null !== $segment->getArrivalTerminal()) {
                $changedProperties[] = 'ArrivalTerminal';
            }
            $segment->setArrivalTerminal($flightSegment->arrival->terminal);
        }

        if (!$segment->getTripid()->getModified()) {
            $segment->getTripid()->setLastParseDate(new \DateTime());
        }
        $this->entityManager->flush();

        $this->tracker->recordChanges($oldTripProperties, $segment->getTripid()->getIdString(),
            $segment->getTripid()->getUser()->getId(), true);

        $tag = Tags::getTimelineKey($segment->getTripid()->getUser()->getId());
        $this->logger->info("invalidating cache", ["tag" => $tag]);
        $this->cache->invalidateTags([$tag]);

        return $changedProperties;
    }

    public function updateOverlay(FlightWithStatus $flight, FlightSegment $flightSegment, string $source): void
    {
        $data = $this->serializer->serialize($flightSegment, 'json');

        if ($this->isEmptyJson($data)) {
            $this->logger->warning("no data, do not add overlay");

            return;
        }

        $id = $this->getOverlayID($flight);
        $data = $this->blendWithExisting($id, $source, $data);
        $row = [
            'kind' => 'S',
            'id' => $id,
            'source' => $source,
            'data' => $data,
        ];
        $this->logger->info("updating trip segment overlay", [$row]);
        $this->updateOverlayQuery->execute($row);
    }

    private function getOverlayID(FlightWithStatus $flightWithStatus)
    {
        $iata = $flightWithStatus->getBookedAirlineIataCode();

        if (empty($iata)) {
            $iata = $this->airlineConverter->FSCodeToIata($flightWithStatus->getBookedAirlineCode());
        }
        $flightNumber = $flightWithStatus->getFlightNumber();
        $departureCode = $flightWithStatus->getDeparture()->getAirportCode();
        $departureDate = (new \DateTime($flightWithStatus->getDeparture()->getDateTime()))->format('Y-m-d\\TH:i');

        return "$iata.$flightNumber.$departureCode.$departureDate";
    }

    private function blendWithExisting($id, $source, $data)
    {
        $this->overlayQuery->execute(["id" => $id, "source" => $source]);
        $existing = $this->overlayQuery->fetchColumn();

        if (!empty($existing)) {
            $existing = json_decode($existing, true);
            $data = json_decode($data, true);
            $data = array_replace_recursive($existing, $data);

            return json_encode($data, JSON_FORCE_OBJECT);
        } else {
            return $data;
        }
    }

    private function isEmptyJson($data)
    {
        $data = json_decode($data, true);

        foreach ($data as $key => $value) {
            if (!empty($value)) {
                return false;
            }
        }

        return true;
    }
}
