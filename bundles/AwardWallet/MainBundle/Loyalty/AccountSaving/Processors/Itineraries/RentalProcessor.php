<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\Itineraries;

use AwardWallet\MainBundle\Entity\Repositories\RentalRepository;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Itineraries\RentalMatcher;
use AwardWallet\MainBundle\Service\DoctrineRetryHelper;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\RentalConverter;
use AwardWallet\MainBundle\Timeline\Diff\ItineraryTracker;
use AwardWallet\Schema\Itineraries\CarRental;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class RentalProcessor extends ItineraryProcessor
{
    public function __construct(
        RentalRepository $repository,
        RentalConverter $converter,
        RentalMatcher $matcher,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        ItineraryTracker $tracker,
        NamesMatcher $namesMatcher,
        EventDispatcherInterface $eventDispatcher,
        DoctrineRetryHelper $doctrineRetryHelper
    ) {
        parent::__construct(
            CarRental::class,
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
