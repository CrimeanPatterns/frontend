<?php

namespace AwardWallet\MainBundle\Service\RA\Flight;

use AwardWallet\MainBundle\Service\MileValue\CalcMileValueCommand;
use AwardWallet\MainBundle\Service\MileValue\Constants;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncCommand extends Command
{
    public static $defaultName = 'aw:ra:flight-sync';

    private Connection $connection;

    private EntityManagerInterface $entityManager;

    private LoggerInterface $logger;

    private FlightDealSubscriber $flightDealSubscriber;

    public function __construct(
        Connection $connection,
        EntityManagerInterface $entityManager,
        LoggerFactory $loggerFactory,
        FlightDealSubscriber $flightDealSubscriber
    ) {
        parent::__construct();

        $this->connection = $connection;
        $this->entityManager = $entityManager;
        $this->logger = $loggerFactory->createLogger($loggerFactory->createProcessor([
            'class' => 'SyncCommand',
        ]));
        $this->flightDealSubscriber = $flightDealSubscriber;
    }

    protected function configure()
    {
        $this->setDescription('Sync flight queries');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->logger->info('start sync queries');

        $stmt = $this->connection->executeQuery("
            SELECT MileValueID, 'Q' AS Type
            FROM RAFlightSearchQuery
            WHERE MileValueID IS NOT NULL
            
            UNION 
            
            SELECT
                mv.MileValueID,
                'M' AS Type
            FROM 
                MileValue mv
                LEFT JOIN RAFlightSearchQuery fsq ON fsq.MileValueID = mv.MileValueID
                JOIN Trip t ON t.TripID = mv.TripID
            WHERE
                fsq.RAFlightSearchQueryID IS NULL
                AND t.Hidden = 0
                AND t.Cancelled = 0
                AND mv.DepDate > NOW() + INTERVAL 3 DAY
                AND mv.RouteType IN (:routeTypes)
                AND mv.Status IN (:statuses)
            ORDER BY MileValueID
        ", [
            'routeTypes' => [Constants::ROUTE_TYPE_ONE_WAY, Constants::ROUTE_TYPE_MULTI_CITY],
            'statuses' => [CalcMileValueCommand::STATUS_NEW, CalcMileValueCommand::STATUS_GOOD],
        ], [
            'routeTypes' => Connection::PARAM_STR_ARRAY,
            'statuses' => Connection::PARAM_STR_ARRAY,
        ]);

        $startTime = microtime(true);
        $batchStartTime = $startTime;
        $processed = 0;
        $processedQueries = 0;
        $processedTrips = 0;

        while ($row = $stmt->fetchAssociative()) {
            $this->flightDealSubscriber->syncByMileValue($row['MileValueID']);

            if ($row['Type'] === 'Q') {
                $processedQueries++;
            } else {
                $processedTrips++;
            }

            $processed++;

            if (($processed % 100) == 0) {
                $this->entityManager->clear();
                $now = microtime(true);
                $speed = round(100 / ($now - $batchStartTime), 2);
                $this->logger->info(sprintf('processed %d milevalues, mem: %s Mb, speed: %s mv/s',
                    $processed,
                    round(memory_get_usage(true) / 1024 / 1024, 1),
                    $speed
                ));
                $batchStartTime = $now;
            }
        }

        $totalTimeSec = microtime(true) - $startTime;

        if ($totalTimeSec < 60) {
            $totalTime = round($totalTimeSec, 2) . ' sec';
        } elseif ($totalTimeSec < 3600) {
            $totalTime = round($totalTimeSec / 60, 2) . ' min';
        } else {
            $totalTime = round($totalTimeSec / 3600, 2) . ' h';
        }

        $this->logger->info(sprintf('sync queries processed, time: %s', $totalTime), [
            'queries' => $processedQueries,
            'trips' => $processedTrips,
        ]);

        return 0;
    }
}
