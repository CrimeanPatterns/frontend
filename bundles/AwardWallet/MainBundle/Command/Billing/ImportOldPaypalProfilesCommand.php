<?php

namespace AwardWallet\MainBundle\Command\Billing;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportOldPaypalProfilesCommand extends Command
{
    protected static $defaultName = 'aw:import-old-paypal-profiles';
    private Statement $statement;

    public function __construct(Connection $connection)
    {
        parent::__construct();

        $this->statement = $connection->prepare(
            "update Usr set OldPaypalRecurringProfileID = :profileId where UserID = :userId"
        );
    }

    public function configure()
    {
        $this
            ->addArgument('jsonl-file', InputArgument::REQUIRED)
            ->addOption('userId', null, InputOption::VALUE_REQUIRED)
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("reading " . $input->getArgument('jsonl-file'));
        $f = fopen($input->getArgument('jsonl-file'), "r");
        $n = 0;

        while ($line = fgets($f)) {
            if (($n % 1000) === 0) {
                $output->writeln("imported $n rows..");
            }

            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $row = json_decode($line, true);

            if ($input->getOption('userId') && $row['UserID'] != $input->getOption('userId')) {
                continue;
            }

            $this->statement->executeStatement(["userId" => $row['UserID'], "profileId" => $row["PayPalRecurringProfileID"]]);
            $n++;
        }

        fclose($f);

        $output->writeln("done, imported $n lines");

        return 0;
    }
}
