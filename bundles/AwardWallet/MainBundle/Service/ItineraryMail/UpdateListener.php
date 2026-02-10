<?php

namespace AwardWallet\MainBundle\Service\ItineraryMail;

use AwardWallet\MainBundle\Entity\Parking;
use AwardWallet\MainBundle\Entity\Rental;
use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\MainBundle\Entity\Restaurant;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Event\ItineraryUpdateEvent;
use AwardWallet\MainBundle\Service\DelayedProducer;
use AwardWallet\MainBundle\Timeline\Diff\Properties;
use AwardWallet\MainBundle\Timeline\Diff\WhiteList;
use Clock\ClockInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class UpdateListener
{
    public const DELAY = 180000; // ms, 3 minutes

    protected DelayedProducer $delayedProducer;

    protected EntityManagerInterface $em;

    protected LoggerInterface $logger;
    protected bool $enable;

    private ClockInterface $clock;

    public function __construct(
        DelayedProducer $itNotificationDelayedProducer,
        EntityManagerInterface $em,
        LoggerInterface $emailLogger,
        ClockInterface $clock,
        bool $itMailerEnabled
    ) {
        $this->delayedProducer = $itNotificationDelayedProducer;
        $this->em = $em;
        $this->logger = $emailLogger;
        $this->enable = $itMailerEnabled;
        $this->clock = $clock;
    }

    public function onItineraryUpdate(ItineraryUpdateEvent $event)
    {
        if (!$this->enable || $event->isSilent()) {
            return;
        }

        $addedProperties = $event->getAdded();

        if (isset($addedProperties) && sizeof($addedProperties) > 0) {
            $this->processAddedProperties($addedProperties);
        }
        $changedProperties = $event->getChanged();

        if (isset($changedProperties) && sizeof($changedProperties) > 0) {
            $this->processChangedProperties($changedProperties, $event->getChangedOld(), $event->getChangedNames());
        }
    }

    /**
     * @param Properties[] $properties
     */
    protected function processAddedProperties(array $properties)
    {
        $this->logger->info("travel reservations have been added, listener");
        $this->processProperties($properties, 'add');
    }

    /**
     * @param Properties[] $properties
     */
    protected function processChangedProperties(array $properties, array $propertiesOld, array $changedNames)
    {
        $this->logger->info("travel reservations have been updated, listener", ['itineraries' => $changedNames]);

        if (WhiteList::shouldNotify($properties, $propertiesOld, $changedNames, $this->clock->current()->getAsSecondsInt())) {
            $this->processProperties($properties, 'update');
        }
    }

    /**
     * @param Properties[] $properties
     */
    protected function processProperties(array $properties, $mode)
    {
        $now = new \DateTime();
        $users = [];

        foreach ($properties as $property) {
            $it = null;
            $entity = $property->getEntity();

            if (!$entity) {
                $this->logger->info("no entity for added/updated notification");

                continue;
            }

            if ($entity instanceof Tripsegment) {
                $it = $entity->getTripid();

                if ($it->getCopied()) {
                    $this->logger->info("tripsegment copied, skip added/updated notification");

                    continue;
                }
            } elseif (
                $entity instanceof Rental
                || $entity instanceof Reservation
                || $entity instanceof Restaurant
                || $entity instanceof Parking
            ) {
                $it = $entity;

                if ($it->getCopied()) {
                    $this->logger->info("itinerary copied, skip added/updated notification");

                    continue;
                }
            }

            if (isset($it)) {
                $user = $it->getUser();

                if ($mode == 'add') {
                    $user->setItineraryadddate($now);
                } elseif ($mode == 'update') {
                    $user->setItineraryupdatedate($now);
                }
                $users[$user->getUserid()] = $now->getTimestamp();
            } else {
                $this->logger->info("no itinerary for added/updated notification");
            }
        }

        if (!sizeof($users)) {
            $this->logger->info("no users for added/updated notification");

            return;
        }

        $this->em->flush();
        $this->logger->info("travel reservations have been added/updated (delayed publish)", [
            'users_array' => array_keys($users),
            'mode_string' => $mode,
            'delay_int' => self::DELAY,
        ]);

        foreach ($users as $userId => $time) {
            $this->delayedProducer->delayedPublish(
                self::DELAY,
                @serialize(['mode' => $mode, 'userId' => $userId, 'datetime' => $time])
            );
        }
    }
}
