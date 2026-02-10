<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Processors;

use AwardWallet\MainBundle\Loyalty\AccountSaving\ProcessingReport;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\Itineraries\ItineraryProcessorInterface;
use AwardWallet\MainBundle\Loyalty\AccountSaving\SavingOptions;
use AwardWallet\MainBundle\Service\Locker;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ItinerariesProcessor
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ItineraryProcessorInterface[]
     */
    private $processors = [];
    /**
     * @var Locker
     */
    private $locker;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ItinerariesProcessor constructor.
     */
    public function __construct(EntityManagerInterface $entityManager, Locker $locker, LoggerInterface $logger, iterable $processors)
    {
        $this->entityManager = $entityManager;
        $this->processors = $processors;
        $this->locker = $locker;
        $this->logger = $logger;
    }

    /**
     * @param SchemaItinerary[] $schemaItineraries
     */
    public function save(array $schemaItineraries, SavingOptions $options): ProcessingReport
    {
        $lockKey = "it_proc_" . $options->getOwner()->getUser()->getId();

        if (!$this->locker->acquire($lockKey, 30, 45)) {
            $this->logger->critical("failed to lock", ["lockKey" => $lockKey, "errorMessage" => $this->locker->getLastError()]);

            throw new \Exception("Failed to lock $lockKey");
        }

        try {
            $report = new ProcessingReport();

            foreach ($schemaItineraries as $schemaItinerary) {
                foreach ($this->processors as $processor) {
                    $report = $report->merge($processor->process($schemaItinerary, $options));
                }
            }
            $this->entityManager->flush();
        } finally {
            $this->locker->release($lockKey);
        }

        foreach ($report->getAdded() as $itinerary) {
            $this->logger->info("added itinerary " . ($itinerary->getRealProvider() ? $itinerary->getRealProvider()->getCode() : "null") . " " . $itinerary->getIdString());
        }

        foreach ($report->getRemoved() as $itinerary) {
            $this->logger->info("removed itinerary " . $itinerary->getIdString());
        }

        return $report;
    }
}
