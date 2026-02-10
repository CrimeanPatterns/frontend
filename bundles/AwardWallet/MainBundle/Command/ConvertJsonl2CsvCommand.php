<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Service\ProgressLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConvertJsonl2CsvCommand extends Command
{
    public static $defaultName = 'aw:convert-jsonl-2-csv';

    public function configure()
    {
        $this
            ->addOption('input-file', null, InputOption::VALUE_REQUIRED)
            ->addOption('output-file', null, InputOption::VALUE_REQUIRED)
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("loading jsonl from " . $input->getOption('input-file'));

        $inputFile = fopen($input->getOption("input-file"), "rb");

        if ($inputFile === false) {
            throw new \Exception("failed to open input file: " . $input->getOption("input-file"));
        }

        $outputFile = fopen($input->getOption("output-file"), "wb");

        if ($inputFile === false) {
            throw new \Exception("failed to open output file: " . $input->getOption("output-file"));
        }

        $progress = new ProgressLogger(new Logger("main", [new StreamHandler('php://stdout')]), 100, 30);
        $count = 0;
        $mergedRow = [];

        try {
            while (!empty($line = fgets($inputFile))) {
                $progress->showProgress("detecting fields", $count);
                $count++;

                $line = trim($line);

                if (empty($line)) {
                    continue;
                }

                $json = json_decode($line, true);
                $mergedRow = array_merge($mergedRow, $json);
            }
            $fields = array_keys($mergedRow);
            $output->writeln("detected fields: " . implode(", ", $fields));
            fputcsv($outputFile, $fields);

            $progress = new ProgressLogger(new Logger("main", [new StreamHandler('php://stdout')]), 100, 30);
            $count = 0;
            $emptyRow = array_combine($fields, array_fill(0, count($fields), null));
            fseek($inputFile, 0);

            while (!empty($line = fgets($inputFile))) {
                $progress->showProgress("converting jsonl", $count);
                $count++;

                $line = trim($line);

                if (empty($line)) {
                    continue;
                }

                $json = json_decode($line, true);
                $values = array_merge($emptyRow, $json);
                fputcsv($outputFile, $values);
            }
        } finally {
            fclose($inputFile);
            fclose($outputFile);
        }

        $output->writeln("done");

        return 0;
    }
}
