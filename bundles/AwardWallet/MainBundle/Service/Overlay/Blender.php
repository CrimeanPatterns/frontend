<?php

namespace AwardWallet\MainBundle\Service\Overlay;

use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Loyalty\Resources\Itineraries\FlightSegment;
use Doctrine\DBAL\Connection;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;

class Blender
{
    private $iataQuery;

    private \Memcached $memcached;

    private SerializerInterface $serializer;

    private LoggerInterface $logger;

    public function __construct(Connection $connection, \Memcached $memcached, SerializerInterface $serializer, LoggerInterface $tripAlertsLogger)
    {
        $this->iataQuery = $connection->prepare("select p.IATACode from Provider p where p.ProviderID = :providerId");
        $this->overlayQuery = $connection->prepare("select Source, Data from Overlay where Kind = 'S' and ID = :id");
        $this->memcached = $memcached;
        $this->serializer = $serializer;
        $this->logger = $tripAlertsLogger;
    }

    public function applyToTripSegment(Tripsegment $ts): void
    {
        if (empty($ts->getTripid()->getProvider()) || empty($ts->getTripid()->getProvider()->getIATACode()) || empty($ts->getFlightNumber()) || empty($ts->getDepcode()) || empty($ts->getDepdate())) {
            return;
        }
        $iata = $ts->getTripid()->getProvider()->getIATACode();
        $id = "$iata.{$ts->getFlightNumber()}.{$ts->getDepcode()}.{$ts->getDepdate()->format('Y-m-d\\TH:i')}";
        $this->overlayQuery->execute(['id' => $id]);

        while ($row = $this->overlayQuery->fetch(\PDO::FETCH_ASSOC)) {
            /** @var FlightSegment $overlay */
            $overlay = $this->serializer->deserialize($row['Data'], FlightSegment::class, 'json');
            $this->logger->info("applying overlay", [
                "id" => $id,
                "overlay" => $overlay,
                "segment" => $ts,
                "IATACode" => $iata,
                "FlightNumber" => $ts->getFlightNumber(),
                "DepDate" => $ts->getDepdate()->format("Y-m-d H:i"),
                "DepCode" => $ts->getDepcode(),
            ]);

            if (!empty($overlay->departure->localDateTime)) {
                $ts->setDepartureDate(new \DateTime($overlay->departure->localDateTime));
            }

            if (!empty($overlay->arrival->localDateTime)) {
                $ts->setArrivalDate(new \DateTime($overlay->arrival->localDateTime));
            }

            if (!empty($overlay->arrival->baggage)) {
                $ts->setBaggageClaim($overlay->arrival->baggage);
            }

            if (!empty($overlay->departure->gate)) {
                $ts->setDepartureGate($overlay->departure->gate);
            }

            if (!empty($overlay->arrival->gate)) {
                $ts->setArrivalGate($overlay->arrival->gate);
            }

            if (!empty($overlay->departure->terminal)) {
                $ts->setDepartureTerminal($overlay->departure->terminal);
            }

            if (!empty($overlay->arrival->terminal)) {
                $ts->setArrivalTerminal($overlay->arrival->terminal);
            }
        }
    }
}
