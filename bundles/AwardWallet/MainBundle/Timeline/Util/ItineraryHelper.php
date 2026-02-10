<?php

namespace AwardWallet\MainBundle\Timeline\Util;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Parking;
use AwardWallet\MainBundle\Entity\Rental;
use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\MainBundle\Entity\Restaurant;
use AwardWallet\MainBundle\Entity\Trip;
use Doctrine\ORM\EntityManagerInterface;

class ItineraryHelper
{
    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    public function getTypeByKind(string $kind): string
    {
        switch ($kind) {
            case Itinerary::KIND_TRIP:
                return 'flight';

            case Itinerary::KIND_RESERVATION:
            case in_array($kind, Reservation::getSegmentMap(), true):
                return 'reservation';

            case Itinerary::KIND_RENTAL:
            case in_array($kind, Rental::getSegmentMap(), true):
                return 'rental';

            case Itinerary::KIND_RESTAURANT:
                return 'event';

            case Itinerary::KIND_PARKING:
            case in_array($kind, Parking::getSegmentMap(), true):
                return 'parking';

            default:
                throw $this->createNotFoundException();
        }
    }

    public function findItinerary(string $type, int $id): ?Itinerary
    {
        switch ($type) {
            case 'reservation':
                return $this->entityManager->getRepository(Reservation::class)->find($id);

            case 'rental':
            case 'taxi_ride':
                return $this->entityManager->getRepository(Rental::class)->find($id);

            case 'event':
                return $this->entityManager->getRepository(Restaurant::class)->find($id);

            case 'parking':
                return $this->entityManager->getRepository(Parking::class)->find($id);

            case 'flight':
                return $this->entityManager->getRepository(Trip::class)->findWithAirports($id);

            case 'bus_ride':
            case 'train_ride':
            case 'ferry_ride':
            case 'cruise':
                return $this->entityManager->getRepository(Trip::class)->find($id);

            default:
                throw new \InvalidArgumentException(sprintf('Unknown itinerary "%s" type', $type));
        }
    }
}
