<?php

namespace AwardWallet\MainBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CalcResetStatsCommand extends Command
{
    /**
     * @var string
     */
    private $esAddress;
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('aw:calc-reset-stats')
            ->setDescription('Calc reset password stats')
            ->addOption('es-address', null, InputOption::VALUE_REQUIRED, 'elasticsearch address', 'log.awardwallet.com');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = $this->logger;
        $this->esAddress = $input->getOption('es-address');

        $otcAfterResetCount = 0;
        $warnings = 0;
        $users = [];

        $response = $this->search("logstash-*", 'channel:security AND context.otcRequired:true AND context.message: email', 90, 10000);
        $logger->info("hits: " . $response['hits']['total']);
        $otcCount = count($response["hits"]["hits"]);

        foreach ($response["hits"]["hits"] as $n => $hit) {
            $response = $this->search($hit['_index'], 'RequestID: ' . $hit['_source']['RequestID'] . ' AND required email otc for user', 10, 1);

            if ($response['hits']['total'] == 0) {
                $logger->warning("request {$hit['_source']['RequestID']}: email otc not found");
                $warnings++;

                continue;
            }
            $userId = $response['hits']['hits'][0]['_source']["context"]["UserID"];
            $time = strtotime($response['hits']['hits'][0]['_source']["@timestamp"]);

            if (($n % 100) == 0) {
                $logger->info("user: $userId, at " . date("Y-m-d H:i:s", $time) . ", processed $n rows..");
            }

            $response = $this->search("logstash-*", 'context.subject: "AwardWallet Password Changed" AND UserID: ' . $userId, 10, 1, $time - 8 * 3600, $time);

            if ($response['hits']['total'] == 0) {
                continue;
            } else {
                $otcAfterResetCount++;
                $users[] = $userId;
                $logger->debug("hit: $userId, at " . date("Y-m-d H:i:s", $time) . ", processed $n rows..");
            }
        }
        $users = array_unique($users);
        $logger->info("done, otc count: $otcCount, otc after reset: $otcAfterResetCount, users: " . count($users) . ", warnings: $warnings");

        return 0;
    }

    private function search($index, $query, $timeout, $limit, $startDate = null, $endDate = null)
    {
        $data = curlRequest('http://' . $this->esAddress . ':9200/' . $index . '/_search?pretty', $timeout, [
            CURLOPT_FAILONERROR => false,
            CURLOPT_POSTFIELDS => '
            {
              "size": ' . $limit . ',
              "sort": [
                {
                  "@timestamp": {
                    "order": "desc",
                    "unmapped_type": "boolean"
                  }
                }
              ],
              "query": {
                "bool": {
                  "must": [
                    {
                      "query_string": {
                        "query": "' . addslashes($query) . '",
                        "analyze_wildcard": true
                      }
                    }
                    ' . (!empty($startDate) ? ',
                    {
                      "range": {
                        "@timestamp": {
                          "gte": ' . ($startDate * 1000) . ',
                          "lte": ' . ($endDate * 1000) . ',
                          "format": "epoch_millis"
                        }
                      }
                    }
                    ' : '') . '
                  ]
                }
              }
            }
        ', ]);

        //        echo $data . "\n";
        return json_decode($data, true);
    }
}
