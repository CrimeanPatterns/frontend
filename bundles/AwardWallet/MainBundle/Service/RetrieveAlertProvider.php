<?php

namespace AwardWallet\MainBundle\Service;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class RetrieveAlertProvider
{
    private \Memcached $memcached;

    private string $esAddress;

    private Connection $conn;

    private \HttpDriverInterface $httpDriver;

    private LoggerInterface $logger;

    /**
     * RetrieveAlertProvider constructor.
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
    public function search(?int $startDate = null, ?string $providerCode = null)
    {
        if (!isset($startDate)) {// if !$startDate then yesterday
            $startDate = strtotime("-1 day", strtotime(date("Y-m-d")));
        }
        $startDate = strtotime(date("Y-m-d", $startDate));

        $key = $providerCode . date("Y-m-d", $startDate);

        $query = 'extra.app: "loyalty" AND extra.worker_executor: "confirmation" AND message:"Failed retrieve"' . (!isset($providerCode) ? '' : ' AND extra.provider: "' . $providerCode . '"');
        $request = $this->getPostData($query, $startDate);

        $key = 'retrive_alert_' . md5($key);

        $data = $this->memcached->get($key);

        if ($data !== false) {
            // for Debug
            //            $this->memcached->delete($key);
            // empty array, not data - so that there is no duplication
            $this->logger->info('RetrieveAlertProvider. data for ' . date("Y-m-d ", $startDate) . ' already loaded');

            return [];
        }
        $data = [];

        $this->logger->info('RetrieveAlertProvider. query to kibana: ' . date("Y-m-d ", $startDate));
        $dataResponse = $this->httpDriver->request(new \HttpDriverRequest("http://{$this->esAddress}:9200/_search",
            'POST', $request, ["Content-Type" => "application/json"], 120))->body;
        $response = json_decode($dataResponse, true);

        $errors = [];
        $providers = [];

        if (!isset($response['hits']['hits']) || empty($response['hits']['hits'])) {
            $this->logger->info('RetrieveAlertProvider. empty response or other format: ' . date("Y-m-d ", $startDate));

            return $data;
        }
        // for check request.
        $hit = array_values($response['hits']['hits'])[0];

        if (!isset($hit['_source']['extra']['provider'], $hit['_source']['extra']['partner'], $hit['_source']['extra']['requestId'])) {
            $this->logger->notice('RetrieveAlertProvider. check request and response format ');

            return [];
        }

        foreach ($response['hits']['hits'] as $hit) {
            $account = null;
            $confNo = null;

            if (isset($hit['_source']['context']['Fields'])) {
                $fields = json_decode($hit['_source']['context']['Fields'], true);

                if (isset($fields['ConfNo'])) {
                    $confNo = $fields['ConfNo'];
                }
            }
            $provider = $hit['_source']['extra']['provider'];

            if (!in_array($provider, $providers)) {
                $providers[] = $provider;
            }

            if (preg_match('/(\d{4}\-\d{1,2}\-\d{1,2}T\d{1,2}:\d{1,2}:\d{1,2})/',
                $hit['_source']['@timestamp'], $m)
            ) {
                $errors[] = [
                    'DetectionDate' => strtotime($m[1]),
                    'ProviderCode' => $provider,
                    'RequestId' => $hit['_source']['extra']['requestId'],
                    'ConfirmationNumber' => $confNo,
                    'Partner' => $hit['_source']['extra']['partner'],
                ];
            }
        }

        if (!empty($errors)) {
            $requestSql = "SELECT p.Code, p.ProviderID FROM Provider p WHERE p.Code IN (?)";

            $result = $this->conn->executeQuery($requestSql, [$providers],
                [\Doctrine\DBAL\Connection::PARAM_STR_ARRAY])->fetchAll(\PDO::FETCH_ASSOC);
            $providers = [];

            foreach ($result as $row) {
                $providers[$row['Code']] = $row['ProviderID'];
            }

            foreach ($errors as $error) {
                if (isset($providers[$error['ProviderCode']])) {
                    $data[] = [
                        'DetectionDate' => $error['DetectionDate'],
                        'ProviderID' => $providers[$error['ProviderCode']],
                        'RequestId' => $error['RequestId'],
                        'ConfirmationNumber' => $error['ConfirmationNumber'],
                        'Partner' => $error['Partner'],
                    ];
                }
            }
        }
        $this->logger->notice('RetrieveAlertProvider. ' . count($data) . ' row(s) found');
        $this->memcached->set($key, $data, 60 * 60);

        return $data;
    }

    private function getPostData(string $query, int $startDate): string
    {
        $start = $startDate * 1000;
        $end = strtotime("+1 day", $startDate) * 1000 - 1;

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
