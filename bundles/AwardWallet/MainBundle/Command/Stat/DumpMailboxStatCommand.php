<?php

namespace AwardWallet\MainBundle\Command\Stat;

use AwardWallet\MainBundle\Service\ElasticSearch\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class DumpMailboxStatCommand extends Command
{
    public static $defaultName = 'aw:dump-mailbox-stat';
    private Client $client;
    private LoggerInterface $logger;

    public function __construct(Client $client, LoggerInterface $logger)
    {
        parent::__construct();

        $this->client = $client;
        $this->logger = $logger;
    }

    public function configure()
    {
        $this
            ->addOption('load-csv-file', null, InputOption::VALUE_REQUIRED, 'CSV file to read initial stats from')
            ->addOption('csv-file', null, InputOption::VALUE_REQUIRED, 'CSV file to write to', 'mailboxes.csv')
            ->addOption('start-date', null, InputOption::VALUE_REQUIRED, 'Start date', '2023-01-01')
            ->addOption('end-date', null, InputOption::VALUE_REQUIRED, 'Start date', '2023-02-01')
            ->addOption('target-size', null, InputOption::VALUE_REQUIRED, 'Target size of the CSV file')
            ->addOption("query", null, InputOption::VALUE_REQUIRED, "ElasticSearch query", "message:mailbox_connected_stat AND context.partner: uber")
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("loading email hashes...");
        $mailboxes = [];

        if ($file = $input->getOption('load-csv-file')) {
            $mailboxes = $this->loadCsv($file);
        }

        $targetSize = $input->getOption('target-size');

        if ($targetSize !== null) {
            $this->logger->info("target size: {$targetSize}");
        }

        it(
            $this->client->query(
                $input->getOption("query"),
                new \DateTime($input->getOption('start-date')),
                new \DateTime($input->getOption('end-date')),
                500
            )
        )
            ->takeWhile(function (array $record) use ($targetSize, &$mailboxes) {
                return $targetSize === null || count($mailboxes) < $targetSize;
            })
            ->map(function (array $record) {
                return [
                    'mailboxId' => $record["context"]["mailboxId"],
                    'email' => $record["context"]["email"],
                    'date' => strtotime(preg_replace("#\..+#ims", "", $record["@timestamp"])),
                ];
            })
            ->onNthMillisAndLast(5000, function (int $millisFromStart, int $iteration, $currentValue, $currentKey, bool $isLast) use (&$mailboxes) {
                $this->logger->info("loaded {$iteration} documents, date: " . date("Y-m-d", $currentValue["date"]) . ", mailboxes: " . count($mailboxes) . "..");
            })
            ->forEach(function (array $record) use (&$mailboxes) {
                if (!isset($mailboxes[$record["mailboxId"]])) {
                    $mailboxes[$record["mailboxId"]] = [
                        'mailboxId' => $record["mailboxId"],
                        'email' => $record["email"],
                        'firstSeen' => $record["date"],
                        'lastSeen' => $record["date"],
                    ];
                } else {
                    $mailboxes[$record["mailboxId"]]["firstSeen"] = min($mailboxes[$record["mailboxId"]]["firstSeen"], $record["date"]);
                    $mailboxes[$record["mailboxId"]]["lastSeen"] = max($mailboxes[$record["mailboxId"]]["lastSeen"], $record["date"]);
                }
            })
        ;

        $this->logger->info("loaded " . count($mailboxes) . " mailboxes, sorting...");
        usort($mailboxes, function (array $a, array $b) {
            return $a["firstSeen"] <=> $b["firstSeen"];
        });

        $this->logger->info("writing to CSV file...");
        $f = fopen($input->getOption('csv-file'), "wb");
        fputcsv($f, ["mailboxId", "emailHash", "firstSeen", "lastSeen"]);
        it($mailboxes)
            ->map(function (array $mailbox) {
                $mailbox['firstSeen'] = date("Y-m-d", $mailbox['firstSeen']);
                $mailbox['lastSeen'] = date("Y-m-d", $mailbox['lastSeen']);

                return $mailbox;
            })
            ->apply(function (array $row) use ($f) {
                fputcsv($f, $row);
            })
        ;
        fclose($f);
        $this->logger->info("done");

        return 0;
    }

    private function loadCsv(string $file): array
    {
        $this->logger->info("loading CSV file {$file}...");
        $f = fopen($file, "rb");
        $header = fgetcsv($f);
        $mailboxes = [];

        while ($row = fgetcsv($f)) {
            $mailboxes[$row[0]] = [
                'mailboxId' => $row[0],
                'email' => $row[1],
                'firstSeen' => strtotime($row[2]),
                'lastSeen' => strtotime($row[3]),
            ];
        }
        fclose($f);

        $this->logger->info("loaded " . count($mailboxes) . " mailboxes");

        return $mailboxes;
    }
}
