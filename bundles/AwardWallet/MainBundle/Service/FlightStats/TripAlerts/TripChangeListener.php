<?php

namespace AwardWallet\MainBundle\Service\FlightStats\TripAlerts;

use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Event\ItineraryUpdateEvent;
use AwardWallet\MainBundle\Timeline\Diff\Properties;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use Psr\Log\LoggerInterface;

class TripChangeListener
{
    private ProducerInterface $producer;

    private LoggerInterface $logger;

    public function __construct(ProducerInterface $tripAlertsUpdaterProducer, LoggerInterface $tripAlertsLogger)
    {
        $this->producer = $tripAlertsUpdaterProducer;
        $this->logger = $tripAlertsLogger;
    }

    public function onItineraryUpdate(ItineraryUpdateEvent $event)
    {
        if ($this->haveTripSegments(array_merge($event->getAdded(), $event->getChanged()))) {
            $this->logger->info("detected trip changes, will resubscribe", ["userId" => $event->getUserId()]);
            $this->producer->publish(UpdateWorker::createMessage($event->getUserId()), '', ['application_headers' => ['x-delay' => ['I', 5000]]]);
        }
    }

    /**
     * @param Properties[] $properties
     * @return bool
     */
    private function haveTripSegments(array $properties)
    {
        foreach ($properties as $property) {
            $entity = $property->getEntity();

            if ($entity instanceof Trip || $entity instanceof Tripsegment) {
                return true;
            }
        }

        return false;
    }
}
