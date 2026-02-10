<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Service\ProgressLogger;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Monolog\Logger;
use Symfony\Bridge\Monolog\Handler\ConsoleHandler;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExportCsvCommand extends Command
{
    public static $defaultName = 'aw:export-csv';
    /**
     * @var Connection
     */
    private $unbufConnection;
    /**
     * @var OutputInterface
     */
    private $output;

    public function __construct(Connection $unbufConnection)
    {
        parent::__construct();
        $this->unbufConnection = $unbufConnection;
    }

    public function configure()
    {
        parent::configure();

        $this
            ->setDescription('export sql query to csv')
            ->addOption('sql-file', null, InputOption::VALUE_REQUIRED, 'file with sql')
            ->addOption('sql', null, InputOption::VALUE_REQUIRED, 'plain sql')
            ->addOption('csv-file', null, InputOption::VALUE_REQUIRED, 'destination csv file')
            ->addOption('host', null, InputOption::VALUE_REQUIRED, 'source mysql host')
            ->addOption('port', null, InputOption::VALUE_REQUIRED, 'source mysql host')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;

        $sql = $this->loadSql($input);
        $connection = $this->createConnection($input);
        $csvFile = $this->createOutputFile($input);

        try {
            $this->dumpQueryToFile($connection, $sql, $csvFile);
        } finally {
            fclose($csvFile);
        }

        return 0;
    }

    private function loadSql(InputInterface $input): string
    {
        if (!empty($input->getOption('sql')) && !empty($input->getOption('sql-file'))) {
            throw new \Exception("specify sql or sql-file, not both");
        }

        $sql = $input->getOption('sql');

        if (empty($sql)) {
            $sqlFile = $input->getOption('sql-file');

            if (empty($sqlFile)) {
                throw new \Exception("specify sql or sql-file");
            }
            $sql = file_get_contents($sqlFile);

            if (empty($sql)) {
                throw new \Exception("failed to load sql from file");
            }
        }

        return $sql;
    }

    private function createConnection(InputInterface $input): Connection
    {
        if (empty($input->getOption('host')) && empty($input->getOption('port'))) {
            return $this->unbufConnection;
        }

        $params = $this->unbufConnection->getParams();

        if (!empty($input->getOption('host'))) {
            $params["host"] = $input->getOption('host');
        }

        if (!empty($input->getOption('port'))) {
            $params["port"] = $input->getOption('port');
        }

        $connection = new Connection($params, $this->unbufConnection->getDriver(), $this->unbufConnection->getConfiguration(), $this->unbufConnection->getEventManager());

        return $connection;
    }

    private function createOutputFile(InputInterface $input)
    {
        $file = $input->getOption('csv-file');

        if (empty($file)) {
            throw new \Exception("csv-file required");
        }
        $handle = fopen($file, "wb+");

        if ($handle === false) {
            throw new \Exception("Failed to open $file");
        }

        return $handle;
    }

    private function dumpQueryToFile(Connection $connection, string $sql, $csvFile)
    {
        $this->output->writeln("loading query");
        $this->output->writeln($sql);
        $connection->exec("set wait_timeout = 86400");
        $connection->exec("set interactive_timeout = 86400");
        $q = $connection->executeQuery($sql);
        $pos = 0;
        $progress = new ProgressLogger(new Logger('main', [new ConsoleHandler($this->output)]), 100, 20);

        do {
            $progress->showProgress('dumping query to csv', $pos);
            $row = $q->fetch(FetchMode::ASSOCIATIVE);

            if ($row !== false) {
                if ($pos === 0) {
                    if (!fputcsv($csvFile, array_keys($row))) {
                        throw new \Exception("Error writing to csv-file");
                    }
                }

                if (!fputcsv($csvFile, $row)) {
                    throw new \Exception("Error writing to csv-file");
                }
            }
            $pos++;
        } while ($row !== false);
        $this->output->writeln("done, dumped $pos rows");
    }
}
