<?php

namespace AwardWallet\MainBundle\Command\Billing;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportPaypalCsvCommand extends Command
{
    protected static $defaultName = 'aw:billing:export-paypal-csv';
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        parent::__construct();
        $this->connection = $connection;
    }

    public function configure()
    {
        $this->addArgument('jsonl-file');
        $this->addArgument('output-csv-file');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $f = fopen($input->getArgument('jsonl-file'), "rb");
        $outFile = fopen($input->getArgument('output-csv-file'), "wb");
        fputcsv($outFile, ["transaction_id", "cart_id", "user_id", "email"]);
        $emails = $this->connection->fetchAllKeyValue("select UserID, Email from Usr");

        while ($line = fgets($f)) {
            $tx = json_decode($line, true);
            fputcsv($outFile, [$tx['TransactionID'], $tx['CartID'], $tx['UserID'], $emails[$tx['UserID']] ?? '']);
        }

        fclose($f);
        fclose($outFile);

        return 0;
    }
}
