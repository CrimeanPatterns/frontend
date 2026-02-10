<?php

namespace AwardWallet\MainBundle\Command;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteHistoryCommand extends Command
{
    protected static $defaultName = 'aw:delete-history';
    private LoggerInterface $logger;
    private Connection $connection;
    private Connection $replicaUnbufferedConnection;

    public function __construct(LoggerInterface $logger, Connection $connection, Connection $replicaUnbufferedConnection)
    {
        parent::__construct();
        $this->logger = $logger;
        $this->connection = $connection;
        $this->replicaUnbufferedConnection = $replicaUnbufferedConnection;
    }

    protected function configure()
    {
        $this
            ->setDescription("Delete data from AccountHistory table")
            ->addArgument('providerCode', InputArgument::REQUIRED)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = $this->logger;
        $connection = $this->connection;
        $unbuffConnection = $this->replicaUnbufferedConnection;

        $providerCode = $input->getArgument('providerCode');
        $providerId = $connection->fetchColumn("select ProviderID from Provider where Code = :code", ["code" => $providerCode]);

        $logger->info("deleting history", ["providerCode" => $providerCode, "providerId" => $providerId]);
        $q = $unbuffConnection->executeQuery("select Account.AccountID 
        from Account 
        where Account.ProviderID = :providerId
        and Account.UpdateDate < '" . date("Y-m-d H:i:s") . "'", ["providerId" => $providerId]);
        $time = 0;
        $accounts = 0;
        $deleted = 0;
        $packet = [];
        $deleteSql = "delete from AccountHistory where AccountID in (?)";

        while ($accountId = $q->fetchColumn()) {
            $packet[] = $accountId;

            if (count($packet) >= 100) {
                $deleted += $connection->executeUpdate($deleteSql, [$packet], [Connection::PARAM_INT_ARRAY]);
                $packet = [];
            }
            $accounts++;

            if ((time() - $time) > 10) {
                $logger->info("deleting..", ["accounts" => $accounts, "deleted" => $deleted]);
                $time = time();
            }
        }

        if (count($packet) > 0) {
            $deleted += $connection->executeUpdate($deleteSql, [$packet], [Connection::PARAM_INT_ARRAY]);
        }

        $logger->info("done", ["accounts" => $accounts, "deleted" => $deleted]);

        return 0;
    }
}
