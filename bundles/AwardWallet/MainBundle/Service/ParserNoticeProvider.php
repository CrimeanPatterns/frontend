<?php

namespace AwardWallet\MainBundle\Service;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class ParserNoticeProvider
{
    private \Memcached $memcached;

    private string $esAddress;

    private Connection $conn;

    private \HttpDriverInterface $httpDriver;

    private LoggerInterface $logger;

    private $providers = [];

    /**
     * ParserNoticeProvider constructor.
     */
    public function __construct(
        \Memcached $memcached,
        string $elasticSearchHost,
        Connection $connection,
        \HttpDriverInterface $curlDriver,
        LoggerInterface $statLogger
    ) {
        $this->memcached = $memcached;
        $this->esAddress = $elasticSearchHost;
        $this->conn = $connection;
        $this->httpDriver = $curlDriver;
        $this->logger = $statLogger;
    }

    /**
     * full day.
     */
    public function search(?int $startDate = null, ?int $endDate = null, ?string $providerCode = null)
    {
        if (!isset($startDate, $endDate)) {
            if (isset($endDate)) {// day $endDate
                $startDate = strtotime(date("Y-m-d", $endDate));
            }

            if (!isset($startDate)) {// if !$endDate && !$startDate then day today
                $startDate = strtotime(date("Y-m-d"));
            }
            $endDate = $startDate;
        }

        if (date("Y-m-d", $startDate) === date("Y-m-d", $endDate)) {
            $startDate = strtotime(date("Y-m-d", $startDate));
            $endDate = strtotime("+1 day", $startDate);
        } else {
            $startDate = strtotime(date("Y-m-d", $startDate));
            $endDate = strtotime("+1 day", strtotime(date("Y-m-d", $endDate)));
        }

        $key = $providerCode . date("Y-m-d", $startDate) . date("Y-m-d", $endDate);

        $key = 'parser_notice_' . md5($key);

        $newData = $this->memcached->get($key);
        $fromCache = true;

        if ($newData !== false) {
            $newData = [];

            // for Debug
            //            $this->logger->info('ItineraryCheckError. get From cache: [' . date("Y-m-d ",
            //                    $startDate) . ', ' . date("Y-m-d", $endDate) . ')');
            //            $this->memcached->delete($key);
            return [$fromCache, $newData];
        }

        $this->providers = [];
        $loyaltyErrors = $this->getLoyaltyNotices($startDate, $endDate, $providerCode);
        $extensionErrors = $this->getExtensionNotices($startDate, $endDate, $providerCode);

        $newData = [];
        $fromCache = false;

        if (!empty($this->providers)) {
            $requestSql = "SELECT p.Code, p.ProviderID FROM Provider p WHERE p.Code IN (?)";

            $result = $this->conn->executeQuery($requestSql, [$this->providers],
                [\Doctrine\DBAL\Connection::PARAM_STR_ARRAY])->fetchAll(\PDO::FETCH_ASSOC);
            $providers = [];

            foreach ($result as $row) {
                $providers[$row['Code']] = $row['ProviderID'];
            }

            foreach ($loyaltyErrors as $error) {
                if (isset($providers[$error['ProviderCode']])) {
                    $newData[] = [
                        'DetectionDate' => $error['DetectionDate'],
                        'ProviderID' => $providers[$error['ProviderCode']],
                        'AccountId' => $error['AccountId'],
                        'RequestId' => $error['RequestId'],
                        'ErrorMessage' => $error['ErrorMessage'],
                        'Partner' => $error['Partner'],
                    ];
                }
            }

            foreach ($extensionErrors as $error) {
                if (isset($providers[$error['ProviderCode']])) {
                    $newData[] = [
                        'DetectionDate' => $error['DetectionDate'],
                        'ProviderID' => $providers[$error['ProviderCode']],
                        'AccountId' => $error['AccountId'],
                        'ErrorMessage' => $error['ErrorMessage'],
                        'Partner' => $error['Partner'],
                    ];
                }
            }
        }
        $this->memcached->set($key, $newData, 60 * 5);

        return [$fromCache, $newData];
    }

    private function getLoyaltyNotices(int $startDate, int $endDate, ?string $providerCode): array
    {
        $errors = [];
        $query = 'extra.app: loyalty AND (context.component: parser OR context.component: ItineraryHelper) AND NOT (extra.worker_executor: reward-availability) AND level: NOTICE' . (!isset($providerCode) ? '' : ' AND extra.provider: ' . $providerCode);
        $request = $this->getPostData($query, $startDate, $endDate);

        $this->logger->info('ItineraryCheckError. query to kibana: [' . date("Y-m-d ",
            $startDate) . ', ' . date("Y-m-d", $endDate) . ')');
        $data = $this->httpDriver->request(new \HttpDriverRequest("http://{$this->esAddress}:9200/_search",
            'POST', $request, ["Content-Type" => "application/json"], 120))->body;
        $response = json_decode($data, true);

        if (!isset($response['hits']['hits'])) {
            $this->logger->info('ItineraryCheckError. empty response or other format: [' . date("Y-m-d ",
                $startDate) . ', ' . date("Y-m-d", $endDate) . ')');

            return $errors;
        }

        foreach ($response['hits']['hits'] as $hit) {
            $account = null;

            if (($hit['_source']['extra']['partner'] !== 'awardwallet'
                    && (!isset($hit['_source']['extra']['requestId']) || empty($hit['_source']['extra']['requestId']))
            )
            || !isset($hit['_source']['extra']['provider'])
            ) {
                continue;
            }
            $provider = $hit['_source']['extra']['provider'];

            if (!in_array($provider, $this->providers)) {
                $this->providers[] = $provider;
            }

            if (isset($hit['_source']['extra']['userData'])) {
                $userData = json_decode($hit['_source']['extra']['userData'], true);

                if (isset($userData['accountId']) && preg_match('/\d+/', $userData['accountId'])) {
                    $account = (int) $userData['accountId'];
                }
            }

            if (preg_match('/(\d{4}\-\d{1,2}\-\d{1,2}T\d{1,2}:\d{1,2}:\d{1,2})/', $hit['_source']['@timestamp'], $m)
            ) {
                $errors[] = [
                    'DetectionDate' => strtotime($m[1]),
                    'ProviderCode' => $provider,
                    'AccountId' => $account,
                    'RequestId' => $hit['_source']['extra']['requestId'],
                    'ErrorMessage' => $hit['_source']['message'],
                    'Partner' => $hit['_source']['extra']['partner'],
                ];
            }
        }

        return $errors;
    }

    private function getExtensionNotices(int $startDate, int $endDate, ?string $providerCode): array
    {
        $errors = [];
        $query = 'extra.app: frontend  AND level: NOTICE AND context.component:parser AND NOT message:"itineraries: empty data"' . (!isset($providerCode) ? '' : ' AND extra.providerCode: ' . $providerCode);
        $request = $this->getPostData($query, $startDate, $endDate);

        $this->logger->info('ItineraryCheckError. query to kibana: [' . date("Y-m-d ",
            $startDate) . ', ' . date("Y-m-d", $endDate) . ')');
        $data = $this->httpDriver->request(new \HttpDriverRequest("http://{$this->esAddress}:9200/_search",
            'POST', $request, ["Content-Type" => "application/json"], 120))->body;
        $response = json_decode($data, true);

        if (!isset($response['hits']['hits']) || empty($response['hits']['hits'])) {
            $this->logger->info('ItineraryCheckError. empty response or other format: [' . date("Y-m-d ",
                $startDate) . ', ' . date("Y-m-d", $endDate) . ')');

            return [];
        }

        foreach ($response['hits']['hits'] as $hit) {
            $account = null;

            if (!isset($hit['_source']['extra']['accountId'])
                || !isset($hit['_source']['extra']['providerCode'])
            ) {
                continue;
            }
            $provider = $hit['_source']['extra']['providerCode'];

            if (!in_array($provider, $this->providers)) {
                $this->providers[] = $provider;
            }
            $account = (int) $hit['_source']['extra']['accountId'];

            if (preg_match('/(\d{4}\-\d{1,2}\-\d{1,2}T\d{1,2}:\d{1,2}:\d{1,2})/', $hit['_source']['@timestamp'], $m)
            ) {
                $errors[] = [
                    'DetectionDate' => strtotime($m[1]),
                    'ProviderCode' => $provider,
                    'AccountId' => $account,
                    'ErrorMessage' => $hit['_source']['message'],
                    'Partner' => 'awardwallet',
                ];
            }
        }

        return $errors;
    }

    private function getPostData(string $query, int $startDate, int $endDate): string
    {
        $start = $startDate * 1000;
        $end = $endDate * 1000 - 1;

        return '
        {
          "size": 10000,
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
                    "analyze_wildcard": true,
                    "default_field": "*"
                  }
                },
                {
                  "range": {
                    "@timestamp": {
                      "gte": ' . $start . ',
                      "lte": ' . $end . ',
                      "format": "epoch_millis"
                    }
                  }
                }
            ],
              "filter": [],
              "should": [],
              "must_not": []
            }
          }
        }            
        ';
    }
}
