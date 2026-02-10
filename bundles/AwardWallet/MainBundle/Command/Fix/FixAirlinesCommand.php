<?php

namespace AwardWallet\MainBundle\Command\Fix;

use AwardWallet\MainBundle\Entity\Repositories\AirlineRepository;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Service\ProgressLogger;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FixAirlinesCommand extends Command
{
    public static $defaultName = 'aw:fix-airlines';
    /**
     * @var Connection
     */
    private $unbufConnection;
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var AirlineRepository
     */
    private $airlineRepository;
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var Connection
     */
    private $stagingUnbufConnection;
    /**
     * @var OutputInterface
     */
    private $output;
    /**
     * @var InputInterface
     */
    private $input;

    public function __construct(
        Connection $unbufConnection,
        Connection $connection,
        Connection $stagingUnbufConnection,
        LoggerInterface $logger,
        AirlineRepository $airlineRepository,
        EntityManagerInterface $em
    ) {
        $this->unbufConnection = $unbufConnection;
        parent::__construct();
        $this->connection = $connection;
        $this->logger = $logger;
        $this->airlineRepository = $airlineRepository;
        $this->em = $em;
        $this->stagingUnbufConnection = $stagingUnbufConnection;
    }

    public function configure()
    {
        $this
            ->addOption('tripSegmentId', null, InputOption::VALUE_REQUIRED, 'process only this trip segment')
            ->addOption('restore-from-staging', null, InputOption::VALUE_NONE)
            ->addOption('userId', null, InputOption::VALUE_REQUIRED, 'process only this user')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $this->input = $input;

        if ($input->getOption('restore-from-staging')) {
            $this->restoreAirlineNames();
        }
        $this->findAirlineIds();
        $this->output->writeln("done");

        return 0;
    }

    private function findAirlineIds()
    {
        $this->output->writeln("searching airline ids");
        $tsRepo = $this->em->getRepository(Tripsegment::class);
        $airlineByProviderQuery = $this->em->createQuery("select 
            a
        from 
            AwardWallet\MainBundle\Entity\Airline a
            join AwardWallet\MainBundle\Entity\Provider p with a.code = p.IATACode
        where
            p.providerid = :providerId");
        $this->processAirlines(
            $this->unbufConnection,
            "TripSegment.AirlineID is null",
            function (array $row, int $count) use ($tsRepo, $airlineByProviderQuery) {
                $airline = null;

                if (!empty($row['AirlineName'])) {
                    $airline = $this->airlineRepository->search(null, null, $row['AirlineName']);
                }

                if ($airline === null && !empty($row['ProviderID'])) {
                    $airlines = $airlineByProviderQuery->execute(["providerId" => $row['ProviderID']]);

                    if (count($airlines) === 1) {
                        $airline = $airlines[0];
                    }
                }

                if ($airline !== null) {
                    /** @var Tripsegment $ts */
                    $ts = $tsRepo->find($row['TripSegmentID']);

                    if ($ts !== null) {
                        $ts->setAirline($airline, false);
                    }
                }

                if (($count % 100) === 0) {
                    $this->em->flush();
                    $this->em->clear();
                }

                return $airline !== null;
            }
        );
        $this->em->flush();
    }

    private function restoreAirlineNames()
    {
        $this->output->writeln("restoring data from staging");
        $q = $this->connection->prepare("select AirlineID, AirlineName from TripSegment where TripSegmentID = ?");
        $update = $this->connection->prepare("update TripSegment set AirlineName = ? where TripSegmentID = ?");
        $this->processAirlines(
            $this->stagingUnbufConnection,
            "TripSegment.AirlineName is not null",
            function (array $stagingRow) use ($q, $update) {
                $q->execute([$stagingRow['TripSegmentID']]);
                $prodRow = $q->fetch(FetchMode::ASSOCIATIVE);

                if ($prodRow !== false && empty($prodRow['AirlineName']) && empty($prodRow['AirlineID'])) {
                    $update->execute([$stagingRow['AirlineName'], $stagingRow['TripSegmentID']]);

                    return true;
                }

                return false;
            }
        );
        $this->em->flush();
    }

    private function processAirlines(Connection $connection, ?string $where, callable $rowProcessor)
    {
        $this->output->writeln("searching for segments with missing airlineId");
        $filter = "";

        if ($tripSegmentId = $this->input->getOption('tripSegmentId')) {
            $filter .= " and TripSegment.TripSegmentID = $tripSegmentId";
        }

        if ($userId = $this->input->getOption('userId')) {
            $filter .= " and Trip.UserID = $userId";
        }

        if ($where !== null) {
            $filter .= " and $where";
        }
        $q = $connection->executeQuery("select 
            Trip.ProviderID,
            TripSegment.TripSegmentID,
            TripSegment.AirlineName
        from
            TripSegment
            join Trip on TripSegment.TripID = Trip.TripID
        where 
            TripSegment.DepDate < adddate(now(), 365 * 2)
            $filter");

        $progress = new ProgressLogger($this->logger, 10, 30);
        $count = 0;
        $corrected = 0;

        while ($row = $q->fetch(FetchMode::ASSOCIATIVE)) {
            $progress->showProgress("correcting airlines, {$row['TripSegmentID']}, {$row['AirlineName']}", $count);

            if (call_user_func($rowProcessor, $row, $count, $corrected)) {
                $corrected++;
            }
            $count++;
        }
        $this->output->writeln("done fixing missing airlineId, processed: $count, fixed: $corrected");
    }
}
