<?php

namespace AwardWallet\MainBundle\Service\MileValue\Async;

use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Event\ItineraryUpdateEvent;
use AwardWallet\MainBundle\Timeline\Diff\Properties;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use Psr\Log\LoggerInterface;

class TripUpdatedSubscriber
{
    private LoggerInterface $logger;
    private Process $asyncProcess;

    public function __construct(LoggerInterface $logger, Process $asyncProcess)
    {
        $this->logger = $logger;
        $this->asyncProcess = $asyncProcess;
    }

    public function onItineraryUpdate(ItineraryUpdateEvent $event)
    {
        $tripIds = [];

        /** @var Properties $properties */
        foreach (array_merge($event->getAdded(), $event->getChanged()) as $properties) {
            $entity = $properties->source->getEntity($properties);

            if ($entity instanceof Trip && $entity->getPricingInfo()->getSpentAwards() > 0) {
                $tripIds[] = $entity->getId();
            }

            if ($entity instanceof Tripsegment && $entity->getTripid()->getPricingInfo()->getSpentAwards() > 0) {
                $tripIds[] = $entity->getTripid()->getId();
            }
        }

        $tripIds = array_unique($tripIds);

        if (count($tripIds) === 0) {
            return;
        }

        $this->logger->info("will check mile value for trips: " . implode(", ", $tripIds));

        foreach ($tripIds as $tripId) {
            $this->asyncProcess->execute(new CalcTripMileValueTask($tripId));
        }
    }
}
