<?php

namespace AwardWallet\MainBundle\Service\Lounge;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearLoungeCommand extends Command
{
    public static $defaultName = 'aw:cleaning-lounges';

    private Connection $connection;

    private Storage $storage;

    public function __construct(Connection $connection, Storage $storage)
    {
        parent::__construct();

        $this->connection = $connection;
        $this->storage = $storage;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('clear lounges changes');

        $this->connection->executeQuery('
            DELETE FROM LoungeSourceChange WHERE ChangeDate < NOW() - INTERVAL 3 MONTH
        ');

        $output->writeln('removing useless lounges sources');

        $this->connection->executeQuery('
            DELETE FROM LoungeSource WHERE DeleteDate IS NOT NULL AND DeleteDate < NOW()
        ');

        $output->writeln('removing actions');

        $this->connection->executeQuery('
            DELETE FROM LoungeAction WHERE DeleteDate IS NOT NULL AND DeleteDate < NOW()
        ');

        $output->writeln('clearing storage');
        $this->storage->clearExpired();

        $output->writeln('done.');
    }
}
