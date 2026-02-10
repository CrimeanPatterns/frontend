<?php

namespace AwardWallet\MainBundle\Command\Fix;

use AwardWallet\Common\Geo\GoogleGeo;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ForwardCompatibility\Result;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;

use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtAssoc;

class RefreshGeoTagsCommand extends Command
{
    public static $defaultName = 'aw:fix:refresh-geotags';

    private Connection $unbufferedConnection;
    private OutputInterface $output;
    private InputInterface $input;
    private Connection $connection;
    private ServiceLocator $geoCoders;
    private GoogleGeo $googleGeo;
    private GoogleGeo $geoCoder;

    public function __construct(Connection $replicaUnbufferedConnection, Connection $connection, ServiceLocator $geoCoders, GoogleGeo $googleGeo)
    {
        parent::__construct();

        $this->unbufferedConnection = $replicaUnbufferedConnection;
        $this->connection = $connection;
        $this->geoCoders = $geoCoders;
        $this->googleGeo = $googleGeo;
    }

    public function configure()
    {
        parent::configure();

        $this
            ->addOption('limit', null, InputOption::VALUE_REQUIRED)
            ->addOption('print', null, InputOption::VALUE_NONE)
            ->addOption('refresh', null, InputOption::VALUE_NONE, 'refresh found tags through geocoding api')
            ->addOption('write', null, InputOption::VALUE_NONE, 'write changes after refresh')
            ->addOption('where', null, InputOption::VALUE_REQUIRED, 'sql where')
            ->addOption('geo-source', null, InputOption::VALUE_REQUIRED, 'geocoding source, run with --geo-source-list to show available ones')
            ->addOption('geo-source-list', null, InputOption::VALUE_NONE, 'list available geocoding sources')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $this->input = $input;

        if ($input->getOption('geo-source-list')) {
            $output->writeln("available geocoders: " . implode(', ', array_keys($this->geoCoders->getProvidedServices())));

            return 0;
        }

        $this->geoCoder = $this->googleGeo;

        if ($geoCoderName = $input->getOption('geo-source')) {
            $output->writeln("selecting geo coder {$geoCoderName}");
            $this->geoCoder = $this->geoCoders->get($geoCoderName);
        }

        $output->writeln("searching geotags");
        $sql = "
        select
          gt.*
        from 
          GeoTag gt 
        ";

        if ($where = $input->getOption('where')) {
            $sql .= " where $where";
        }

        if ($limit = $input->getOption('limit')) {
            $sql .= " limit $limit";
        }

        $q = $this->unbufferedConnection->executeQuery($sql);

        $this->processRows($q);

        return 0;
    }

    private function processRows(Result $q)
    {
        $chain = stmtAssoc($q)
            ->onNthMillis(10000, function ($time, $ticksCounter, $value, $key) {
                $this->output->writeln("processed $ticksCounter records..");
            });

        if ($this->input->getOption('refresh')) {
            $chain = $chain->onEach(function (array $row) {
                $updated = $this->geoCoder->FindGeoTag($row['Address'], null, 0, !$this->input->getOption('write'), false);
                $old = $this->format($row);
                $new = $this->format($updated);

                if ($old !== $new) {
                    $this->output->writeln("{$row['GeoTagID']}: updated {$old} --> {$new}");
                } else {
                    $this->output->writeln("{$row['GeoTagID']}: no changes");
                }
            });
        }

        if ($this->input->getOption('print')) {
            $chain = $chain->onEach(function (array $row) {
                $this->output->writeln("{$row['Address']}: {$row['Lat']},{$row['Lng']} - {$row['Source']}, {$row['Country']}, {$row['TimeZoneLocation']}");
            });
        }

        $total = $chain->count();

        $this->output->writeln("done, processed $total rows");
    }

    private function format(array $row): string
    {
        return "{$row['Source']} {$row['Lat']},{$row['Lng']} " . $row['Country'] ?? '' . " {$row['TimeZoneLocation']}";
    }
}
