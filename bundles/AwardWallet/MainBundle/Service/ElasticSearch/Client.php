<?php

namespace AwardWallet\MainBundle\Service\ElasticSearch;

use AwardWallet\Strings\Strings;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Psr\Log\LoggerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class Client
{
    private \Elasticsearch\Client $client;

    private LoggerInterface $logger;

    private \Memcached $memcached;

    public function __construct(
        string $elasticSearchHost,
        LoggerInterface $logger,
        \Memcached $memcached
    ) {
        $this->client = ClientBuilder::create()
            ->setHosts([$elasticSearchHost])
            ->build();
        $this->logger = $logger;
        $this->memcached = $memcached;
    }

    public function aggregate(string $query, array $aggs, int $startTime, int $endTime): array
    {
        $key = "es_agg2_" . sha1($query . json_encode($aggs) . $startTime . $endTime);
        $response = $this->memcached->get($key);

        if ($response !== false) {
            return $response;
        }

        $response = $this->client->search([
            "index" => "logstash-*",
            "size" => 0,
            "body" => [
                "aggs" => $aggs,
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
                            $this->dateRange($startTime, $endTime),
                        ],
                    ],
                ],
            ],
        ]);

        if (!isset($response['aggregations'])) {
            throw new \Exception("failed to aggregate, response: " . Strings::cutInMiddle(json_encode($response), 256));
        }

        $this->memcached->set($key, $response['aggregations'], 86400);

        return $response['aggregations'];
    }

    /**
     * @return iterable<array>
     */
    public function query(string $query, \DateTimeInterface $startTime, \DateTimeInterface $endTime, int $responsePageSize, array $extraRequestOptions = [], ?int $maxResults = null): iterable
    {
        $query = \preg_replace('/[\r\n]/', ' ', $query);

        $request = [
            "index" => "logstash-*",
            "size" => $responsePageSize,
            'scroll' => '5m',
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
                            $this->dateRange($startTime->getTimestamp(), $endTime->getTimestamp()),
                        ],
                    ],
                ],
            ],
        ];

        if (count($extraRequestOptions) > 0) {
            $request['body'] = array_merge($request['body'], $extraRequestOptions);
        }

        $response = $this->client->search($request);

        yield from it($this->scroll($response, $query, $responsePageSize, $maxResults))
            ->map(static fn (array $doc) => $doc['_source']);
    }

    private function dateRange(int $from, int $before)
    {
        return [
            "range" => [
                "@timestamp" => [
                    "gte" => $from * 1000,
                    "lt" => $before * 1000,
                    "format" => "epoch_millis",
                ],
            ],
        ];
    }

    private function scroll(array $response, string $query, int $responsePageSize, ?int $maxResults): iterable
    {
        $result = $response['hits']['hits'] ?? [];
        $resultCount = \count($result);
        $page = 1;
        $this->scrollIds[] = $response['_scroll_id'];

        $logHelper = function (array $hitsList) use (&$page, $query, &$resultCount) {
            [$minDate, $maxDate] =
                it($hitsList)
                    ->map(static fn (array $hit) => $hit['_source']['@timestamp'])
                    ->bounds();

            $this->logger->debug(
                "query: " . \substr($query, 0, 100) . "..." .
                ", loaded: {$resultCount} documents" .
                ", page: {$page}" .
                ", from: {$minDate}" .
                ", to: {$maxDate}"
            );
        };

        $logHelper($result);

        yield from $result;

        while (\count($result) >= $responsePageSize && ($maxResults === null || $resultCount < $maxResults)) {
            $response = $this->client->scroll([
                'scroll_id' => $response['_scroll_id'],
                'scroll' => '5m',
            ]);
            $result = $response['hits']['hits'] ?? [];
            $resultCount += \count($result);
            $page++;
            $logHelper($result);

            yield from $result;
        }

        try {
            $this->client->clearScroll(['scroll_id' => \array_pop($this->scrollIds)]);
        } catch (Missing404Exception $exception) {
            $this->logger->warning("failed to clear scroll: " . $exception->getMessage());
        }
    }
}
