<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestGoneAwayCommand extends Command
{
    public static $defaultName = 'aw:test-gone-away';

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        parent::__construct();
        $this->connection = $connection;
    }

    protected function configure()
    {
        $this
            ->setDescription('test mysql gone away error')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("connecting");
        $stmt = $this->connection->prepare("select DisplayName from Provider where Code = :code");
        $stmt->bindValue("code", "chase");
        $q = $stmt->executeQuery();
        $name = $q->fetchOne();
        $output->writeln("name: " . $name);
        $output->writeln("waiting");
        sleep(15);
        $q = $stmt->executeQuery();
        $name = $q->fetchOne();
        $output->writeln("name: " . $name);

        return 0;
    }
}
