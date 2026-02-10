<?php

namespace AwardWallet\MainBundle\Service;

use Doctrine\DBAL\Connection;
use Elasticsearch\ClientBuilder;
use Psr\Log\LoggerInterface;

class ProviderProxyStats
{
    private \Memcached $memcached;

    private LoggerInterface $logger;

    private Connection $connection;

    private $client;

    /**
     * RewardAvailabilityStatus constructor.
     */
    public function __construct(
        \Memcached $memcached,
        string $elasticSearchHost,
        Connection $connection,
        LoggerInterface $logger
    ) {
        $this->memcached = $memcached;
        $this->esAddress = $elasticSearchHost;
        $this->connection = $connection;
        $this->logger = $logger;

        $hosts = [trim($elasticSearchHost, '/') . ':9200'];
        // Instantiate a new ClientBuilder
        $this->client = ClientBuilder::create()
            ->setHosts($hosts)// Set the hosts
            ->build();
    }

    // $providerCode empty - search without separation by providers
    // $providerCode 'all' or code - search by providers
    public function search(
        string $cluster,
        string $type,
        ?int $startDate = null,
        ?int $endDate = null,
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

        $extQuery = [];

        if ($cluster === 'awardwallet') {
            $extQuery[] = "app: loyalty";
        } else {
            $extQuery[] = "app: " . $cluster;
        }

        if ($type === 'hotel') {
            $executor = "reward-availability-hotel";
        } else {
            $executor = "reward-availability";
        }

        if (!empty($providerCode) && $providerCode !== 'all') {
            $extQuery[] = "extra.provider: " . $providerCode;
        }
        $partner = $cluster === 'juicymiles' ? 'juicymiles' : 'awardwallet';

        if ($cluster === 'juicymiles') {
            $extQuery[] = "extra.partner: " . $partner;
        }
        $key = $cluster . $partner . $startDate . $endDate . $providerCode;

        $key = 'reward_availability_proxy_' . md5($key);

        // for Debug
        //        $this->memcached->delete($key);
        $newData = $this->memcached->get($key);

        if ($newData !== false) {
            return $newData;
        }

        $query = implode(" AND ", $extQuery);

        if (!empty($providerCode)) {
            $params = $this->getParamsProvider(addslashes($query), $executor, $startDate, $endDate);
            $response1 = $this->client->search($params);

            if (!isset($response1['hits']['hits'], $response1['aggregations']['provider']['buckets'])) {
                $this->logger->info('ProviderProxyStats. empty response or other format: [' . date("Y-m-d H:i",
                    $startDate) . ', ' . date("Y-m-d H:i", $endDate) . '] for ' . $providerCode);

                return [];
            }
            $params = $this->getParamsProviderMixedProxy(addslashes($query), $executor, $startDate, $endDate);
            $response2 = $this->client->search($params);
            $result = $this->prepareResponseProvider($response1, $response2);
        } else {
            $params = $this->getParamsProxy(addslashes($query), $executor, $startDate, $endDate, true);
            $response = $this->client->search($params);

            if (!isset($response['hits']['hits'], $response['aggregations']['byInterval']['buckets'])) {
                $this->logger->info('ProxyStats. empty response or other format: [' . date("Y-m-d H:i",
                    $startDate) . ', ' . date("Y-m-d H:i", $endDate) . ']');

                return [];
            }
            $result = $this->prepareResponseProxy($response);
        }

        $this->memcached->set($key, $result, 10);

        return $result;
    }

    private function prepareResponseProvider($data, $dataMixed)
    {
        $buckets = [];
        $docCountProv = $data['aggregations']['providerCnt']['value'];

        foreach ($data['aggregations']['provider']['buckets'] as $bucket) {
            $prov = $bucket['key'];

            if ($bucket['proxyCnt']['value'] == 0) {
                $buckets[$prov]['errorMsg'] = 'no proxy';
            }

            foreach ($bucket['proxy']['buckets'] as $proxy) {
                $docCountProxy = $bucket['proxyCnt']['value'];
                $buckets[$prov][$proxy['key']] = [
                    'percent' => $docCountProxy,
                    //                    'percent' => round((float) $docCountProxy / $docCountProv * 100, 2),
                ];

                foreach ($proxy['region']['buckets'] as $region) {
                    $proxyName = $proxy['key'] . '-' . $region['key'];
                    $docCountRegion = $proxy['regionCnt']['value'];
                    $percentRegion = round((float) $docCountRegion / $docCountProxy * 100, 2);

                    $buckets[$prov]['uniqueAddress'][$proxyName] = $region['cntUniqueAddress']['value'];

                    foreach ($region['retryNumber']['buckets'] as $retryNumber) {
                        $buckets[$prov]['details'][$proxyName]['tableRetry']['header'] = [
                            $proxyName,
                            '1',
                            '9',
                            '4',
                            '10',
                            '6',
                            '11',
                        ];

                        if (!isset($buckets[$prov]['details'][$proxyName]['tableRetry']['data'])) {
                            $buckets[$prov]['details'][$proxyName]['tableRetry']['data'] = [];
                        }
                        $numData = array_push(
                            $buckets[$prov]['details'][$proxyName]['tableRetry']['data'],
                            ['retry ' . $retryNumber['key'], 0, 0, 0, 0, 0, 0]
                        ) - 1;

                        foreach ($retryNumber['errorCode']['buckets'] as $errorCode) {
                            $docCountErrorCode = $retryNumber['errorCodeCnt']['value'];
                            //                            $percentErrorCode = round((float) $errorCode['doc_count'] / $docCountErrorCode * 100, 2);
                            $percentErrorCode = $errorCode['doc_count'];

                            if (!in_array((string) $errorCode['key'],
                                array_values($buckets[$prov]['details'][$proxyName]['tableRetry']['header']))) {
                                $buckets[$prov]['details'][$proxyName]['tableRetry']['header'][] = (string) $errorCode['key'];
                            }
                            $numKey = array_search((string) $errorCode['key'],
                                $buckets[$prov]['details'][$proxyName]['tableRetry']['header']);
                            $buckets[$prov]['details'][$proxyName]['tableRetry']['data'][$numData][$numKey] = $percentErrorCode;

                            $buckets[$prov][$proxy['key']][$region['key']][$retryNumber['key']]['errorCode'][(string) $errorCode['key']] = $errorCode['doc_count'];
                        }
                    }

                    $buckets[$prov]['tableError']['header'] = ['Error Codes Stats', '1', '9', '4', '10', '6', '11'];

                    if (!isset($buckets[$prov]['tableError']['data'])) {
                        $buckets[$prov]['tableError']['data'] = [];
                    }
                    $numData = array_push($buckets[$prov]['tableError']['data'],
                        [$proxy['key'] . '-' . $region['key'], 0, 0, 0, 0, 0, 0]) - 1;

                    foreach ($region['errorCode']['buckets'] as $errorCode) {
                        $docCountErrorCode = $region['errorCodeCnt']['value'];
                        //                        $percentErrorCode = round((float) $errorCode['doc_count'] / $docCountErrorCode * 100, 2);
                        $percentErrorCode = $errorCode['doc_count'];

                        if (!in_array((string) $errorCode['key'],
                            array_values($buckets[$prov]['tableError']['header']))) {
                            $buckets[$prov]['tableError']['header'][] = (string) $errorCode['key'];
                        }
                        $numKey = array_search((string) $errorCode['key'], $buckets[$prov]['tableError']['header']);
                        $buckets[$prov]['tableError']['data'][$numData][$numKey] = $percentErrorCode;

                        $buckets[$prov][$proxy['key']][$region['key']]['errorCode'][(string) $errorCode['key']] = $errorCode['doc_count'];
                    }
                }
            }
        }
        $bucketsMix = [];

        foreach ($dataMixed['hits']['hits'] as $doc) {
            $fields = $doc['fields'];

            if (!isset($fields['context.proxyProviderOnInit'][0])) {
                continue;
            }
            $bucketsMix[$fields['extra.provider'][0]][json_encode($fields['proxyChangeHelped'][0])]['data'][] = [
                'proxyProviderOnInit' => isset($fields['context.proxyProviderOnInit']) ? $fields['context.proxyProviderOnInit'][0] : null,
                'proxyRegionOnInit' => isset($fields['context.proxyRegionOnInit']) ? $fields['context.proxyRegionOnInit'][0] : null,
                'proxyAddressOnInit' => isset($fields['context.proxyAddressOnInit']) ? $fields['context.proxyAddressOnInit'][0] : null,
                'proxyProvider' => isset($fields['context.proxyProvider']) ? $fields['context.proxyProvider'][0] : null,
                'proxyRegion' => isset($fields['context.proxyRegion']) ? $fields['context.proxyRegion'][0] : null,
                'proxyAddress' => isset($fields['context.proxyAddress']) ? $fields['context.proxyAddress'][0] : null,
                'errorCode' => isset($fields['context.errorCode']) ? $fields['context.errorCode'][0] : null,
                'retryNumber' => isset($fields['context.retryNumber']) ? $fields['context.retryNumber'][0] : null,
                'requestId' => isset($fields['extra.requestId']) ? $fields['extra.requestId'][0] : null,
            ];
        }
        $records = $bucketsMix;

        foreach ($records as $prov => $provData) {
            $provCnt = count($provData['true']['data'] ?? []) + count($provData['false']['data'] ?? []);

            if ($provCnt === 0) {
                continue;
            }
            $buckets[$prov]['mixed']['list'] = $bucketsMix[$prov];
            $buckets[$prov]['mixed']['helped']['true'] = round(count($provData['true']['data'] ?? []) / $provCnt,
                4) * 100;
            $buckets[$prov]['mixed']['helped']['false'] = 100 - $buckets[$prov]['mixed']['helped']['true'];
        }

        return ['prov' => $buckets];
    }

    private function prepareResponseProxy($data)
    {
        $buckets = [];

        foreach ($data['aggregations']['byInterval']['buckets'] as $bucket) {
            $interval = str_replace('T', ' ', substr($bucket['key_as_string'], 0, 16));
            $docCountProxy = $bucket['proxyCnt']['value'];

            foreach ($bucket['proxy']['buckets'] as $proxy) {
                $proxyProv = $proxy['key'];

                foreach ($proxy['region']['buckets'] as $region) {
                    $proxyProvReg = $region['key'];
                    $docCountRegion = $region['errorCodeCnt']['value'];
                    $buckets[$interval][$proxyProv . '-' . $proxyProvReg]['success'] = 0.0;

                    foreach ($region['success']['buckets'] as $errorCode) {
                        $buckets[$interval][$proxyProv . '-' . $proxyProvReg]['success'] += $errorCode['doc_count'];
                    }
                    $buckets[$interval][$proxyProv . '-' . $proxyProvReg]['success'] = round($buckets[$interval][$proxyProv . '-' . $proxyProvReg]['success'] / $docCountRegion,
                        2) * 100;
                    $buckets[$interval][$proxyProv . '-' . $proxyProvReg]['failed'] = 100 - $buckets[$interval][$proxyProv . '-' . $proxyProvReg]['success'];
                    $buckets[$interval][$proxyProv . '-' . $proxyProvReg]['uniqueAddress'] = $region['cntUniqueAddress']['value'];
                }
            }
        }
        $statIp = [];

        foreach ($data['aggregations']['proxy']['buckets'] as $proxy) {
            $proxyProv = $proxy['key'];

            foreach ($proxy['region']['buckets'] as $region) {
                $proxyProvReg = $region['key'];
                $statIp[$proxyProv][$proxyProvReg]['uniqueAddress'] = $region['cntUniqueAddress']['value'];
            }
        }

        return ['data' => $buckets, 'stat' => $statIp];
    }

    private function getDefaultParams(string $query, string $executor, int $startDate, int $endDate): array
    {
        $start = $startDate * 1000;
        $end = $endDate * 1000 - 1;
        $size = 0;

        $params = [
            "size" => $size,
            "body" => [
                "query" => [
                    "bool" => [
                        "must" => [
                            [
                                "query_string" => [
                                    "query" => $query,
                                    "analyze_wildcard" => true,
                                    "default_field" => "*",
                                ],
                            ],
                        ],
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
                                                            "extra.worker_executor" => $executor,
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
                                                            "message" => "proxy statistic",
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
                                    "field" => "context.errorCode",
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
                        "must_not" => [],
                    ],
                ],
                "sort" => [["@timestamp" => ["order" => "desc", "unmapped_type" => "boolean"]]],
                "aggs" => [],
            ],
        ];

        return $params;
    }

    private function getProxyAggs(?bool $onInit = false)
    {
        if ($onInit) {
            $proxyField = "context.proxyProviderOnInit";
            $regionField = "context.proxyRegionOnInit";
            $addressField = "context.proxyAddressOnInit";
        } else {
            $proxyField = "context.proxyProvider";
            $regionField = "context.proxyRegion";
            $addressField = "context.proxyAddress";
        }

        return [
            "terms" => [
                "field" => $proxyField,
                "size" => 100,
                "order" => ["_term" => "asc"],
            ],
            "aggs" => [
                "regionCnt" => [
                    "value_count" => [
                        "field" => $regionField,
                    ],
                ],
                "region" => [
                    "terms" => [
                        "field" => $regionField,
                        "size" => 20,
                        "order" => ["_term" => "asc"],
                    ],
                    "aggs" => [
                        "errorCodeCnt" => [
                            "value_count" => [
                                "field" => "context.errorCode",
                            ],
                        ],
                        "cntUniqueAddress" => [
                            "cardinality" => [
                                "field" => $addressField,
                            ],
                        ],
                        "success" => [
                            "terms" => [
                                "field" => "context.errorCode",
                                "include" => [1, 4, 9],
                                "size" => 3,
                                "order" => ["_term" => "asc"],
                            ],
                        ],
                        "failed" => [
                            "terms" => [
                                "field" => "context.errorCode",
                                "exclude" => [1, 4, 9],
                                "size" => 10,
                                "order" => ["_term" => "asc"],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function getProviderAggs(?bool $onInit = false)
    {
        if ($onInit) {
            $proxyField = "context.proxyProviderOnInit";
            $regionField = "context.proxyRegionOnInit";
            $addressField = "context.proxyAddressOnInit";
        } else {
            $proxyField = "context.proxyProvider";
            $regionField = "context.proxyRegion";
            $addressField = "context.proxyAddress";
        }

        return [
            "terms" => [
                "field" => "extra.provider",
                "size" => 100,
                "order" => ["_term" => "asc"],
            ],
            "aggs" => [
                "retryNumberCnt" => [
                    "value_count" => [
                        "field" => "context.retryNumber",
                    ],
                ],
                "retryNumber" => [
                    "terms" => [
                        "field" => "context.retryNumber",
                        "size" => 5,
                        "order" => ["_term" => "asc"],
                    ],
                    "aggs" => [
                        "errorCodeCnt" => [
                            "value_count" => [
                                "field" => "context.errorCode",
                            ],
                        ],
                        "errorCode" => [
                            "terms" => [
                                "field" => "context.errorCode",
                                "size" => 12,
                                "order" => ["_term" => "asc"],
                            ],
                        ],
                        "cntUniqueAddress" => [
                            "cardinality" => [
                                "field" => $addressField,
                            ],
                        ],
                        "retryNumberCnt" => [
                            "value_count" => [
                                "field" => "context.retryNumber",
                            ],
                        ],
                        "retryNumber" => [
                            "terms" => [
                                "field" => "context.retryNumber",
                                "size" => 5,
                                "order" => ["_term" => "asc"],
                            ],
                        ],
                    ],
                ],
                "proxyCnt" => [
                    "value_count" => [
                        "field" => $proxyField,
                    ],
                ],
                "proxy" => [
                    "terms" => [
                        "field" => $proxyField,
                        "size" => 10,
                        "order" => ["_term" => "asc"],
                    ],
                    "aggs" => [
                        "regionCnt" => [
                            "value_count" => [
                                "field" => $regionField,
                            ],
                        ],
                        "region" => [
                            "terms" => [
                                "field" => $regionField,
                                "size" => 20,
                                "order" => ["_term" => "asc"],
                            ],
                            "aggs" => [
                                "errorCodeCnt" => [
                                    "value_count" => [
                                        "field" => "context.errorCode",
                                    ],
                                ],
                                "errorCode" => [
                                    "terms" => [
                                        "field" => "context.errorCode",
                                        "size" => 12,
                                        "order" => ["_term" => "asc"],
                                    ],
                                ],
                                "cntUniqueAddress" => [
                                    "cardinality" => [
                                        "field" => $addressField,
                                    ],
                                ],
                                "retryNumberCnt" => [
                                    "value_count" => [
                                        "field" => "context.retryNumber",
                                    ],
                                ],
                                "retryNumber" => [
                                    "terms" => [
                                        "field" => "context.retryNumber",
                                        "size" => 5,
                                        "order" => ["_term" => "asc"],
                                    ],
                                    "aggs" => [
                                        "errorCodeCnt" => [
                                            "value_count" => [
                                                "field" => "context.errorCode",
                                            ],
                                        ],
                                        "errorCode" => [
                                            "terms" => [
                                                "field" => "context.errorCode",
                                                "size" => 12,
                                                "order" => ["_term" => "asc"],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    // ['day','hour', ...]
    private function getByInterval($type)
    {
        return [
            "date_histogram" => [
                "field" => "@timestamp",
                "calendar_interval" => $type,
            ],
            "aggs" => [],
        ];
    }

    private function getParamsProxy(string $query, string $executor, int $startDate, int $endDate, ?bool $withByInterval = false): array
    {
        $params = $this->getDefaultParams($query, $executor, $startDate, $endDate);

        $proxyHead = $this->getProxyAggs();

        if ($withByInterval) {
            if ($endDate - $startDate > 60 * 60 * 24 * 2) {
                $type = 'day';
            } else {
                $type = 'hour';
            }
            $byInterval = $this->getByInterval($type);
            $byInterval['aggs'] = [
                'proxy' => $proxyHead,
                'proxyCnt' => [
                    "value_count" => [
                        "field" => "context.proxyProvider",
                    ],
                ],
            ];
            $params['body']['aggs'] =
                [
                    'byInterval' => $byInterval,
                    'proxy' => $proxyHead,
                ];
        } else {
            $params['body']['aggs'] = [
                'proxy' => $proxyHead,
                'proxyCnt' => [
                    "value_count" => [
                        "field" => "context.proxyProvider",
                    ],
                ],
            ];
        }

        return $params;
    }

    private function getParamsProvider(
        string $query,
        string $executor,
        int $startDate,
        int $endDate,
        ?bool $withByInterval = false
    ): array {
        $params = $this->getDefaultParams($query, $executor, $startDate, $endDate);

        $providerHead = $this->getProviderAggs();

        if ($withByInterval) {// not used
            if ($endDate - $startDate > 60 * 60 * 24 * 2) {
                $type = 'day';
            } else {
                $type = 'hour';
            }
            $byInterval = $this->getByInterval($type);
            $byInterval['aggs'] = [
                'provider' => $providerHead,
                'providerCnt' => [
                    "value_count" => [
                        "field" => "context.proxyProvider",
                    ],
                ],
            ];
            $params['body']['aggs']['byInterval'] = $byInterval;
        } else {
            //            $params['body']['aggs'] = ['provider' => $providerHead, 'proxy' => $proxyHead];
            $params['body']['aggs'] = [
                'provider' => $providerHead,
                'providerCnt' => [
                    "value_count" => [
                        "field" => "context.proxyProvider",
                    ],
                ],
            ];
        }

        return $params;
    }

    private function getParamsProviderMixedProxy(string $query, string $executor, int $startDate, int $endDate): array
    {
        $params = $this->getDefaultParams($query, $executor, $startDate, $endDate);

        $params['size'] = 10000;

        $params['body']['query']['bool']['must'][] = [
            'script' => [
                'script' => [
                    "lang" => 'painless',
                    "inline" => "doc['context.proxyAddressOnInit']!=null && doc['context.proxyAddress']!=null && doc['context.proxyAddressOnInit'] != doc['context.proxyAddress']",
                ],
            ],
        ];
        $params['body']['_source'] = false;
        $params['body']['script_fields'] = [
            "proxyChangeHelped" => [
                "script" => "params['_source']['context']['errorCode']!=6 && params['_source']['context']['proxyAddressOnInit']!=params['_source']['context']['proxyAddress']",
            ],
        ];
        $params['body']['fields'] = [
            "extra.provider",
            "extra.requestId",
            "context.proxyProviderOnInit",
            "context.proxyProvider",
            "context.proxyRegionOnInit",
            "context.proxyRegion",
            "context.proxyAddressOnInit",
            "context.proxyAddress",
            "context.retryNumber",
            "context.errorCode",
        ];
        unset($params['body']['aggs']);

        return $params;
    }
}
