<?php

namespace AwardWallet\MainBundle\Command;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveDuplicatesRAFlightCommand extends Command
{
    public static $defaultName = 'aw:remove-duplicates-ra-flight';

    private LoggerInterface $logger;

    private $connection;

    private $replicaUnbufferedConnection;

    public function __construct(
        Connection $connection,
        Connection $replicaUnbufferedConnection,
        LoggerInterface $logger
    ) {
        parent::__construct();

        $this->connection = $connection;
        $this->replicaUnbufferedConnection = $replicaUnbufferedConnection;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this
            ->setDescription('Remove duplicates from  RAFlight for adding new unique key');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('START: ' . date('Y-m-d H:i:s'));

        $q = $this->replicaUnbufferedConnection->executeQuery(/** @lang MySQL */ "
            SELECT 
                Provider, Airlines, Cabins, FareClasses, AwardType, Route, FromAirport, ToAirport, MileCost, Taxes, DaysBeforeDeparture,
                DepartureDate, ArrivalDate, IsFastest, IsCheapest, Passengers, ClassOfService, SegmentClassOfService, RAFlightID
            FROM RAFlight ORDER BY DepartureDate");

        $deleteSql = /** @lang MySQL */
            "DELETE FROM RAFlight WHERE RAFlightID IN (?)";

        $unique = [];
        $toRemoveIds = [];
        $deleted = 0;
        $processed = 0;
        $date = null;

        while ($row = $q->fetchAssociative()) {
            if ($date != substr($row['DepartureDate'], 0, 10)) {
                $unique = [];
                $date = substr($row['DepartureDate'], 0, 10);
                $this->logger->info('check for ' . $date);
            }
            $processed++;

            if ($processed % 10000 == 0) {
                $this->logger->info(sprintf('processed %s', $processed));
            }
            $fields = $row;
            unset($fields['RAFlightID']);
            $fields = array_map('strtolower', $fields);
            $uniqueData = implode('-', $fields);

            if (array_key_exists($uniqueData, $unique)) {
                $toRemoveIds[] = $row['RAFlightID'];
            } else {
                $unique[$uniqueData] = true;
            }

            if (count($toRemoveIds) >= 100) {
                $this->logger->info(json_encode($toRemoveIds));
                $deleted += $this->connection->executeStatement($deleteSql, [$toRemoveIds],
                    [Connection::PARAM_INT_ARRAY]);
                $toRemoveIds = [];
            }
        }

        if (count($toRemoveIds) > 0) {
            $deleted += $this->connection->executeStatement($deleteSql, [$toRemoveIds], [Connection::PARAM_INT_ARRAY]);
        }

        $this->logger->info(sprintf('RAFlight deleted %s/%s', $deleted, $processed));
        $output->writeln('ENDED: ' . date("Y-m-d H:i:s"));

        return 0;
    }
}
