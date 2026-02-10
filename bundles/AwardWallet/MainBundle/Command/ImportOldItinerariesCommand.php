<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\Common\Geo\GoogleGeo;
use AwardWallet\MainBundle\Entity\Repositories\AirlineRepository;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Service\ArchiveAirportFinder;
use AwardWallet\MainBundle\Service\ProgressLogger;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\FetchMode;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class ImportOldItinerariesCommand extends Command
{
    public static $defaultName = 'aw:import-old-itineraries';
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var Connection
     */
    private $archiveConnection;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var Connection
     */
    private $unbufferedArchiveConnection;
    /**
     * @var array
     */
    private $existingRows = [];
    /**
     * @var \Doctrine\DBAL\Statement[]
     */
    private $existingQueries = [];
    /**
     * @var array
     */
    private $tableFields;
    /**
     * @var \Doctrine\DBAL\Statement
     */
    private $geoTagQuery;
    /**
     * @var \Doctrine\DBAL\Statement
     */
    private $archiveGeoTagQuery;
    /**
     * @var GoogleGeo
     */
    private $geo;
    /**
     * @var AirlineRepository
     */
    private $airlineRepository;
    /**
     * @var ArchiveAirportFinder
     */
    private $archiveAirportFinder;
    /**
     * @var int
     */
    private $lastPingDate;

    public function __construct(
        Connection $connection,
        Connection $archiveConnection,
        Connection $unbufferedArchiveConnection,
        LoggerInterface $logger,
        GoogleGeo $geo,
        AirlineRepository $airlineRepository,
        ArchiveAirportFinder $archiveAirportFinder
    ) {
        parent::__construct();
        $this->connection = $connection;
        $this->archiveConnection = $archiveConnection;
        $this->logger = $logger;
        $this->unbufferedArchiveConnection = $unbufferedArchiveConnection;
        $this->geo = $geo;
        $this->airlineRepository = $airlineRepository;
        $this->archiveAirportFinder = $archiveAirportFinder;
    }

    public function configure()
    {
        $this
            ->addOption('userId', null, InputOption::VALUE_REQUIRED)
            ->addOption('load-user-list', null, InputOption::VALUE_REQUIRED, 'load user list from file, one login per line')
            ->addOption('tables', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'tables to import', ['Trip', 'Reservation', 'Rental', 'Restaurant', 'Plan', 'TravelPlan'])
            ->addOption('from-id', null, InputOption::VALUE_REQUIRED, 'import row >= this id')
            ->addOption('before-id', null, InputOption::VALUE_REQUIRED, 'import rows < this id')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info("importing old itineraries");

        $this->prepareQueries();
        $this->loadReferences();
        $this->archiveConnection->executeUpdate("SET SESSION wait_timeout = 86400");
        $this->archiveConnection->executeUpdate("SET SESSION net_read_timeout = 86400");
        $this->archiveConnection->executeUpdate("SET SESSION net_write_timeout = 86400");

        $tables = $input->getOption('tables');
        $this->logger->info("importing tables: " . implode(", ", $tables));

        foreach ($tables as $table) {
            $filter = $this->createSqlFilter($table, $input);

            if ($this->tableExists($table)) {
                if ($table === "Trip") {
                    $this->copyTable(
                        "Trip",
                        "select 
                        t.* from Trip t join TripSegment ts on t.TripID = ts.TripID 
                        where t.Hidden = 0 and ts.Hidden = 0 %filter%
                        group by t.TripID
                        order by t.TripID",
                        $filter,
                        function (int $tripId): int {
                            return $this->copyTable("TripSegment", "select * from TripSegment where TripID = {$tripId}", "", null,
                                false);
                        }
                    );
                } elseif ($table === "TravelPlan") {
                    $this->migrateTravelPlans($filter);
                } else {
                    $this->copyTable($table, "select * from {$table} t where t.Hidden = 0 %filter% order by {$table}ID", $filter);
                }
            }
        }

        $this->logger->info("done");

        return 0;
    }

    private function prepareQueries()
    {
        $this->geoTagQuery = $this->connection->prepare("select GeoTagID from GeoTag where Address = ?");
        $this->archiveGeoTagQuery = $this->connection->prepare("select * from GeoTag where Address = ?");
    }

    private function loadReferences()
    {
        foreach (["Trip", "Reservation", "Rental", "Restaurant", "TripSegment", "GeoTag", "Plan"] as $table) {
            $this->tableFields[$table] = $this->loadTableFields($table);
        }
    }

    private function loadTableFields(string $table): array
    {
        return array_map(function (array $row) {
            return $row["Field"];
        }, $this->connection->executeQuery("describe $table")->fetchAll(FetchMode::ASSOCIATIVE));
    }

    private function createSqlFilter(string $table, InputInterface $input): string
    {
        $result = "";

        if ($userId = $input->getOption('userId')) {
            $result .= " and t.UserID = {$userId}";
        }

        if ($file = $input->getOption('load-user-list')) {
            $userIds = $this->connection->executeQuery("select UserID from Usr where Login in ('" .
                it(explode("\n", file_get_contents($file)))
                    ->map("addslashes")
                    ->joinToString("', '")
                . "')")->fetchAll(FetchMode::COLUMN, 0);
            $result .= " and t.UserID in (" . implode(", ", $userIds) . ")";
        }

        if ($id = $input->getOption('from-id')) {
            $result .= " and t.{$table}ID >= " . (int) $id;
        }

        if ($id = $input->getOption('before-id')) {
            $result .= " and t.{$table}ID < " . (int) $id;
        }

        return $result;
    }

    private function copyTable(string $table, string $sql, string $filter, ?callable $onRowImported = null, $unbuffered = true): int
    {
        $connection = $unbuffered ? $this->unbufferedArchiveConnection : $this->archiveConnection;
        $lastId = 0;
        $rowCount = 0;
        $copiedCount = 0;
        $uniqueErrors = 0;

        do {
            if ($rowCount > 0) {
                $this->logger->warning("connection error, retrying query, last id: {$lastId}");
            }
            $builtSql = str_replace("%filter%", $filter . " and t.{$table}ID > $lastId", $sql);

            if ($unbuffered) {
                $this->logger->info("sql: {$builtSql}");
            }
            $q = $connection->executeQuery($builtSql);

            if ($unbuffered) {
                $progress = new ProgressLogger($this->logger, 100, 30);
            }
            $fetchCount = 0;

            while ($row = $q->fetch(FetchMode::ASSOCIATIVE)) {
                if ($unbuffered) {
                    $progress->showProgress("processing $table, id: {$row[$table . 'ID']}, copied: {$copiedCount}, unique errors: {$uniqueErrors}", $rowCount);
                }

                if (!$this->recordExists($table, $row[$table . "ID"])) {
                    $this->connection->beginTransaction();

                    try {
                        $copied = $this->copyRow($table, $row);

                        if ($copied && $onRowImported !== null) {
                            $copiedCount += call_user_func($onRowImported, $row[$table . "ID"]);
                        }
                    } catch (UniqueConstraintViolationException $exception) {
                        if ($unbuffered) {
                            $this->logger->warning("UniqueConstraintViolationException: " . $exception->getMessage());
                            $this->connection->rollBack();
                            $uniqueErrors++;

                            continue;
                        }

                        throw $exception;
                    }

                    if ($copied) {
                        $copiedCount++;
                    }
                    $this->connection->commit();
                }
                $rowCount++;
                $fetchCount++;
                $lastId = $row[$table . "ID"];
            }

            if ($unbuffered) {
                $this->logger->info("$table fetched. fetched $fetchCount rows, last id {$lastId}.");
            }
        } while ($unbuffered && $fetchCount > 0);

        if ($unbuffered) {
            $this->logger->info("$table table complete. processed $rowCount rows, copied: {$copiedCount}, unique errors: {$uniqueErrors}, last id {$lastId}.");
        }

        return $copiedCount;
    }

    private function recordExists(string $table, $id): bool
    {
        $key = "k" . $id;
        $result = $this->existingRows[$table][$key] ?? null;

        if ($result !== null) {
            $this->logger->debug("record {$table}-{$id} exists: " . json_encode($result));

            return $result;
        }

        if (!array_key_exists($table, $this->existingQueries)) {
            $keyField = $this->getKeyField($table);
            $this->existingQueries[$table] = $this->connection->prepare("select 1 from $table where {$keyField} = ?");
        }

        $this->existingQueries[$table]->execute([$id]);
        $this->existingRows[$table][$key] = $this->existingQueries[$table]->fetchColumn() !== false;

        if ($this->existingRows[$table][$key]) {
            $this->logger->debug("record found: {$table}-{$id}");
        } else {
            $this->logger->debug("record not found: {$table}-{$id}");
        }

        return $this->existingRows[$table][$key];
    }

    private function copyRow(string $table, array $row): bool
    {
        $this->pingSql();

        if (isset($row['UserID']) && !$this->recordExists("Usr", $row['UserID'])) {
            $this->logger->debug("skipping missing user: {$table}-{$row['UserID']}");

            return false;
        }

        $row = $this->filterRow($row, $table);

        $this->connection->insert($table, $row);

        return true;
    }

    private function filterRow(array $row, string $table): array
    {
        $row = array_intersect_key($row, array_flip($this->tableFields[$table]));
        $row = array_filter($row, function ($value) {
            return $value !== null;
        });

        unset($row['TravelPlanID']);

        $row = $this->clearMissingLinks($row);

        $row = $this->filterTables($row, $table);

        return $row;
    }

    private function filterTables(array $row, string $table): array
    {
        if ($table === "TripSegment") {
            $row = $this->filterTripSegment($row);
        }

        if ($table === "Rental") {
            $row["PickupGeoTagID"] = $this->findGeoTag($row["PickupLocation"] ?? '');
            $row["DropoffGeoTagID"] = $this->findGeoTag($row["DropoffLocation"] ?? '');
        }

        if ($table === "Reservation") {
            $row["GeoTagID"] = $this->findGeoTag($row["Address"] ?? '');
        }

        if ($table === "Restaurant") {
            $row["GeoTagID"] = $this->findGeoTag($row["Address"] ?? '');
        }

        return $row;
    }

    private function filterTripSegment(array $row)
    {
        foreach (['Dep', 'Arr'] as $prefix) {
            if (empty($row["{$prefix}Code"])) {
                $airCode = $this->archiveAirportFinder->findAirCodeByTag($row['TripSegmentID'], $prefix);

                if ($airCode !== null) {
                    $row["{$prefix}Code"] = $airCode;
                }
            }

            if (empty($row["Scheduled{$prefix}Date"])) {
                $row["Scheduled{$prefix}Date"] = $row["{$prefix}Date"];
            }
            $row["{$prefix}GeoTagID"] = $this->findGeoTag($row["{$prefix}Code"] ?? $row["{$prefix}Name"]);
        }

        if (!empty($row['AirlineID']) && !$this->recordExists("Airline", $row['AirlineID'])) {
            $row['AirlineID'] = null;
        }

        if (empty($row['AirlineID']) && !empty($row['AirlineName'])) {
            $airline = $this->airlineRepository->search(null, null, $row['AirlineName'], true);

            if ($airline !== null) {
                $row['AirlineID'] = $airline->getAirlineid();
                $row['AirlineName'] = $airline->getName();
            }
        }

        unset($row["FlightInfoID"]);

        return $row;
    }

    private function findGeoTag(string $address): int
    {
        $this->geoTagQuery->execute([$address]);
        $result = $this->geoTagQuery->fetchColumn();

        if ($result !== false) {
            return $result;
        }

        $this->archiveGeoTagQuery->execute([$address]);
        $archivedGeoTag = $this->archiveGeoTagQuery->fetch(FetchMode::ASSOCIATIVE);

        if ($archivedGeoTag !== false) {
            if (!$this->copyRow("GeoTag", $archivedGeoTag)) {
                throw new \Exception("GeoTag with ID {$archivedGeoTag['GeoTagID']} already exists");
            }

            return $archivedGeoTag['GeoTagID'];
        }

        return $this->geo->FindGeoTag($address)['GeoTagID'];
    }

    private function getKeyField(string $table): string
    {
        $keyField = $table . "ID";

        if ($keyField === "UsrID") {
            $keyField = "UserID";
        }

        return $keyField;
    }

    private function tableExists(string $table): bool
    {
        return $this->archiveConnection->executeQuery("show tables like ?", [$table])->fetch() !== false;
    }

    private function clearMissingLinks(array $row): array
    {
        foreach (["Provider", "Account", "UserAgent"] as $table) {
            $keyField = $table . 'ID';

            if (isset($row[$keyField]) && !$this->recordExists($table, $row[$keyField])) {
                unset($row[$keyField]);
            }
        }

        return $row;
    }

    private function migrateTravelPlans(string $filter)
    {
        $this->logger->info("migrating TravelPlan");
        $count = 0;
        $migrated = 0;
        $planExistsQuery = $this->connection->prepare("select 1 from Plan where UserID = ? and StartDate = ? and Name = ?");
        $q = $this->unbufferedArchiveConnection->executeQuery("
            select t.UserID, t.UserAgentID, t.StartDate, t.EndDate, t.Name
            from TravelPlan t where t.CustomName = 1 and t.Hidden = 0 $filter");

        while ($row = $q->fetch(FetchMode::ASSOCIATIVE)) {
            $planExistsQuery->execute([$row["UserID"], $row["StartDate"], $row["Name"]]);

            if ($planExistsQuery->fetch() === false) {
                $row["ShareCode"] = RandomStr(ord('a'), ord('z'), 32);

                if ($this->copyRow("Plan", $row)) {
                    $migrated++;
                }
            }
            $count++;
        }
        $this->logger->info("TravelPlan migrated, processed $count records, migrated: $migrated");
    }

    private function pingSql()
    {
        if ((time() - $this->lastPingDate) > 60) {
            $this->logger->info("pinging mysql");
            $this->connection->ping();
            $this->archiveConnection->ping();
            $this->lastPingDate = time();
        }
    }
}
