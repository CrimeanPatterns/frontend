<?php

namespace AwardWallet\MainBundle\Security;

use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\ElasticsearchException;
use Psr\Log\LoggerInterface;

class BotIpDetector
{
    private const CACHE_KEY = 'detected_bot_ips';
    private const LOCK_CACHE_KEY = 'detected_bot_ips_lock';
    private const SOFT_EXPIRATION_TIME = 300;
    private const HARD_EXPIRATION_TIME = 600;

    /**
     * @var string
     */
    private $elasticSearchHost;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var \Memcached
     */
    private $memcached;

    public function __construct(
        string $elasticSearchHost,
        LoggerInterface $logger,
        \Memcached $memcached
    ) {
        $this->elasticSearchHost = $elasticSearchHost;
        $this->logger = $logger;
        $this->memcached = $memcached;
    }

    public function getBotIps(): array
    {
        /** @var BotIpDetectorState|bool $cache */
        $cache = $this->memcached->get(self::CACHE_KEY);

        if (
            $cache === false
            || ((time() - $cache->getCreatedAt()) > self::SOFT_EXPIRATION_TIME && $this->memcached->add(self::LOCK_CACHE_KEY,
                1, 60))
        ) {
            $ips = $this->extractBotsIpsFromLogs();
            $cache = new BotIpDetectorState(time(), $ips);
            $this->memcached->set(self::CACHE_KEY, $cache, self::HARD_EXPIRATION_TIME);
            $this->memcached->delete(self::LOCK_CACHE_KEY);
        }

        return $cache->getData();
    }

    private function extractBotsIpsFromLogs(): array
    {
        $client = ClientBuilder::create()
            ->setHosts([$this->elasticSearchHost])
            ->build();

        $endTime = time();
        $startTime = $endTime - 86400 * 2;
        $this->logger->info("calculating login requests for last 2 days");

        try {
            $response = $client->search([
                "index" => "logstash-*",
                "size" => 500,
                "client" => [
                    "timeout" => '10',
                    "connect_timeout" => '10',
                ],
                "body" => [
                    'aggs' => [
                        2 => [
                            'terms' => [
                                'field' => 'ip',
                                'size' => 50000,
                                'order' => [
                                    '_count' => 'desc',
                                ],
                            ],
                        ],
                    ],
                    'size' => 0,
                    'query' => [
                        'bool' => [
                            'must' => [
                                [
                                    'query_string' => [
                                        'query' => 'first_line_request:"POST /login_check"',
                                        'analyze_wildcard' => true,
                                        'default_field' => '*',
                                    ],
                                ],
                                $this->dateRange($startTime, $endTime),
                            ],
                        ],
                    ],
                ],
            ]);
        } catch (ElasticsearchException $exception) {
            $this->logger->error("failed to contact elasticsearch: " . $exception->getMessage(),
                ["exception" => $exception]);

            return [];
        }

        $rows = $response['aggregations'][2]['buckets'];
        $rows = array_filter($rows, function (array $row) {
            return $row['doc_count'] > 30;
        });
        $this->logger->info("login requests for last 2 days: " . count($rows));

        return array_map(function (array $row) {
            return $row['key'];
        }, $rows);
    }

    private function dateRange(int $from, int $before)
    {
        return [
            "range" => [
                "@timestamp" => [
                    "gte" => $from * 1000,
                    "lte" => $before * 1000,
                    "format" => "epoch_millis",
                ],
            ],
        ];
    }
}
