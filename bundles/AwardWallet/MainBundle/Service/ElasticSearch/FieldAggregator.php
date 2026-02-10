<?php

namespace AwardWallet\MainBundle\Service\ElasticSearch;

use Psr\Log\LoggerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class FieldAggregator
{
    private Client $client;

    private LoggerInterface $logger;

    public function __construct(Client $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    public function aggregate(string $query, string $fieldName, int $startTime, int $endTime)
    {
        $aggs = $this->client->aggregate(
            $query,
            [
                "agg0" => [
                    "terms" => [
                        "field" => $fieldName,
                        "size" => 10000000,
                        "order" => [
                            "_count" => "desc",
                        ],
                    ],
                ],
            ],
            $startTime,
            $endTime
        );

        $result = it($aggs["agg0"]["buckets"])
            ->flatMap(function (array $value) {
                return [$value['key'] => $value['doc_count']];
            })
            ->toArrayWithKeys()
        ;

        $this->logger->info("got " . count($result) . " rows from query: $query aggregated by $fieldName");

        return $result;
    }
}
