<?php

namespace AwardWallet\MainBundle\Loyalty\Stats;

use Aws\S3\S3Client;
use Elasticsearch\ClientBuilder;
use Psr\Log\LoggerInterface;
use Twig\Environment;

class Calculator
{
    public const REPORT_NAME = 'loyalty-billing-report.html';

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var \Elasticsearch\Client
     */
    private $client;
    /**
     * @var Environment
     */
    private $twig;
    /**
     * @var S3Client
     */
    private $s3client;

    public function __construct(string $elasticSearchHost, LoggerInterface $logger, Environment $twig, S3Client $s3client)
    {
        $this->logger = $logger;
        $this->client = ClientBuilder::create()
            ->setHosts([$elasticSearchHost])
            ->build();
        $this->twig = $twig;
        $this->s3client = $s3client;
    }

    public function calc(): void
    {
        $thisMonth = [
            'start' => new \DateTime('first day of this month 00:00'),
            'end' => new \DateTime('first day of next month 00:00'),
            'title' => 'This month',
        ];
        $lastMonth = [
            'start' => new \DateTime('first day of last month 00:00'),
            'end' => new \DateTime('first day of this month 00:00'),
            'title' => 'Last month',
        ];

        $data = [
            'tripit' => [
                $this->calcTripitBlock($thisMonth),
                $this->calcTripitBlock($lastMonth),
            ],
        ];

        $this->s3client->putObject([
            'Bucket' => 'aw-frontend-data',
            'Key' => self::REPORT_NAME,
            'Body' => $this->twig->render('@AwardWalletMain/Manager/LoyaltyAdmin/billingReportContent.html.twig', $data),
            'Expires' => new \DateTime('+14 days'),
        ]);
    }

    private function calcTripitBlock(array $dateRange): array
    {
        return array_merge(
            [
                'title' => $dateRange['title'],
                'startDate' => $dateRange['start']->format("Y-m-d"),
                'endDate' => $dateRange['end']->format("Y-m-d"),
            ],
            $this->calcTripItData($dateRange['start'], $dateRange['end'])
        );
    }

    private function calcTripItData(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        $users = $this->calcTripItUsers($start, $end);
        $userPrices = array_map(function (array $checksByDays) {
            return min(0.30, count($checksByDays) * 0.03);
        }, $users);

        $total = array_sum($userPrices);

        if ($total < 10000) {
            $total = '$10,000 (actual $' . number_format($total, 2, '.', ',') . ')';
        } else {
            $total = '$' . number_format($total, 2, '.', ',');
        }

        return [
            'users' => count($users),
            'total' => $total,
            'max user checks per day' => count($users) ? max(array_map(function (array $checksByDays) { return max($checksByDays); }, $users)) : 0,
            'max user checks per month' => count($users) ? max(array_map(function (array $checksByDays) { return array_sum($checksByDays); }, $users)) : 0,
            'average user checks per month' => count($users) ? round(array_sum(array_map(function (array $checksByDays) { return array_sum($checksByDays); }, $users)) / count($users), 1) : null,
        ];
    }

    private function query(string $query, int $startTime, int $endTime): \Iterator
    {
        $response = $this->client->search([
            "index" => "loyalty-stats-*",
            "size" => 5000,
            'scroll' => '90s',
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
                            $this->dateRange($startTime, $endTime),
                        ],
                    ],
                ],
            ],
        ]);

        return $this->scroll($response);
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

    private function scroll(array $response): \Iterator
    {
        $result = $response['hits']['hits'];

        while (isset($response['hits']['hits']) && count($response['hits']['hits']) > 0) {
            foreach ($response['hits']['hits'] as $hit) {
                yield $hit['_source'];
            }
            $response = $this->client->scroll([
                'scroll_id' => $response['_scroll_id'],
                'scroll' => '30s',
            ]);
            $this->logger->info("scrolled " . count($response['hits']['hits']) . " documents");
        }

        return $result;
    }

    private function calcTripItUsers(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        /**
         * {.
         * }.
         */
        $cacheFile = sys_get_temp_dir() . '/last-loyaly-report-data-' . $start->format("Y-m-d") . "_" . $end->format("Y-m-d") . '.json';

        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 36000) {
            $this->logger->info("loading from cache file {$cacheFile}");

            return json_decode(file_get_contents($cacheFile), true);
        }

        $users = [];
        $q = $this->query('message: "Partner statistic" AND Partner: "tripitprod"', $start->getTimestamp(), $end->getTimestamp());

        foreach ($q as $hit) {
            if (!isset($users[$hit['UserID']])) {
                $users[$hit['UserID']] = [];
            }
            $day = date("Y-m-d", strtotime($hit['RequestDateTime']));

            if (!isset($users[$hit['UserID']][$day])) {
                $users[$hit['UserID']][$day] = 1;
            } else {
                $users[$hit['UserID']][$day]++;
            }
        }
        file_put_contents($cacheFile, json_encode($users, JSON_PRETTY_PRINT));

        return $users;
    }
}
