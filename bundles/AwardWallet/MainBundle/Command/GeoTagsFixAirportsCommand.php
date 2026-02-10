<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Globals\Geo;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GeoTagsFixAirportsCommand extends Command
{
    protected static $defaultName = 'aw:geotags:fixairports';

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        parent::__construct();
        $this->connection = $connection;
    }

    protected function configure()
    {
        $this
            ->setDescription('fix airports')
            ->setDefinition([
                new InputOption('dry-run', null, InputOption::VALUE_NONE, 'do not actually remove geotags'),
                new InputOption('min-dist', null, InputOption::VALUE_REQUIRED, 'minimum distance in miles', 100),
            ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $minDist = $input->getOption('min-dist');

        $stmt = $this->connection->executeQuery('
            SELECT
                ac.AirCode,
                gt.GeoTagID,
                ac.Lat as AirLat,
                ac.Lng as AirLng,
                gt.Lat as GoogleLat,
                gt.Lng as GoogleLng
            FROM GeoTag gt
            JOIN AirCode ac ON gt.Address = ac.AirCode
            WHERE
                LENGTH(gt.Address) = 3 AND
                gt.Lat IS NOT NULL AND
                gt.Lng IS NOT NULL
        ');

        $invalidGeoTags = [];

        while ($row = $stmt->fetch()) {
            if (($distance = Geo::distance($row['AirLat'], $row['AirLng'], $row['GoogleLat'], $row['GoogleLng'])) >= $minDist) {
                $output->writeln(sprintf('Invalid GeoTag(%d), Code: "%s", distance: %d miles', $row['GeoTagID'], $row['AirCode'], (int) $distance));
                $invalidGeoTags[] = $row['GeoTagID'];
            }
        }

        $invalidGeoTagsCount = count($invalidGeoTags);
        $output->writeln("Total: {$invalidGeoTagsCount} invalid GetTag(s)");

        if (!$invalidGeoTags) {
            return 0;
        }

        if (!$input->getOption('dry-run')) {
            $this->connection->executeQuery('DELETE FROM GeoTag WHERE GeoTagID IN (?)',
                [$invalidGeoTags],
                [Connection::PARAM_INT_ARRAY]
            );
        }

        return 0;
    }
}
