<?php

namespace AwardWallet\MainBundle\Command\Fix;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FixCityAirCodesCommand extends Command
{
    protected static $defaultName = 'aw:fix-city-aircodes';

    private LoggerInterface $logger;
    private Connection $connection;

    public function __construct(
        LoggerInterface $logger,
        Connection $connection
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->connection = $connection;
    }

    protected function configure()
    {
        $this
            ->setDescription('fix geotags with city codes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $conn = $this->connection;
        $logger = $this->logger;

        $logger->info("searching..");
        $tags = $conn->executeQuery("select distinct
        	g.* 
        from 
        	GeoTag g
        	join AirCode cc on g.Address = cc.CityCode
        	left outer join AirCode ac on ac.AirCode = g.Address
        where
        	ac.AirCodeID is null
        ")->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($tags as $tag) {
            $logger->info("updating tag", $tag);
            $conn->executeUpdate("update GeoTag set UpdateDate = adddate(now(), -360) where GeoTagID = :id", ["id" => $tag["GeoTagID"]]);
            FindGeoTag($tag["Address"]);
        }

        $logger->info("done");

        return 0;
    }
}
