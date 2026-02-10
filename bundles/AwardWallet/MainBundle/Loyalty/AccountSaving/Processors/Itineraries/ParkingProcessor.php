<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\Itineraries;

use AwardWallet\MainBundle\Entity\Repositories\ParkingRepository;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Itineraries\ParkingMatcher;
use AwardWallet\MainBundle\Service\DoctrineRetryHelper;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\ParkingConverter;
use AwardWallet\MainBundle\Timeline\Diff\ItineraryTracker;
use AwardWallet\Schema\Itineraries\Parking;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ParkingProcessor extends ItineraryProcessor
{
    public function __construct(
        ParkingRepository $repository,
        ParkingConverter $converter,
        ParkingMatcher $matcher,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        ItineraryTracker $tracker,
        NamesMatcher $namesMatcher,
        EventDispatcherInterface $eventDispatcher,
        DoctrineRetryHelper $doctrineRetryHelper
    ) {
        parent::__construct(
            Parking::class,
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
