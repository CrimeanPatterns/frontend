<?php

namespace AwardWallet\MainBundle\Entity\Listener;

use AwardWallet\Common\Entity\Geotag;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\UpdateWorker;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TripsegmentListener
{
    /**
     * @var ContainerInterface
     */
    private $serviceContainer;

    /**
     * @var ProducerInterface
     */
    private $tripalertsUpdater;
    private EntityManagerInterface $entityManager;

    /**
     * TripsegmentListener constructor.
     */
    public function __construct(
        ContainerInterface $serviceContainer,
        ProducerInterface $tripalertsUpdater,
        EntityManagerInterface $entityManager
    ) {
        $this->serviceContainer = $serviceContainer;
        $this->tripalertsUpdater = $tripalertsUpdater;
        $this->entityManager = $entityManager;
    }

    public function postLoad(Tripsegment $tripsegment)
    {
    }

    public function prePersist(Tripsegment $tripsegment)
    {
        if ($tripsegment->getTripid() && Trip::CATEGORY_AIR !== $tripsegment->getTripid()->getCategory()) {
            return;
        }

        if (null === $tripsegment->getDepgeotagid() && !empty($tripsegment->getDepartureAirport())) {
            $tripsegment->setDepgeotagid($this->getGeoTag($tripsegment->getDepartureAirport()->getAircode()));
        }

        if (null === $tripsegment->getArrgeotagid() && !empty($tripsegment->getArrivalAirport())) {
            $tripsegment->setArrgeotagid($this->getGeoTag($tripsegment->getArrivalAirport()->getAircode()));
        }
    }

    public function preUpdate(Tripsegment $tripsegment, PreUpdateEventArgs $event)
    {
        if ($tripsegment->getTripid() && Trip::CATEGORY_AIR !== $tripsegment->getTripid()->getCategory()) {
            return;
        }
        $depCodeHasChanged = function () use ($event) {
            return $event->hasChangedField('depname') || $event->hasChangedField('depcode');
        };
        $arrCodeHasChanged = function () use ($event) {
            return $event->hasChangedField('arrname') || $event->hasChangedField('arrcode');
        };

        if ($depCodeHasChanged() && !empty($tripsegment->getDepartureAirport())) {
            $tripsegment->setDepgeotagid($this->getGeoTag($tripsegment->getDepartureAirport()->getAircode()));
        }

        if ($arrCodeHasChanged() && !empty($tripsegment->getArrivalAirport())) {
            $tripsegment->setArrgeotagid($this->getGeoTag($tripsegment->getArrivalAirport()->getAircode()));
        }
    }

    public function postUpdate(Tripsegment $tripsegment)
    {
        $this->postPersist($tripsegment);
    }

    public function postPersist(Tripsegment $tripsegment)
    {
        if (Trip::CATEGORY_AIR === $tripsegment->getTripid()->getCategory()) {
            // publish with delay to prevent multiple updates for one user
            $this->tripalertsUpdater->publish(
                UpdateWorker::createMessage($tripsegment->getTripid()->getUser()->getUserid()),
                '',
                ['application_headers' => ['x-delay' => ['I', 5000]]]
            );
        }
    }

    /**
     * @return Geotag|null
     */
    private function getGeoTag(string $airportCode)
    {
        $arrivalGeoTag = FindGeoTag($airportCode, null, GEOTAG_TYPE_AIRPORT);

        if (null !== $arrivalGeoTag) {
            return $this->entityManager->getRepository(Geotag::class)->find($arrivalGeoTag['GeoTagID']);
        }
    }
}
