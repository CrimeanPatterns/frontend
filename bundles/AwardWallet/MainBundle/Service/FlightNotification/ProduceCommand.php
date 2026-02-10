<?php

namespace AwardWallet\MainBundle\Service\FlightNotification;

use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Service\LogProcessor;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Handler\PsrHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtAssoc;

class ProduceCommand extends Command
{
    public const MAX_TIMEZONE = 15; // timezone-unaware, relatively to baseDate, UTC+14:00, M† and DST
    public const MIN_TIMEZONE = 13; // timezone-unaware, relatively to baseDate,  UTC-12:00, Y and DST

    public static $defaultName = 'aw:flight-notification:produce';

    private LoggerInterface $logger;

    private EntityManagerInterface $em;

    private Connection $replicaConnection;

    private OffsetHandler $offsetHandler;

    private Producer $producer;

    private int $processedSegments = 0;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $em,
        Connection $unbufConnection,
        OffsetHandler $offsetHandler,
        Producer $producer
    ) {
        parent::__construct();

        $logProcessor = new LogProcessor(null, [], [], ['ts:%d!ts']);
        $this->logger = new Logger('flight_notification_command', [new PsrHandler($logger)], [$logProcessor]);
        $this->em = $em;
        $this->replicaConnection = $unbufConnection;
        $this->offsetHandler = $offsetHandler;
        $this->producer = $producer;
    }

    protected function configure()
    {
        parent::configure();

        $this
            ->addOption('baseDate', 'b', InputOption::VALUE_REQUIRED, 'base date for calculations', 'now')
            ->addOption('userId', 'u', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'filter by userId')
            ->addOption('providerId', 'p', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'filter by providerId')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'dry run')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->processedSegments = 0;
        $this->logger->info('flights notifications prepare start');

        $baseDate = new \DateTime($input->getOption('baseDate'));
        $this->logger->info(sprintf('base date: [%s]', $baseDate->format('c')));
        $dateDiff = $baseDate->getTimestamp() - time();

        if ($usersIds = $input->getOption('userId')) {
            $usersIds = array_map('intval', $usersIds);
            $this->logger->info(sprintf('filter by userId: [%s]', implode(', ', $usersIds)));
        }

        if ($providersIds = $input->getOption('providerId')) {
            $providersIds = array_map('intval', $providersIds);
            $this->logger->info(sprintf('filter by providerId: [%s]', implode(', ', $providersIds)));
        }

        $dryRun = !empty($input->getOption('dry-run'));

        if ($dryRun) {
            $this->logger->info('dry run');
        }

        foreach ($this->fetch($this->getQuery($baseDate, $usersIds)) as $segment) {
            $context = ['ts' => $segment->getId()];

            if (!$dryRun) {
                $result = $this->producer->publish(
                    $segment,
                    new \DateTime('@' . (time() + $dateDiff)),
                    function (OffsetStatus $offsetStatus) use ($providersIds) {
                        return !$providersIds || in_array($offsetStatus->getProviderId(), $providersIds);
                    }
                );
            } else {
                $result = true;
            }

            if ($result) {
                $this->processedSegments++;
            }
        }

        $this->logger->info(sprintf('processed %d segments', $this->processedSegments));
        $output->writeln('done.');

        return 0;
    }

    /**
     * @return iterable<Tripsegment>
     */
    private function fetch($query): iterable
    {
        $tsRep = $this->em->getRepository(Tripsegment::class);

        return stmtAssoc($query)
            ->onNthAndLast(100, function () {
                $this->em->clear();
                $this->logger->info("processed {$this->processedSegments} segments, mem: " . Helper::formatMemory(memory_get_usage(true)));
            })
            ->map(function (array $row) use ($tsRep) {
                return $tsRep->find($row['TripSegmentID']);
            })
            ->filterNotNull();
    }

    private function getQuery(\DateTimeInterface $baseDate, ?array $usersIds)
    {
        $now = $baseDate->getTimestamp();
        $offsets = $this->getProvidersOffsets();
        $queryParams = [
            [':category', TRIP_CATEGORY_AIR, \PDO::PARAM_INT],
        ];

        if ($usersIds) {
            $usersCondition = ' AND t.UserID IN (:usersIds)';
            $queryParams[] = [':usersIds', $usersIds, Connection::PARAM_INT_ARRAY];
        } else {
            $usersCondition = '';
        }

        $ordNumber = 0;
        $subQuerySql = [];

        foreach ($offsets as $offsetData) {
            $offset = $offsetData['offset'];
            $deadline = $offsetData['deadline'];
            $start = $now + $deadline - (self::MAX_TIMEZONE * 60 * 60);
            $end = $now + $offset + OffsetHandler::PREPARE_OFFSET + (self::MIN_TIMEZONE * 60 * 60);
            $queryParams[] = [
                ":startDate{$ordNumber}",
                date('Y-m-d H:i:s', $start),
                \PDO::PARAM_STR,
            ];
            $queryParams[] = [
                ":endDate{$ordNumber}",
                date('Y-m-d H:i:s', $end),
                \PDO::PARAM_STR,
            ];
            $queryParams[] = [
                ":offset{$ordNumber}",
                round($offset / 60 / 60, 2),
                \PDO::PARAM_STR,
            ];

            $subQuerySql[] = "
                SELECT
                    ts.TripSegmentID,
                    :offset{$ordNumber} AS Offset
                FROM
                    TripSegment ts
                    JOIN Trip t ON ts.TripID = t.TripID
                WHERE
                    t.Category = :category
                    AND ts.DepDate >= :startDate{$ordNumber}
                    AND ts.DepDate <= :endDate{$ordNumber}
                    $usersCondition
            ";

            $ordNumber++;
        }

        $subQuerySql = implode(' UNION ', $subQuerySql);
        $params = [];
        $types = [];

        foreach ($queryParams as [$name, $value, $type]) {
            $params[$name] = $value;
            $types[$name] = $type;
        }

        $queryStartTime = microtime(true);
        $query = $this->replicaConnection->executeQuery(
            $querySql = "
                SELECT 
                    TripSegmentID
                FROM (
                    {$subQuerySql}
                ) trips
                ORDER BY TripSegmentID
            ",
            $params,
            $types
        );

        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            } else {
                $value = $this->replicaConnection->quote($value);
            }

            if (substr($key, 0, 1) !== ':') {
                $key = ':' . $key;
            }
            $querySql = str_replace($key, $value, $querySql);
        }

        $this->logger->debug($querySql);
        $this->logger->info(sprintf('query time: %s sec', round(microtime(true) - $queryStartTime, 5)));

        return $query;
    }

    private function getProvidersOffsets(): array
    {
        $result = [];

        foreach ($this->offsetHandler->getOffsetMap() as $categories) {
            foreach ($categories as $offsets) {
                foreach ($offsets as $kind => $offset) {
                    $secOffset = (int) ceil($offset * 60 * 60);
                    $deadline = $this->offsetHandler->getDeadline($offset, $offsets);
                    $key = sprintf('%s-%d-%d', $kind, $secOffset, $deadline);
                    $result[$key] = [
                        'offset' => $secOffset,
                        'deadline' => $deadline,
                    ];
                }
            }
        }

        return $result;
    }
}
