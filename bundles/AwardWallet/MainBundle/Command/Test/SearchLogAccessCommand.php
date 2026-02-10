<?php

namespace AwardWallet\MainBundle\Command\Test;

use AwardWallet\MainBundle\Service\ElasticSearch\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class SearchLogAccessCommand extends Command
{
    protected static $defaultName = 'aw:test:search-log-access';
    private Client $elasticSearchClient;
    private LoggerInterface $logger;

    public function __construct(Client $elasticSearchClient, LoggerInterface $logger)
    {
        parent::__construct();

        $this->elasticSearchClient = $elasticSearchClient;
        $this->logger = $logger;
    }

    public function configure()
    {
        $this
            ->addOption('startDate', null, InputOption::VALUE_REQUIRED, '', '-24 hours')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info("searching for log access");
        $logFiles = [];

        it(
            $this->elasticSearchClient->query(
                'channel: "nginx_access_log" AND extra.app: "frontend" AND code: 302',
                new \DateTime($input->getOption('startDate')),
                new \DateTime(),
                5000
            )
        )
            ->onNthMillisAndLast(5000, function (int $millisFromStart, int $iteration, $currentValue, $currentKey, bool $isLast) use (&$logFiles) {
                $this->logger->info("loaded {$iteration} documents, date: " . $currentValue["@timestamp"] . ", logfiles found: " . count($logFiles) . "..");
            })
            ->filter(function (array $doc) {
                return stripos($doc['path'], '/manager/loyalty/logs/aw-loyalty-logs/awardwallet_checkaccount_') === 0;
            })
            ->apply(function (array $doc) use (&$logFiles) {
                $logName = basename($doc['path']);

                if (!isset($logFiles[$logName])) {
                    $logFiles[$logName] = [
                        'hits' => 0,
                        'ips' => [],
                    ];
                }

                $logFiles[$logName]['hits']++;
                $logFiles[$logName]['ips'][$doc['remote']] = ($logFiles[$logName]['ips'][$doc['remote']] ?? 0) + 1;
            })
        ;

        $this->logger->info("finished search, found " . count($logFiles) . " accessed log files. saved them logFiles.json");
        file_put_contents("logFiles.json", json_encode($logFiles, JSON_PRETTY_PRINT));

        return 0;
    }
}
