<?php

namespace AwardWallet\MainBundle\Command\Fix;

use AwardWallet\Common\Geo\CityCorrector;
use AwardWallet\MainBundle\Service\ProgressLogger;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Monolog\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FixCitiesCommand extends Command
{
    public static $defaultName = 'aw:fix-cities';
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var Connection
     */
    private $unbufConnection;
    /**
     * @var CityCorrector
     */
    private $cityCorrector;

    public function __construct(Logger $logger, Connection $unbufConnection, Connection $connection, CityCorrector $cityCorrector)
    {
        parent::__construct();
        $this->logger = $logger;
        $this->connection = $connection;
        $this->unbufConnection = $unbufConnection;
        $this->cityCorrector = $cityCorrector;
    }

    public function configure()
    {
        $this
            ->addOption('userId', null, InputOption::VALUE_REQUIRED)
            ->addOption('updatedBefore', null, InputOption::VALUE_REQUIRED, 'check geotags updated before this date', '2030-10-07')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED)
            ->addOption('apply', null, InputOption::VALUE_NONE)
            ->addOption('geoTagId', null, InputOption::VALUE_REQUIRED)
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $apply = $input->getOption('apply');
        $this->logger->info("fixing cities to english, " . ($apply ? ", real mode" : ", dry run"));

        $sql = "select g.* 
        from 
            GeoTag g
            left join AirCode ac on g.Address = ac.AirCode
        where 
            g.City is not null 
            and ac.AirCode is null
            and g.CountryCode is not null 
            and g.Lat is not null 
            and g.CountryCode not in (:excludeCountries) 
            and g.UpdateDate < :updateDate";
        $params = [
            "updateDate" => $input->getOption('updatedBefore'),
            "excludeCountries" => CityCorrector::RELIABLE_CITY_COUNTRY_CODES,
        ];

        if ($userId = $input->getOption('userId')) {
            $sql .= " and GeoTagID in (
                select GeoTagID from Reservation where UserID = :userId
                union select PickupGeoTagID from Rental where UserID = :userId
                union select DropoffGeoTagID from Rental where UserID = :userId
                union select GeoTagID from Restaurant where UserID = :userId
                union select ts.DepGeoTagID from TripSegment ts join Trip t on ts.TripID = t.TripID where t.UserID = :userId
                union select ts.ArrGeoTagID from TripSegment ts join Trip t on ts.TripID = t.TripID where t.UserID = :userId
            )";
            $params["userId"] = $userId;
        }

        if ($geoTagId = $input->getOption('geoTagId')) {
            $sql .= " and GeoTagID = :geoTagId";
            $params["geoTagId"] = $geoTagId;
        }

        if ($geoTagId === null) {
            $sql .= " and CityFixed = 0";
        }

        if ($limit = $input->getOption('limit')) {
            $sql .= " limit $limit";
        }

        $this->logger->info("loading tags: $sql");
        $q = $this->unbufConnection->executeQuery($sql, $params, ["excludeCountries" => Connection::PARAM_STR_ARRAY]);

        $progressLogger = new ProgressLogger($this->logger, 10, 30);
        $pos = 0;
        $updated = 0;
        $geoTagId = null;
        $this->logger->pushProcessor(function (array $record) use (&$geoTagId) {
            $record['context']['GeoTagID'] = $geoTagId;

            return $record;
        });
        $fixedQuery = $this->connection->prepare("update GeoTag set CityFixed = 1 where GeoTagID = ?");

        try {
            while ($row = $q->fetch(FetchMode::ASSOCIATIVE)) {
                $geoTagId = $row['GeoTagID'];
                $progressLogger->showProgress("checking tags, checked: $pos, updated: $updated..", $pos);
                $city = $this->cityCorrector->correct($row['Address'], $row["City"], $row['CountryCode'], $row['Lat'],
                    $row['Lng']);

                if ($city !== $row['City']) {
                    $this->logger->info("changing {$row['City']} to {$city} at {$row['Lat']},{$row['Lng']} - tag id: {$row['GeoTagID']}" . ($apply ? "" : ", dry-run"));

                    if ($apply) {
                        $this->connection->executeUpdate("update GeoTag set City = :city, UpdateDate = now() where GeoTagID = :id",
                            ["city" => $city, "id" => $row['GeoTagID']]);
                        $updated++;
                    }
                }
                $pos++;

                if ($apply) {
                    $fixedQuery->execute([$geoTagId]);
                }
            }
        } finally {
            $this->logger->popProcessor();
        }

        $this->logger->info("done, processed: $pos, updated: $updated");

        return 0;
    }
}
