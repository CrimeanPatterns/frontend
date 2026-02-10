<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FillHistoryKeysCommand extends Command
{
    protected static $defaultName = 'aw:fill-history-keys';

    private Connection $connection;

    public function __construct(
        Connection $connection
    ) {
        parent::__construct();
        $this->connection = $connection;
    }

    protected function configure()
    {
        $this
            ->setDescription('Fill UUID for AccountHistory table');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("updating history keys");

        do {
            $count = $this->connection->executeUpdate("UPDATE AccountHistory h SET h.UUID = UUID() WHERE h.UUID IS NULL");
            $output->writeln("updated $count rows..");
        } while ($count > 0);
        $output->writeln("done");

        return 0;
    }
}
