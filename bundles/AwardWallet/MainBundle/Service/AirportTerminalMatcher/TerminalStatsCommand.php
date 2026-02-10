<?php

namespace AwardWallet\MainBundle\Service\AirportTerminalMatcher;

use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\LogProcessor;
use Clock\ClockInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Monolog\Handler\PsrHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TerminalStatsCommand extends Command
{
    public static $defaultName = 'aw:terminal-stats';

    private Connection $connection;

    private Connection $replicaUnbufferedConnection;

    private LoggerInterface $logger;

    private ClockInterface $clock;

    private Statement $selectTerminalQuery;

    private Statement $insertTerminalQuery;

    private Statement $insertTerminalAliasQuery;

    public function __construct(
        Connection $connection,
        Connection $replicaUnbufferedConnection,
        LoggerInterface $logger,
        ClockInterface $clock
    ) {
        parent::__construct();

        $this->connection = $connection;
        $this->replicaUnbufferedConnection = $replicaUnbufferedConnection;
        $logProcessor = new LogProcessor('terminal_stats_command');
        $this->logger = new Logger('terminal_stats_command', [new PsrHandler($logger)], [$logProcessor]);
        $this->clock = $clock;

        $this->selectTerminalQuery = $this->connection->prepare("
            SELECT AirportTerminalID FROM AirportTerminal WHERE AirportCode = ? AND Name = ? LIMIT 1
        ");
        $this->insertTerminalQuery = $this->connection->prepare("
            INSERT INTO AirportTerminal (AirportCode, Name) VALUES (?, ?) ON DUPLICATE KEY UPDATE UpdateDate = ?
        ");
        $this->insertTerminalAliasQuery = $this->connection->prepare("
            INSERT INTO AirportTerminalAlias (AirportTerminalID, Alias) VALUES (?, ?) ON DUPLICATE KEY UPDATE UpdateDate = ?
        ");
    }

    protected function configure()
    {
        parent::configure();

        $this->addOption('clear-terminals', 'c', InputOption::VALUE_NONE, 'deleting old terminals, aliases');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $now = $this->clock->current()->getAsDateTime()->format('Y-m-d H:i:s');
        $q = $this->connection->executeQuery(
            "SELECT * FROM FlightStats WHERE CreateDate < ? ORDER BY CreateDate ASC",
            [$now]
        );
        $processed = 0;

        while ($row = $q->fetchAssociative()) {
            $processed++;
            $depTerminal = $row['DepTerminal'] ?? Matcher::MAIN_TERMINAL;
            $arrTerminal = $row['ArrTerminal'] ?? Matcher::MAIN_TERMINAL;
            $this->insertTerminalQuery->executeStatement([$row['DepCode'], $depTerminal, $now]);
            $depTerminalId = $this->selectTerminalQuery->executeQuery([$row['DepCode'], $depTerminal])->fetchOne();
            $this->insertTerminalQuery->executeStatement([$row['ArrCode'], $arrTerminal, $now]);
            $arrTerminalId = $this->selectTerminalQuery->executeQuery([$row['ArrCode'], $arrTerminal])->fetchOne();
            $depDate = new \DateTime($row['DepDate']);
            $qTs = $this->tripSegmentsQuery(
                $row['DepCode'],
                $row['ArrCode'],
                $depDate,
                array_unique([
                    $row['FlightNumber'],
                    $row['FlightNumber2'],
                ]),
                array_unique([
                    $row['BookedAirline'],
                    $row['OperatingAirline'],
                    $row['PrimaryMarketingAirline'],
                ])
            );

            while ($ts = $qTs->fetchAssociative()) {
                $depTerminalAlias = $this->prepareTerminal($ts['DepartureTerminal']);
                $arrTerminalAlias = $this->prepareTerminal($ts['ArrivalTerminal']);

                if (!StringHandler::isEmpty($depTerminalAlias)) {
                    $this->insertTerminalAliasQuery->executeStatement([$depTerminalId, $depTerminalAlias, $now]);
                }

                if (!StringHandler::isEmpty($arrTerminalAlias)) {
                    $this->insertTerminalAliasQuery->executeStatement([$arrTerminalId, $arrTerminalAlias, $now]);
                }
            }
        }

        $this->connection->executeStatement(
            "DELETE FROM FlightStats WHERE CreateDate < ?",
            [$now]
        );

        $q = $this->connection->executeQuery("
            SELECT
                a.AirportCode, al.Alias
            FROM
                AirportTerminal a
                JOIN AirportTerminalAlias al ON al.AirportTerminalID = a.AirportTerminalID
            GROUP BY a.AirportCode, al.Alias
            HAVING COUNT(*) > 1;
        ");

        while ($row = $q->fetchAssociative()) {
            $this->logger->info(sprintf('removing duplicates, %s, alias: %s', $row['AirportCode'], $row['Alias']));
            $this->connection->executeStatement("
                DELETE aa
                FROM AirportTerminalAlias AS aa INNER JOIN AirportTerminal AS a
                WHERE
                    a.AirportTerminalID = aa.AirportTerminalID
                    AND a.AirportCode = ?
                    AND aa.Alias = ?;
            ", [$row['AirportCode'], $row['Alias']]);
        }

        if ($input->getOption('clear-terminals')) {
            $this->logger->info('deleting old aliases');

            $this->connection->executeQuery("
                DELETE FROM AirportTerminalAlias WHERE UpdateDate < ? - INTERVAL 6 MONTH
            ", [$now]);
        }

        $this->logger->info(sprintf('done, processed flights: %d', $processed));

        return 0;
    }

    private function prepareTerminal(?string $terminal): ?string
    {
        if (is_null($terminal)) {
            return null;
        }

        return trim(preg_replace('/\s{2,}/', ' ', $terminal));
    }

    private function tripSegmentsQuery(
        string $depCode,
        string $arrCode,
        \DateTime $depDate,
        array $flightNumbers,
        array $airlineCodes
    ) {
        return $this->replicaUnbufferedConnection->executeQuery("
            SELECT
                ts.DepartureTerminal,
                ts.ArrivalTerminal
            FROM TripSegment ts
            WHERE
                ts.DepCode = :depCode
                AND ts.ArrCode = :arrCode
                AND ts.ScheduledDepDate = :depDate
                AND ts.FlightNumber IN (:flightNumbers)
                AND (
                    (
                        ts.DepartureTerminal IS NOT NULL
                        AND ts.DepartureTerminal NOT IN (:brokenTerminals)
                    )
                    OR (
                        ts.ArrivalTerminal IS NOT NULL
                        AND ts.ArrivalTerminal NOT IN (:brokenTerminals)
                    )
                )
        ", [
            ':depCode' => $depCode,
            ':arrCode' => $arrCode,
            ':depDate' => $depDate->format('Y-m-d H:i:s'),
            ':flightNumbers' => $this->getFlightNumberVariations($flightNumbers, $airlineCodes),
            ':brokenTerminals' => [
                'terminal', '-', '',
            ],
        ], [
            ':depCode' => \PDO::PARAM_STR,
            ':arrCode' => \PDO::PARAM_STR,
            ':depDate' => \PDO::PARAM_STR,
            ':flightNumbers' => Connection::PARAM_STR_ARRAY,
            ':brokenTerminals' => Connection::PARAM_STR_ARRAY,
        ]);
    }

    /**
     * @param string[] $flightNumbers
     * @param string[] $airlineCodes
     */
    private function getFlightNumberVariations(array $flightNumbers, array $airlineCodes): array
    {
        $flightNumberVariations = [];

        foreach ($flightNumbers as $flightNumber) {
            $flightNumberVariations[] = $flightNumber;
            $flightNumberVariations[] = sprintf('%04s', $flightNumber);
            $flightNumberVariations[] = sprintf('%03s', $flightNumber);
            $flightNumberVariations[] = sprintf('%02s', $flightNumber);

            foreach ($airlineCodes as $airlineIataCode) {
                $flightNumberVariations[] = "{$airlineIataCode}{$flightNumber}";
                $flightNumberVariations[] = "{$airlineIataCode} {$flightNumber}";
                $flightNumberVariations[] = sprintf('%s%04s', $airlineIataCode, $flightNumber);
                $flightNumberVariations[] = sprintf('%s%03s', $airlineIataCode, $flightNumber);
                $flightNumberVariations[] = sprintf('%s%02s', $airlineIataCode, $flightNumber);
            }
        }

        return array_values(array_unique($flightNumberVariations));
    }
}
