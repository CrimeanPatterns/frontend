<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\Itineraries;

use AwardWallet\MainBundle\Entity\Repositories\ReservationRepository;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Itineraries\ReservationMatcher;
use AwardWallet\MainBundle\Service\DoctrineRetryHelper;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\HotelReservationConverter;
use AwardWallet\MainBundle\Timeline\Diff\ItineraryTracker;
use AwardWallet\Schema\Itineraries\HotelReservation;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ReservationProcessor extends ItineraryProcessor
{
    public function __construct(
        ReservationRepository $repository,
        HotelReservationConverter $converter,
        ReservationMatcher $matcher,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        ItineraryTracker $tracker,
        NamesMatcher $namesMatcher,
        EventDispatcherInterface $eventDispatcher,
        DoctrineRetryHelper $doctrineRetryHelper
    ) {
        parent::__construct(
            HotelReservation::class,
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
    }
}
