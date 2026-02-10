<?php

namespace AwardWallet\MainBundle\Service\RA;

use AwardWallet\MainBundle\Service\AppBot\Adapter\Slack;
use AwardWallet\MainBundle\Service\AppBot\AppBot;
use Doctrine\DBAL\Connection;
use Elasticsearch\ClientBuilder;
use Psr\Log\LoggerInterface;

class RewardAvailabilityStatus
{
    public const ALL = 0;
    public const ERRORS = 1; // not success
    public const UNKNOWN_ERRORS = 2; // except 4 and success
    public const SUCCESS = 3; // for RA - 1, 9
    public const KNOWN_ERRORS = 4; // for RA - 4
    public const ALL_OVER90 = 5;
    public const ALL_OVER120 = 6;

    private const MSG_WARNING = ":warning: Alert: The success rate for the *%s* provider (%s) has dropped to %d%% (from %d%%) in the last %d hours.";
    private const MSG_INFO = ":information_source: Notice: The success rate for the *%s* provider (%s) has improved, reaching %d%% (from %d%%) over the past %d hours.";
    private const MSG_WARNING_OFF = ":warning: Alert: The *%s* provider (%s) has been turned off by the AwardWallet team.";
    private const MSG_INFO_OFF = ":information_source: Notice: The *%s* provider (%s) has been turned on by the AwardWallet team.";

    private \Memcached $memcached;

    private LoggerInterface $logger;

    private Connection $connection;

    private AppBot $appBot;

    private $client;

    /**
     * RewardAvailabilityStatus constructor.
     */
    public function __construct(
        \Memcached $memcached,
        string $elasticSearchHost,
        Connection $connection,
        LoggerInterface $logger,
        AppBot $appBot
    ) {
        $this->memcached = $memcached;
        $this->esAddress = $elasticSearchHost;
        $this->connection = $connection;
        $this->logger = $logger;
        $this->appBot = $appBot;

        $hosts = [trim($elasticSearchHost, '/') . ':9200'];
        // Instantiate a new ClientBuilder
        $this->client = ClientBuilder::create()
            ->setHosts($hosts)// Set the hosts
            ->build();
    }

    public function search(
        ?int $startDate = null,
        ?int $endDate = null,
        int $typeStates = self::ALL,
        bool $onlyAggData = true,
        string $cluster = 'juicymiles',
        string $type = 'flight',
        int $priority = 0,
        ?string $providerCode = null
    ) {
        if (!isset($endDate, $startDate) || $startDate > $endDate) {
            $endDate = time();
            $startDate = strtotime('-4 hours');
        } else {
            // 00:00 - 23:59
            // cut seconds
            $startDate = strtotime(date('Y-m-d H:i', $startDate));
            $endDate = strtotime(date('Y-m-d H:i', $endDate));
        }
        $checkAlarm = ($endDate - $startDate) >= 60 * 60 * 12;

        $extQuery = [];

        $extQuery[] = "app: " . $cluster;

        if ($type === 'hotel') {
            $method = "CheckReward-availability-hotel";
        } else {
            $method = "CheckReward-availability";
        }

        if (!empty($providerCode)) {
            $extQuery[] = "context.Provider: " . $providerCode;
        } else {
            $extQuery[] = "NOT context.Provider: testprovider";
        }

        if ($typeStates === self::ALL_OVER90) {
            $typeStates = self::ALL;
            $extQuery[] = "context.FullProcessingTimeGT90: 1";
        } elseif ($typeStates === self::ALL_OVER120) {
            $typeStates = self::ALL;
            $extQuery[] = "context.FullProcessingTimeGT120: 1";
        }
        $partner = $cluster === 'juicymiles' ? 'juicymiles' : 'awardwallet';

        if ($cluster === 'juicymiles') {
            $extQuery[] = "extra.partner: " . $partner;
        }

        if (!empty($priority)) {
            $extQuery[] = "context.Priority: " . $priority;
        }
        $key = $cluster . $priority . $partner . $startDate . $endDate . $typeStates . ($onlyAggData ? 1 : 0) . $providerCode;
        $key2 = $cluster . $priority . $partner . $startDate . $endDate . $typeStates . 0 . $providerCode;

        $key = 'reward_availability_' . md5($key);

        // for Debug
        //        $this->memcached->delete($key);
        $newData = $this->memcached->get($key);

        if ($newData !== false) {
            return $newData;
        }

        if ($onlyAggData) {
            // agg есть и в кэше для неагригированного
            $key2 = 'reward_availability_' . md5($key2);

            // for Debug
            //            $this->memcached->delete($key2);
            $newData = $this->memcached->get($key2);

            if ($newData !== false) {
                return $newData;
            }
        }

        $newData = [];

        $query = implode(" AND ", $extQuery);

        $params = $this->getParams(addslashes($query), $method, $startDate, $endDate, $typeStates, $onlyAggData);
        $response = $this->client->search($params);
        $params = $this->getParams(addslashes($query), $method, $startDate, $endDate, $typeStates, $onlyAggData, true);
        $responseCapt = $this->client->search($params);
        $bucketsCapt = $responseCapt['aggregations']['pCode']['buckets'];

        if (!isset($response['hits']['hits'],$response['aggregations']['pCode']['buckets'])) {
            $this->logger->info('RewardAvailability. empty response or other format: [' . date("Y-m-d H:i",
                $startDate) . ', ' . date("Y-m-d H:i", $endDate) . ')');

            return $newData;
        }
        $lastSearch = $this->searchLast($startDate, $endDate, $cluster, $type);

        $hits = [];

        foreach ($response['hits']['hits'] as $hit) {
            $hits[] = array_intersect_key($hit['_source']['context'], [
                'RequestID' => true,
                'Partner' => true,
                'Provider' => true,
                'ErrorCode' => true,
                'RequestDateTime' => true,
                'CheckStartDateTime' => true,
                'CheckCompleteDateTime' => true,
                'FullProcessingTime' => true,
                'FullProcessingTimeGT90' => true,
                'FullProcessingTimeGT120' => true,
                'QueueTime' => true,
                'CaptchaTime' => true,
                'ParsingTime' => true,
                'ParsingTimeGT90' => true,
                'ParsingTimeGT120' => true,
                'ParseRetries' => true,
                'RequestData' => true,
                'ResponseData' => true,
            ]);
        }
        $providerData = $this->connection->executeQuery(/** @lang MySQL */ "SELECT Code, DisplayName, RewardAvailabilityPriority FROM Provider WHERE RewardAvailabilityPriority>0")->fetchAllAssociative();
        $providerPriority = [];

        $providerName = [];

        foreach ($providerData as $row) {
            $providerPriority[$row['Code']] = $row['RewardAvailabilityPriority'];
            $providerName[$row['Code']] = $row['DisplayName'];
        }

        $buckets = [];

        foreach ($response['aggregations']['pCode']['buckets'] as $bucket) {
            $prov = $bucket['key'];
            $bucketCapt = array_values(array_filter($bucketsCapt,
                function ($s) use ($prov) {
                    return $s['key'] === $prov;
                }));
            $buckets[$bucket['key']]['lastTime'] = $lastSearch[$bucket['key']] ?? 'n/a';
            $buckets[$bucket['key']]['priority'] = $providerPriority[$bucket['key']] ?? 999;

            foreach ($bucket['eCode']['buckets'] as $subBucket) {
                $this->incStat($buckets[$bucket['key']], 'requests', $subBucket['doc_count']);

                switch (true) {
                    case $subBucket['key'] === ACCOUNT_CHECKED:
                    case $subBucket['key'] === ACCOUNT_WARNING:
                        $this->incStat($buckets[$bucket['key']], 'success', $subBucket['doc_count']);
                        $this->incStat($buckets[$bucket['key']], 'successState', $subBucket['doc_count'], $subBucket['key']);

                        break;

                    case $subBucket['key'] !== ACCOUNT_PROVIDER_ERROR:
                        $this->incStat($buckets[$bucket['key']], 'unknown', $subBucket['doc_count']);
                        $this->incStat($buckets[$bucket['key']], 'unknownState', $subBucket['doc_count'], $subBucket['key']);
                        $this->incStat($buckets[$bucket['key']], 'errors', $subBucket['doc_count']);

                        break;

                    case $subBucket['key'] === ACCOUNT_PROVIDER_ERROR:
                        $this->incStat($buckets[$bucket['key']], 'known', $subBucket['doc_count']);
                        $this->incStat($buckets[$bucket['key']], 'knownState', $subBucket['doc_count'], $subBucket['key']);
                        $this->incStat($buckets[$bucket['key']], 'errors', $subBucket['doc_count']);

                        break;

                    default:
                        $this->incStat($buckets[$bucket['key']], 'errors', $subBucket['doc_count']);

                        break;
                }
            }

            // $checkAlarm
            if (!isset($buckets[$bucket['key']]['successState'][1])) {
                $buckets[$bucket['key']]['alarm'] = true;
            }
            $buckets[$bucket['key']]['parsingTime'] = [
                'min' => $bucket['sParsingTime']['min'],
                'max' => $bucket['sParsingTime']['max'],
                'avg' => round($bucket['sParsingTime']['avg'], 2),
            ];
            $buckets[$bucket['key']]['captchaTime'] = [
                'min' => isset($bucketCapt[0]) ? $bucketCapt[0]['sCaptchaTime']['min'] : null,
                'max' => isset($bucketCapt[0]) ? $bucketCapt[0]['sCaptchaTime']['max'] : null,
                'avg' => isset($bucketCapt[0]) ? round($bucketCapt[0]['sCaptchaTime']['avg'], 2) : null,
            ];

            if (isset($bucket['totalSuccess']['buckets'][0])) {
                $buckets[$bucket['key']]['totalSuccess'] = array_sum(array_map(function ($s) {
                    return $s['doc_count'];
                }, $bucket['totalSuccess']['buckets'][0]['success']['buckets']));
            }
            $buckets[$bucket['key']]['parsingTimeGT90'] = [
                'sum' => $bucket['sParsingTimeGT90']['sum'],
                'all' => $bucket['sParsingTimeGT90']['count'],
            ];
            $buckets[$bucket['key']]['parsingTimeGT120'] = [
                'sum' => $bucket['sParsingTimeGT120']['sum'],
                'all' => $bucket['sParsingTimeGT120']['count'],
            ];
            $buckets[$bucket['key']]['queueTime'] = [
                'min' => $bucket['sQueueTime']['min'],
                'max' => $bucket['sQueueTime']['max'],
                'avg' => round($bucket['sQueueTime']['avg'], 2),
            ];
            $buckets[$bucket['key']]['fullProcessingTime'] = [
                'min' => $bucket['sFullProcessingTime']['min'],
                'max' => $bucket['sFullProcessingTime']['max'],
                'avg' => round($bucket['sFullProcessingTime']['avg'], 2),
            ];
            $buckets[$bucket['key']]['fullProcessingTimeGT90'] = [
                'sum' => $bucket['sFullProcessingTimeGT90']['sum'],
                'all' => $bucket['sFullProcessingTimeGT90']['count'],
            ];
            $buckets[$bucket['key']]['fullProcessingTimeGT120'] = [
                'sum' => $bucket['sFullProcessingTimeGT120']['sum'],
                'all' => $bucket['sFullProcessingTimeGT120']['count'],
            ];
        }

        $result = ['list' => $hits, 'aggData' => $buckets, 'providerName' => $providerName];
        $this->memcached->set($key, $result, 10);

        return $result;
    }

    // TODO only for Point.me, only flights
    public function checkChanges(int $lastHours): void
    {
        $endDate = time();
        $startDate = strtotime('-' . $lastHours . ' hours', $endDate);
        $firstDate = strtotime('-' . $lastHours . ' hours', $startDate);

        $result = [];

        $res1 = $this->search($firstDate, $startDate);
        $res2 = $this->search($startDate, $endDate);

        foreach ($res1['aggData'] as $provider => $data1) {
            if (!isset($res2['aggData'][$provider])) {
                // stopped parse or started (nothing compare)
                continue;
            }
            $data2 = $res2['aggData'][$provider];

            // ???            $data['success']
            if (!array_key_exists('totalSuccess', $data1)) {
                $this->logger->info("missing total success: " . json_encode($data1));
            }

            $percent1 = round($data1['totalSuccess'] * 100 / $data1['requests']);
            $percent2 = round($data2['totalSuccess'] * 100 / $data2['requests']);
            $diff = $percent2 - $percent1;

            if ($percent2 < 10 || $percent1 < 10 || abs($diff) > 20) {
                $result[] = [
                    'up' => ($diff > 0),
                    'providerCode' => $provider,
                    'providerName' => $res1['providerName'][$provider],
                    'valueNew' => $percent2,
                    'valueOld' => $percent1,
                ];
            }
        }

        $msg = [];

        foreach ($result as $item) {
            if (!$item['up']) {
                $text = sprintf(self::MSG_WARNING, $item['providerCode'], $item['providerName'], $item['valueNew'], $item['valueOld'], $lastHours);
            } else {
                $text = sprintf(self::MSG_INFO, $item['providerCode'], $item['providerName'], $item['valueNew'], $item['valueOld'], $lastHours);
            }
            $msg[] = $text;
        }

        if (!empty($msg)) {
            $message = [
                'text' => '',
                'blocks' => [
                    [
                        'type' => 'section',
                        'text' => [
                            'type' => 'mrkdwn',
                            'text' => implode("\n", $msg),
                        ],
                    ],
                ],
            ];
            $this->appBot->send(Slack::CHANNEL_LAUNCH_CRAWLERS, $message);
            $this->appBot->send(Slack::CHANNEL_AW_RA_ALERTS, $message);
        }
    }

    public function alertCanCheckRewardAvailability(string $providerCode, int $isTurnOn)
    {
        $providerName = $this->connection->executeQuery(/** @lang MySQL */ "SELECT DisplayName FROM Provider WHERE Code = ?", [$providerCode])->fetchOne();

        if ($isTurnOn !== 1) {
            $text = sprintf(self::MSG_WARNING_OFF, $providerCode, $providerName);
        } else {
            $text = sprintf(self::MSG_INFO_OFF, $providerCode, $providerName);
        }
        $message = [
            'text' => '',
            'blocks' => [
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => $text,
                    ],
                ],
            ],
        ];
        $this->appBot->send(Slack::CHANNEL_LAUNCH_CRAWLERS, $message);
        $this->appBot->send(Slack::CHANNEL_AW_RA_ALERTS, $message);
    }

    private function searchLast(int $startDate, int $endDate, string $cluster, string $type)
    {
        $extQuery = [];
        $partner = ($cluster === 'juicymiles' ? 'juicymiles' : 'awardwallet');

        if ($cluster === 'juicymiles') {
            $extQuery[] = "extra.partner: " . $partner;
        }
        $key = $partner . $startDate . $endDate;

        $key = 'reward_availability_lastsearch_' . md5($key);

        // for Debug
        //        $this->memcached->delete($key);
        $newData = $this->memcached->get($key);

        if ($newData !== false) {
            return $newData;
        }

        if ($type === 'hotel') {
            $method = "CheckReward-availability-hotel";
        } else {
            $method = "CheckReward-availability";
        }
        $newData = [];

        $query = implode(" AND ", $extQuery);
        $params = $this->getParamsLastSearch(addslashes($query), $method, $startDate, $endDate);
        $response = $this->client->search($params);

        if (!isset($response['hits']['hits'],$response['aggregations']['pCode']['buckets'])) {
            $this->logger->info('RewardAvailability. empty response or other format: [' . date("Y-m-d H:i",
                $startDate) . ', ' . date("Y-m-d H:i", $endDate) . ']');

            return $newData;
        }

        $result = [];

        foreach ($response['aggregations']['pCode']['buckets'] as $bucket) {
            $result[$bucket['key']] = preg_replace("/\d{4}-(\d{2})-(\d{2})T(\d{2}:\d{2}).+/", ' $3 $1/$2', $bucket['sLastTime']['max_as_string']);
        }

        $this->memcached->set($key, $result, 10);

        return $result;
    }

    private function incStat(&$data, $fieldName, $value = 1, $extField = null)
    {
        if (isset($extField)) {
            if (isset($data[$fieldName][$extField])) {
                $data[$fieldName][$extField] += $value;
            } else {
                $data[$fieldName][$extField] = $value;
            }
        } else {
            if (isset($data[$fieldName])) {
                $data[$fieldName] += $value;
            } else {
                $data[$fieldName] = $value;
            }
        }
    }

    // query for extra filter partner, provider
    // only
    private function getParams(string $query, string $method, int $startDate, int $endDate, int $typeStates, bool $onlyAggData, ?bool $noZeroCapture = false): array
    {
        $start = $startDate * 1000;
        $end = $endDate * 1000 - 1;

        switch ($typeStates) {
            case self::ERRORS:
                $mustNot = [
                    ["match_phrase" => ["context.ErrorCode" => ["query" => ACCOUNT_CHECKED]]],
                    ["match_phrase" => ["context.ErrorCode" => ["query" => ACCOUNT_WARNING]]],
                ];

                break;

            case self::UNKNOWN_ERRORS:
                $mustNot = [
                    ["match_phrase" => ["context.ErrorCode" => ["query" => ACCOUNT_CHECKED]]],
                    ["match_phrase" => ["context.ErrorCode" => ["query" => ACCOUNT_PROVIDER_ERROR]]],
                    ["match_phrase" => ["context.ErrorCode" => ["query" => ACCOUNT_WARNING]]],
                ];

                break;

            default:
                $mustNot = [];

                break;
        }

        if ($noZeroCapture) {
            $mustNot[] = ["match_phrase" => ["context.CaptchaTime" => ["query" => 0]]];
        }

        if ($onlyAggData) {
            $size = 0;
        } else {
            $size = 200;
        }

        $params = [
            "size" => $size,
            "body" => [
                "query" => [
                    "bool" => [
                        "must" => [],
                        "filter" => [
                            [
                                "bool" => [
                                    "filter" => [
                                        [
                                            "bool" => [
                                                "should" => [
                                                    [
                                                        "match_phrase" => [
                                                            "extra.app" => "loyalty",
                                                        ],
                                                    ],
                                                ],
                                                "minimum_should_match" => 1,
                                            ],
                                        ],
                                        [
                                            "bool" => [
                                                "should" => [
                                                    [
                                                        "match_phrase" => [
                                                            "context.Method" => $method,
                                                            //                                                            "extra.worker_executor" => "reward-availability"
                                                        ],
                                                    ],
                                                ],
                                                "minimum_should_match" => 1,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
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
                        "must_not" => $mustNot,
                    ],
                ],
                "sort" => [["@timestamp" => ["order" => "desc", "unmapped_type" => "boolean"]]],
                "aggs" => [
                    "pCode" => [
                        "terms" => [
                            "field" => "context.Provider",
                            "size" => 100,
                        ],
                        "aggs" => [
                            "eCode" => [
                                "terms" => [
                                    "field" => "context.ErrorCode",
                                    "size" => 100,
                                ],
                            ],
                            "sFullProcessingTime" => [
                                "stats" => [
                                    "field" => "context.FullProcessingTime",
                                ],
                            ],
                            "sFullProcessingTimeGT90" => [
                                "stats" => [
                                    "field" => "context.FullProcessingTimeGT90",
                                ],
                            ],
                            "sFullProcessingTimeGT120" => [
                                "stats" => [
                                    "field" => "context.FullProcessingTimeGT120",
                                ],
                            ],
                            "sQueueTime" => [
                                "stats" => [
                                    "field" => "context.QueueTime",
                                ],
                            ],
                            "sParsingTime" => [
                                "stats" => [
                                    "field" => "context.ParsingTime",
                                ],
                            ],
                            "sCaptchaTime" => [
                                "stats" => [
                                    "field" => "context.CaptchaTime",
                                ],
                            ],
                            "sParsingTimeGT90" => [
                                "stats" => [
                                    "field" => "context.ParsingTimeGT90",
                                ],
                            ],
                            "sParsingTimeGT120" => [
                                "stats" => [
                                    "field" => "context.ParsingTimeGT120",
                                ],
                            ],
                            "totalSuccess" => [
                                "terms" => [
                                    "field" => "context.FullProcessingTimeGT90",
                                    "exclude" => [1],
                                ],
                                "aggs" => [
                                    "success" => [
                                        "terms" => [
                                            "field" => "context.ErrorCode",
                                            "include" => [ACCOUNT_CHECKED, ACCOUNT_PROVIDER_ERROR, ACCOUNT_WARNING],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $must = [];

        if (!empty($query)) {
            if ($typeStates === self::SUCCESS) {
                $query .= ' AND (context.ErrorCode:' . ACCOUNT_CHECKED . ' OR context.ErrorCode:' . ACCOUNT_WARNING . ')';
            } elseif ($typeStates === self::KNOWN_ERRORS) {
                $query .= ' AND context.ErrorCode:' . ACCOUNT_PROVIDER_ERROR;
            }
            $must = [
                [
                    "query_string" => [
                        "query" => $query,
                        "analyze_wildcard" => true,
                        "default_field" => "*",
                    ],
                ],
            ];
        } else {
            if ($typeStates === self::SUCCESS) {
                $must = [
                    ["match_phrase" => ["context.ErrorCode" => ["query" => ACCOUNT_CHECKED]]],
                    ["match_phrase" => ["context.ErrorCode" => ["query" => ACCOUNT_WARNING]]],
                ];
            } elseif ($typeStates === self::KNOWN_ERRORS) {
                $must = [
                    ["match_phrase" => ["context.ErrorCode" => ["query" => ACCOUNT_PROVIDER_ERROR]]],
                ];
            }
        }
        $params['body']['query']['bool']['must'] = $must;

        return $params;
    }

    private function getParamsLastSearch(string $query, string $method, int $startDate, int $endDate): array
    {
        $start = $startDate * 1000;
        $end = $endDate * 1000 - 1;
        $mustNot = [];
        $size = 0;

        $params = [
            "size" => $size,
            "body" => [
                "query" => [
                    "bool" => [
                        "must" => [],
                        "filter" => [
                            [
                                "bool" => [
                                    "filter" => [
                                        [
                                            "bool" => [
                                                "should" => [
                                                    [
                                                        "match_phrase" => [
                                                            "extra.app" => "loyalty",
                                                        ],
                                                    ],
                                                ],
                                                "minimum_should_match" => 1,
                                            ],
                                        ],
                                        [
                                            "bool" => [
                                                "should" => [
                                                    [
                                                        "match_phrase" => [
                                                            "context.Method" => $method,
                                                        ],
                                                    ],
                                                ],
                                                "minimum_should_match" => 1,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
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
                                    ],
                                ],
                            ],
                        ],
                        "should" => [],
                        "must_not" => $mustNot,
                    ],
                ],
                "sort" => [["@timestamp" => ["order" => "desc", "unmapped_type" => "boolean"]]],
                "aggs" => [
                    "pCode" => [
                        "terms" => [
                            "field" => "context.Provider",
                            "size" => 100,
                        ],
                        "aggs" => [
                            "sLastTime" => [
                                "stats" => [
                                    "field" => "@timestamp",
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $must = [];
        $params['body']['query']['bool']['must'] = $must;

        return $params;
    }
}
