<?php

namespace AwardWallet\MainBundle\Command\Fix;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Service\ProgressLogger;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FixNullDatesCommand extends Command
{
    public static $defaultName = 'aw:fix-null-dates';

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(LoggerInterface $logger, Connection $connection)
    {
        parent::__construct();
        $this->logger = $logger;
        $this->connection = $connection;
    }

    protected function configure()
    {
        $this
            ->setName('aw:fix-plus-expiration')
            ->setDescription('fix null date column in a table')
            ->addOption('table', null, InputOption::VALUE_REQUIRED, 'table name')
            ->addOption('column', null, InputOption::VALUE_REQUIRED, 'column name')
            ->addOption('value', null, InputOption::VALUE_REQUIRED, 'new date value', '2000-01-01')
            ->addOption('apply', null, InputOption::VALUE_NONE, 'apply changes')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tableName = $input->getOption('table');
        $column = $input->getOption('column');
        $date = $input->getOption('value');
        $apply = $input->getOption('apply');

        $output->writeln("will change " . ($apply ? "(real)" : "(dry-run)") . " empty values in {$input->getOption('table')}.{$input->getOption('column')} to {$input->getOption('value')}");

        $sql = "select {$tableName}ID from {$tableName} where {$column} is null";
        $output->writeln($sql);

        $processed = 0;
        $q = $this->connection->executeQuery($sql);
        $progress = new ProgressLogger($this->logger, 10, 10);

        while ($id = $q->fetchColumn()) {
            $processed++;

            if ($apply) {
                $this->connection->executeUpdate("update {$tableName} set {$column} = :date 
                where {$tableName}ID = :id", ['date' => $date, 'id' => $id]);
            }
            $progress->showProgress("processed {$processed} records..", $processed);
        }

        $output->writeln("done, " . ($apply ? "updated" : "checked") . " $processed records");

        return 0;
    }
}
