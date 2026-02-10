<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\Common\Itineraries\FlightSegment;
use AwardWallet\Common\Parsing\Filter\FlightStats\FlightNumberFilter;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Loyalty\Resources\Itineraries\FlightPoint;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FlightStatsFixFlightNumberCommand extends Command
{
    public const DEFAULT_LIMIT = 5000;
    protected static $defaultName = 'aw:flight_stats:fix_flight_number';

    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;
    private FlightNumberFilter $flightNumberFilter;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        FlightNumberFilter $flightNumberFilter
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->flightNumberFilter = $flightNumberFilter;
    }

    protected function configure()
    {
        $this
            ->setDescription('Search for trip segments without a flight number and try to find it on flightStats')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Limit number of segments to update', self::DEFAULT_LIMIT);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = intval($input->getOption('limit'));

        $segments = $this->entityManager->getRepository(Tripsegment::class)->findActualSegments($limit);

        $message = "Found " . count($segments) . " segments";
        $this->logger->info($message);

        $filtered = [];

        /** @var Tripsegment $segment */
        foreach ($segments as $segment) {
            $flightSegment = $this->tripsegmentToFlightSegmentTransformer($segment);
            $this->flightNumberFilter->filterTripSegment(null, $flightSegment);

            if (null !== $flightSegment->flightNumber) {
                $segment->setFlightNumber($flightSegment->flightNumber);
                $filtered[] = $segment->getId();
            }
        }

        $this->entityManager->flush();

        $message = "Fixed " . count($filtered) . " segments";
        $this->logger->debug("Fixed IDs: " . implode(', ', $filtered));
        $this->logger->info($message);

        return 0;
    }

    /**
     * @return FlightSegment
     */
    private function tripsegmentToFlightSegmentTransformer(Tripsegment $tripsegment)
    {
        $flightSegment = new FlightSegment($this->logger);
        $flightSegment->departure = new FlightPoint($this->logger);
        $flightSegment->arrival = new FlightPoint($this->logger);
        $flightSegment->departure->localDateTime = $tripsegment->getDepdate()->format('Y-m-d\TH:i:s');
        $flightSegment->departure->airportCode = $tripsegment->getDepcode();
        $flightSegment->arrival->airportCode = $tripsegment->getArrcode();

        return $flightSegment;
    }
}
