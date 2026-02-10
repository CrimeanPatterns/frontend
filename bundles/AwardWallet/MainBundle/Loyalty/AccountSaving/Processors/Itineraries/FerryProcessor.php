<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\Itineraries;

use AwardWallet\MainBundle\Entity\Repositories\TripRepository;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Itineraries\FerryMatcher;
use AwardWallet\MainBundle\Service\DoctrineRetryHelper;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\FerryConverter;
use AwardWallet\MainBundle\Timeline\Diff\ItineraryTracker;
use AwardWallet\Schema\Itineraries\Ferry;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class FerryProcessor extends ItineraryProcessor
{
    public function __construct(
        TripRepository $repository,
        FerryConverter $converter,
        FerryMatcher $matcher,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        ItineraryTracker $tracker,
        NamesMatcher $namesMatcher,
        EventDispatcherInterface $eventDispatcher,
        DoctrineRetryHelper $doctrineRetryHelper
    ) {
        parent::__construct(
            Ferry::class,
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
