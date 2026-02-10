<?php

namespace AwardWallet\MainBundle\Service\RA\Flight;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CleanCommand extends Command
{
    public static $defaultName = 'aw:ra:flight-clean';

    private Connection $connection;

    private LoggerInterface $logger;

    public function __construct(Connection $connection, LoggerFactory $loggerFactory)
    {
        parent::__construct();

        $this->connection = $connection;
        $this->logger = $loggerFactory->createLogger($loggerFactory->createProcessor([
            'class' => 'CleanCommand',
        ]));
    }

    protected function configure()
    {
        $this
            ->setDescription('Clean old flight search queries')
            ->addOption('test', null, InputOption::VALUE_NONE, 'Test mode');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filter = '';

        if ($input->getOption('test')) {
            $filter = " AND Parsers = 'test'";
        }

        $deletedQueries = $this->connection->executeStatement("
            DELETE FROM RAFlightSearchQuery
            WHERE DeleteDate IS NOT NULL
            AND DeleteDate < NOW() - INTERVAL 1 MONTH
            $filter
        ");

        $deletedRoutes = $this->connection->executeStatement('
            DELETE FROM RAFlightSearchRoute
            WHERE LastSeenDate < NOW() - INTERVAL 1 MONTH AND Flag = 0
        ');

        $this->logger->info(sprintf(
            'deleted %d old flight search queries, %d old routes',
            $deletedQueries,
            $deletedRoutes
        ));

        return 0;
    }
}
