<?php

namespace AwardWallet\MainBundle\Command\Stat;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RecordSiteStatsCommand extends Command
{
    public static $defaultName = 'aw:record-site-stats';
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        parent::__construct();
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info("frontend_stat:users", ["Count" => $this->connection->executeQuery("select count(*) from Usr")->fetchColumn(0)]);

        return 0;
    }
}
