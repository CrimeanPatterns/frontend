<?php

namespace AwardWallet\MainBundle\Command\Test;

use Elasticsearch\ClientBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SearchLogRACommand extends Command
{
    protected static $defaultName = 'aw:test:search-log-ra';

    private LoggerInterface $logger;
    private array $hosts;

    public function __construct(LoggerInterface $logger, $esHost)
    {
        parent::__construct();

        $this->logger = $logger;
        $this->hosts = [trim($esHost, '/') . ':9200'];
    }

    public function configure()
    {
        $this
            ->addOption('startDate', null, InputOption::VALUE_REQUIRED, '', '-24 hours')
            ->addOption('priority', null, InputOption::VALUE_REQUIRED, '', '9')
            ->addOption('provider', null, InputOption::VALUE_REQUIRED, '', 'mileageplus')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, '0 - requests, 1 - result', 0);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        // Instantiate a new ClientBuilder
        $client = ClientBuilder::create()
            ->setHosts($this->hosts)// Set the hosts
            ->build();
        $this->logger->info("searching for log");
        $logData = [];

        $start = new \DateTime($input->getOption('startDate'));
        $end = new \DateTime($input->getOption('startDate'));
        $priority = $input->getOption('priority');
        $provider = $input->getOption('provider');
        $type = (int) $input->getOption('type');

        if ($type === 1) {
            $query = 'context.Provider: ' . $provider . ' AND extra.partner:juicymiles AND  app: juicymiles AND context.Priority:' . $priority;
        } else {
            $query = '"\"priority\":' . $priority . '" AND "\"provider\":\"' . $provider . '\"" AND "request data:*"';
        }

        for ($i = 1; $i < 24; $i++) {
            $end->add(new \DateInterval('PT1H'));
            $this->logger->info($start->format('Y-m-d H:i') . '-' . $end->format('Y-m-d H:i'));

            if ($type === 1) {
                $res = $client->search($this->getParams1($query, $start, $end));

                foreach ($res['hits']['hits'] as $doc) {
                    $ctxt = $doc['_source']['context'];

                    if (isset($logData[$ctxt['RequestID']])) {
                        continue;
                    }
                    $logData[$ctxt['RequestID']] = [
                        'provider' => $ctxt['Provider'],
                        'responseTime' => str_replace('T', ' ', substr($doc['_source']['@timestamp'], 0, 19)),
                        'request' => $ctxt['RequestData'],
                        'status' => $ctxt['ErrorCode'] ?? null,
                    ];
                }
            } else {
                $res = $client->search($this->getParams2($start, $end, $priority, $provider));

                foreach ($res['hits']['hits'] as $doc) {
                    $ext = $doc['_source']['extra'];

                    if (isset($logData[$ext['requestId']])) {
                        continue;
                    }

                    $logData[$ext['requestId']] = [
                        'provider' => $ext['provider'],
                        'requestTime' => str_replace('T', ' ', substr($doc['_source']['@timestamp'], 0, 19)),
                        'request' => str_replace('request data: ', '', $doc['_source']['message']),
                    ];
                }
            }
            $start->add(new \DateInterval('PT1H'));
        }

        $this->logger->info("finished search, found " . count($logData) . " rows. saved them logFiles.json");
        $suff = '';

        if ($type === 1) {
            $suff = '-res';
        }
        $file_name = sprintf('log-%s-%d%s.json', $provider, $priority, $suff);
        file_put_contents($file_name, json_encode($logData, JSON_PRETTY_PRINT));

        return 0;
    }

    private function getParams1(string $query, \DateTime $start, \DateTime $end): array
    {
        $start = $start->getTimestamp() * 1000;
        $end = $end->getTimestamp() * 1000 - 1;

        $size = 10000;

        $params = [
            "size" => $size,
            "body" => [
                "query" => [
                    "bool" => [
                        "must" => [],
                        "filter" => [
                            [
                                "exists" => [
                                    "field" => "context.RequestID",
                                ],
                            ],
                            [
                                "exists" => [
                                    "field" => "context.ErrorCode",
                                ],
                            ],
                            [
                                "range" => [
                                    "@timestamp" => [
                                        "gte" => $start,
                                        "lte" => $end,
                                        "format" => "epoch_millis",
                                        //                                        "gte" => "2021-05-30T11:23:55.588Z",
                                        //                                        "lte" => "2021-05-31T11:23:55.588Z",
                                        //                                        "format" => "strict_date_optional_time"
                                    ],
                                ],
                            ],
                        ],
                        "should" => [],
                    ],
                ],
                "sort" => [["@timestamp" => ["order" => "asc", "unmapped_type" => "boolean"]]],
            ],
        ];

        $must = [];

        if (!empty($query)) {
            $must = [
                [
                    "query_string" => [
                        "query" => $query,
                        "analyze_wildcard" => true,
                        "default_field" => "*",
                    ],
                ],
            ];
        }
        $params['body']['query']['bool']['must'] = $must;

        return $params;
    }

    private function getParams2($start, $end, $priority, $provider)
    {
        $start = $start->getTimestamp() * 1000;
        $end = $end->getTimestamp() * 1000 - 1;
        $params = [
            "size" => 10000,
            "body" => [
                "query" => [
                    "bool" => [
                        "must" => [],
                        "filter" => [
                            [
                                "bool" => [
                                    "filter" => [
                                        [
                                            "multi_match" => [
                                                "type" => "phrase",
                                                "query" => "\"priority\":" . $priority,
                                                "lenient" => true,
                                            ],
                                        ],
                                        [
                                            "bool" => [
                                                "filter" => [
                                                    [
                                                        "multi_match" => [
                                                            "type" => "phrase",
                                                            "query" => "\"provider\":\"{$provider}\"",
                                                            "lenient" => true,
                                                        ],
                                                    ],
                                                    [
                                                        "multi_match" => [
                                                            "type" => "phrase",
                                                            "query" => "request data:*",
                                                            "lenient" => true,
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            [
                                "range" => [
                                    "@timestamp" => [
                                        "gte" => $start,
                                        "lte" => $end,
                                        "format" => "epoch_millis",
                                    ],
                                ],
                            ],
                        ],
                        "should" => [],
                    ],
                ],
                "sort" => [["@timestamp" => ["order" => "asc", "unmapped_type" => "boolean"]]],
            ],
        ];

        return $params;
    }
}
