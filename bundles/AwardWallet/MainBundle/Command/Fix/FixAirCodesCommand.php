<?php

namespace AwardWallet\MainBundle\Command\Fix;

use AwardWallet\Common\Geo\GeoAirportFinder;
use AwardWallet\Common\Geo\GoogleGeo;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Service\ArchiveAirportFinder;
use AwardWallet\MainBundle\Service\ProgressLogger;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FixAirCodesCommand extends Command
{
    public static $defaultName = 'aw:fix-aircodes';
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var ArchiveAirportFinder
     */
    private $archiveAirportFinder;
    /**
     * @var GoogleGeo
     */
    private $googleGeo;
    /**
     * @var GeoAirportFinder
     */
    private $geoAirportFinder;
    /**
     * @var InputInterface
     */
    private $input;
    /**
     * @var OutputInterface
     */
    private $output;

    public function __construct(
        LoggerInterface $logger,
        Connection $connection,
        ArchiveAirportFinder $archiveAirportFinder,
        GoogleGeo $googleGeo,
        GeoAirportFinder $geoAirportFinder
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->connection = $connection;
        $this->archiveAirportFinder = $archiveAirportFinder;
        $this->googleGeo = $googleGeo;
        $this->geoAirportFinder = $geoAirportFinder;
    }

    public function configure()
    {
        $this
            ->addOption('userId', null, InputOption::VALUE_REQUIRED, 'process only this user')
            ->addOption('tripSegmentId', null, InputOption::VALUE_REQUIRED, 'process only this trip segment')
            ->addOption('fix-missing', null, InputOption::VALUE_NONE, 'fix missing air codes')
            ->addOption('fix-city-codes', null, InputOption::VALUE_NONE, 'fix city air codes')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        if ($input->getOption('fix-missing')) {
            $this->fixMissingAirCodes($input, $output);
        }

        if ($input->getOption('fix-city-codes')) {
            $this->fixCityCodes($input, $output);
        }

        return 0;
    }

    private function createFilters(InputInterface $input): string
    {
        $result = "";

        if ($userId = $input->getOption('userId')) {
            $result .= " and t.UserID = $userId";
        }

        if ($tripSegmentId = $input->getOption('tripSegmentId')) {
            $result .= " and ts.TripSegmentID = $tripSegmentId";
        }

        return $result;
    }

    private function createQuery(string $filters): Statement
    {
        return $this->connection->executeQuery("select
            ts.TripSegmentID,
            ts.DepCode,
            ts.ArrCode,
            ts.DepName,
            ts.ArrName,
            ts.DepGeoTagID,
            ts.ArrGeoTagID,
            dgt.Lat as DepLat,
            dgt.Lng as DepLng,
            agt.Lat as ArrLat,
            agt.Lng as ArrLng,
            dgt.Address as DepAddress,
            agt.Address as ArrAddress
        from
            TripSegment ts
            join Trip t on ts.TripID = t.TripID
            left join GeoTag dgt on ts.DepGeoTagID = dgt.GeoTagID
            left join GeoTag agt on ts.ArrGeoTagID = agt.GeoTagID
        where
            (ts.DepCode is null or ts.ArrCode is null or ts.DepCode = '' or ts.ArrCode = '')
            and ts.DepDate < adddate(now(), 365 * 2)
            $filters");
    }

    private function createCityQuery(string $prefix, string $filters): Statement
    {
        return $this->connection->executeQuery("select
            ts.TripSegmentID,
            ts.{$prefix}Code,
            ts.{$prefix}Name,
            ts.{$prefix}GeoTagID,
            gt.Lat as {$prefix}Lat,
            gt.Lng as {$prefix}Lng,
            gt.Address as {$prefix}Address,
            cc.CityName as {$prefix}CityName 
        from
            TripSegment ts
            join Trip t on ts.TripID = t.TripID
            left join GeoTag gt on ts.{$prefix}GeoTagID = gt.GeoTagID
            left join AirCode ac on ac.AirCode = ts.{$prefix}Code 
            join AirCode cc on cc.CityCode = ts.{$prefix}Code 
        where
            ts.{$prefix}Date < adddate(now(), 365 * 2)
            and ac.AirCode is null
            and ts.{$prefix}Name is not null and ts.{$prefix}Name <> ''
            $filters");
    }

    private function fixMissingAirCodes(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("fixing missing depcodes");
        $filters = $this->createFilters($input);
        $progress = new ProgressLogger($this->logger, 100, 30);
        $count = 0;
        $fixed = 0;

        foreach ($this->createQuery($filters) as $row) {
            foreach (["Dep", "Arr"] as $prefix) {
                $progress->showProgress("processing {$row["TripSegmentID"]}, {$prefix}..", $count);

                if (empty($row["{$prefix}Code"])) {
                    $airCode = $this->archiveAirportFinder->findAirCodeByTag($row["TripSegmentID"], $prefix);

                    // some odd bug, when geotagid pointing to null address record
                    if ($airCode === null && empty($row["{$prefix}Address"]) && !empty($row["{$prefix}Name"])) {
                        $geoTag = $this->googleGeo->FindGeoTag($row["{$prefix}Name"]);

                        if ($geoTag["Lat"] !== null && $geoTag["Lat"] !== "") {
                            $row["{$prefix}Lat"] = $geoTag["Lat"];
                            $row["{$prefix}Lng"] = $geoTag["Lng"];
                        }
                    }

                    if ($airCode === null && !empty($row["{$prefix}Name"]) && $row["{$prefix}Lat"] !== null) {
                        $airport = $this->geoAirportFinder->getNearestAirport($row["{$prefix}Lat"], $row["{$prefix}Lng"], 50);

                        if ($airport !== null) {
                            $airCode = $airport->getAircode();
                        }
                    }

                    if ($airCode !== null) {
                        $geoTagId = $this->googleGeo->FindGeoTag($airCode)['GeoTagID'];
                        $this->connection->executeUpdate("update TripSegment 
                        set {$prefix}Code = ?, {$prefix}GeoTagID = ? 
                        where TripSegmentID = ?", [$airCode, $geoTagId, $row["TripSegmentID"]]);
                        $fixed++;
                    } else {
                        $this->logger->info("no data for {$row['TripSegmentID']}, {$prefix}, tag {$row["{$prefix}Name"]}, {$row["{$prefix}GeoTagID"]}");
                    }
                }
                $count++;
            }
        }
        $output->writeln("done, processed: $count, fixed: $fixed");
    }

    private function fixCityCodes(InputInterface $input, OutputInterface $output)
    {
        $filters = $this->createFilters($input);

        foreach (["Dep", "Arr"] as $prefix) {
            $output->writeln("fixing city {$prefix} codes");
            $progress = new ProgressLogger($this->logger, 100, 30);
            $count = 0;
            $fixed = 0;

            foreach ($this->createCityQuery($prefix, $filters) as $row) {
                $progress->showProgress("processing {$row["TripSegmentID"]}, {$prefix}..", $count);

                $airCode = $this->selectCityAirport($row["{$prefix}CityName"], $row["{$prefix}Code"], $row["{$prefix}Name"]);

                if ($airCode !== null) {
                    $geoTagId = $this->googleGeo->FindGeoTag($airCode)['GeoTagID'];
                    $this->connection->executeUpdate("update TripSegment 
                    set {$prefix}Code = ?, {$prefix}GeoTagID = ? 
                    where TripSegmentID = ?", [$airCode, $geoTagId, $row["TripSegmentID"]]);
                    $fixed++;
                } else {
                    $this->logger->info("no data for {$row['TripSegmentID']}, {$prefix}, tag {$row["{$prefix}Name"]}, {$row["{$prefix}GeoTagID"]}");
                }
                $count++;
            }
        }
        $output->writeln("done, processed: $count, fixed: $fixed");
    }

    private function selectCityAirport(string $cityName, string $cityCode, string $airportName): ?string
    {
        $search = $cityName . ", " . trim(str_ireplace("airport", "", str_ireplace($cityCode, "", $airportName))) . " airport";
        $tag = $this->googleGeo->FindGeoTag($search, null, 0, false);

        if (!empty($tag["Lat"])) {
            $airport = $this->geoAirportFinder->getNearestAirport($tag['Lat'], $tag['Lng'], 50);

            if ($airport !== null) {
                $this->output->writeln("selected {$airport->getAirportName(true)} for {$airportName} at {$cityName}");

                return $airport->getAircode();
            }
        }

        return null;
    }
}
