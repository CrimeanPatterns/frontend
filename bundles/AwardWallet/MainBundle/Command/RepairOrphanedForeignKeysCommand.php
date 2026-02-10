<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RepairOrphanedForeignKeysCommand extends Command
{
    protected static $defaultName = 'aw:repair-orphaned-foreign-keys';

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(
        Connection $connection
    ) {
        parent::__construct();
        $this->connection = $connection;
    }

    public function configure()
    {
        $this
            ->setDescription('In the process of creating a short database dump we\' turning off foreign key checks. This command removes references that are orphaned in this process.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'do not repair database, only report')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $getReferencesQuery = 'SELECT `TABLE_NAME`, `COLUMN_NAME`, `REFERENCED_TABLE_NAME`, `REFERENCED_COLUMN_NAME`  FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=\'awardwallet\' AND REFERENCED_TABLE_SCHEMA IS NOT NULL';
            $statement = $this->connection->query($getReferencesQuery);
        } catch (DBALException $e) {
            $output->writeln("Query failed: $getReferencesQuery, Error: {$e->getMessage()}");

            return 0;
        }
        $rowsFixed = 0;

        foreach ($statement->fetchAll(\PDO::FETCH_NUM) as $reference) {
            [$sourceTable, $sourceColumn, $targetTable, $targetColumn] = $reference;

            if ($input->getOption('dry-run')) {
                $q = $this->connection->executeQuery("SELECT t1.$sourceColumn from $sourceTable t1 LEFT JOIN $targetTable t2 ON t1.$sourceColumn = t2.$targetColumn 
                WHERE t1.$sourceColumn IS NOT NULL AND t2.$targetColumn IS NULL");
                $q->execute();

                while ($id = $q->fetchColumn()) {
                    $output->writeln("missing {$targetTable}.{$targetColumn} = {$id} in {$sourceTable}.{$sourceColumn}");
                    $rowsFixed++;
                }
            } else {
                $rowsFixed += $this->connection->executeUpdate("UPDATE $sourceTable t1 LEFT JOIN $targetTable t2 ON t1.$sourceColumn = t2.$targetColumn SET t1.$sourceColumn = NULL WHERE t1.$sourceColumn IS NOT NULL AND t2.$targetColumn IS NULL");
            }
        }
        $output->writeln("Done. Fixed rows count: $rowsFixed");

        return 0;
    }
}
