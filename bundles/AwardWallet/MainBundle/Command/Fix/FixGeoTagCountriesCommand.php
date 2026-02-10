<?php

namespace AwardWallet\MainBundle\Command\Fix;

use AwardWallet\Common\Geo\Bing\ReverseGeoCoder;
use AwardWallet\Common\Geo\GeoCodeResult;
use AwardWallet\MainBundle\Service\ProgressLogger;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Monolog\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FixGeoTagCountriesCommand extends Command
{
    public static $defaultName = 'aw:fix-geotag-countries';
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
     * @var ReverseGeoCoder
     */
    private $reverseGeoCoder;
    /**
     * @var \Memcached
     */
    private $memcached;

    public function __construct(
        Logger $logger,
        Connection $unbufConnection,
        Connection $connection,
        ReverseGeoCoder $reverseGeoCoder,
        \Memcached $memcached
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->connection = $connection;
        $this->unbufConnection = $unbufConnection;
        $this->reverseGeoCoder = $reverseGeoCoder;
        $this->memcached = $memcached;
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
        $this->logger->info("fixing country codes, " . ($apply ? ", real mode" : ", dry run"));

        $sql = "select g.* 
        from 
            GeoTag g
        where 
            length(g.CountryCode) > 2  
            and g.Lat is not null 
            and g.UpdateDate < :updateDate";
        $params = [
            "updateDate" => $input->getOption('updatedBefore'),
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

        if ($limit = $input->getOption('limit')) {
            $sql .= " limit $limit";
        }

        $this->logger->info("loading tags: $sql");
        $q = $this->unbufConnection->executeQuery($sql, $params);

        $progressLogger = new ProgressLogger($this->logger, 10, 30);
        $pos = 0;
        $updated = 0;
        $geoTagId = null;
        $this->logger->pushProcessor(function (array $record) use (&$geoTagId) {
            $record['context']['GeoTagID'] = $geoTagId;

            return $record;
        });

        try {
            while ($row = $q->fetch(FetchMode::ASSOCIATIVE)) {
                $geoTagId = $row['GeoTagID'];
                $progressLogger->showProgress("checking tags, checked: $pos, updated: $updated..", $pos);

                $country = $this->detectCountry($row['CountryCode'], $row['Lat'], $row['Lng']);

                if ($country !== null) {
                    $this->logger->info("changing {$row['CountryCode']} to {$country} at {$row['Lat']},{$row['Lng']} - tag id: {$row['GeoTagID']}" . ($apply ? "" : ", dry-run"));

                    if ($apply) {
                        $this->connection->executeUpdate("update GeoTag set CountryCode = :countryCode, UpdateDate = now() where GeoTagID = :id",
                            ["countryCode" => $country, "id" => $row['GeoTagID']]);
                        $updated++;
                    }
                }

                $pos++;
            }
        } finally {
            $this->logger->popProcessor();
        }

        $this->logger->info("done, processed: $pos, updated: $updated");

        return 0;
    }

    private function detectCountry(string $countryCode, float $lat, float $lng): ?string
    {
        $cacheKey = "cc_map_" . $countryCode;
        $cache = $this->memcached->get($cacheKey);

        if ($cache !== false) {
            return $cache;
        }

        $result = $this->detectCountryByCoords($lat, $lng);

        if ($result !== null) {
            $this->memcached->set($cacheKey, $result, 86400 * 30);
        }

        return $result;
    }

    private function detectCountryByCoords(float $lat, float $lng): ?string
    {
        $results = $this->reverseGeoCoder->reverseGeoCode($lat, $lng);

        if (count($results) > 0) {
            /** @var GeoCodeResult $result */
            $result = reset($results);

            if (isset($result->detailedAddress['CountryCode'])) {
                return $result->detailedAddress['CountryCode'];
            }
        }

        return null;
    }
}
