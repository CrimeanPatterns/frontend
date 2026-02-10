<?php

namespace AwardWallet\MainBundle\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveOldSessionsCommand extends Command
{
    protected static $defaultName = 'aw:remove-old-sessions';

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
            ->setDescription('Remove old sessions from Session table')
            ->addArgument(
                'maxlifetime',
                InputArgument::OPTIONAL,
                "Sessions that have not updated for the last maxlifetime seconds will be removed",
                ini_get("session.gc_maxlifetime")
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $maxlifetime = $input->getArgument('maxlifetime');
        $sql = "DELETE FROM Session WHERE LastActivityDate < :time";
        $minTime = date("Y-m-d H:i:s", strtotime("-" . $maxlifetime . " seconds"));
        $output->writeln("Min datetime: " . $minTime);

        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':time', $minTime, \PDO::PARAM_STR);
        $stmt->execute();

        return 0;
    }
}
