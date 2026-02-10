<?php

namespace AwardWallet\MainBundle\Service;

use Doctrine\DBAL\Connection;

class WatchdogKillListProvider
{
    private \Memcached $memcached;

    private string $esAddress;

    private Connection $conn;

    private \HttpDriverInterface $httpDriver;

    /**
     * WatchdogKillListProvider constructor.
     */
    public function __construct(\Memcached $memcached, string $elasticSearchHost, Connection $connection, \HttpDriverInterface $curlDriver)
    {
        $this->memcached = $memcached;
        $this->esAddress = $elasticSearchHost;
        $this->conn = $connection;
        $this->httpDriver = $curlDriver;
    }

    public function search(?int $limit = null, ?int $providerId = null)
    {
        $query = '"Process killed by watchdog" OR "received timeout, will not save"';

        $request = $this->getPostData($query);
        $key = 'kill_list_provider_v5';

        $newData = $this->memcached->get($key);

        if ($newData === false) {
            $data = $this->httpDriver->request(new \HttpDriverRequest("http://{$this->esAddress}:9200/_search",
                'POST', $request, ["Content-Type" => "application/json"], 30))->body;
            $response = json_decode($data, true);

            $accounts = [];
            $killDates = [];
            $killReasons = [];

            if (isset($response['hits']['hits'])) {
                foreach ($response['hits']['hits'] as $hit) {
                    if (isset($hit['_source']['context']['userData'])) {
                        $userData = json_decode($hit['_source']['context']['userData'], true);

                        if (!isset($userData['accountId'])) {
                            continue;
                        }

                        if (preg_match('/\d+/', $userData['accountId'])) {
                            $accounts[] = (int) $userData['accountId'];
                        }

                        if (
                            preg_match('/(\d{4}\-\d{1,2}\-\d{1,2}T\d{1,2}:\d{1,2}:\d{1,2})/',
                                $hit['_source']['@timestamp'], $m)
                            && (!isset($killDates[(int) $userData['accountId']]) || $killDates[(int) $userData['accountId']] < strtotime($m[1]))
                        ) {
                            $killDates[(int) $userData['accountId']] = strtotime($m[1]);
                        }
                        $killReasons[(int) $userData['accountId']] = $hit['_source']['message'];
                    }
                }
            }

            $accounts = array_unique($accounts);

            $newData = [];

            if (!empty($accounts)) {
                $requestSql = "SELECT p.Code, a.ProviderID, a.UpdateDate, a.AccountID, a.UserID, a.Login, a.Login2, a.ErrorCode, a.ErrorMessage, a.DebugInfo, a.Balance, a.SavePassword, a.CheckedBy, a.Login3, a.SuccessCheckDate, a.ExpirationDate, a.ExpirationAutoSet, a.SubAccounts, a.DisableExtension, a.DisableClientPasswordAccess, a.PassChangeDate, a.CreationDate
	FROM Account a, Provider p WHERE a.ProviderID=p.ProviderID AND a.AccountID IN(?)";

                $result = $this->conn->executeQuery($requestSql, [$accounts],
                    [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY]);
                $killedAccounts = $result->fetchAll(\PDO::FETCH_ASSOC);

                foreach ($killedAccounts as $killedAccount) {
                    $killedAccount["DebugInfo"] = $killReasons[(int) $killedAccount['AccountID']];

                    if (
                        isset($killDates[(int) $killedAccount['AccountID']])
                        && $killDates[(int) $killedAccount['AccountID']] > strtotime($killedAccount['UpdateDate'])
                    ) {
                        $killedAccount['UpdateDate'] = date('Y-m-d H:i:s',
                            $killDates[(int) $killedAccount['AccountID']]);
                        $newData[$killedAccount['ProviderID']][] = $killedAccount;
                    }
                }
            }
            $this->memcached->set($key, $newData, 60);
        }

        $res = [];

        if (!empty($providerId)) {
            if (!empty($newData[$providerId])) {
                $res = array_slice($newData[$providerId], 0, $limit);
            } else {
                return [];
            }
        } else {
            foreach ($newData as $provId => $newDatum) {
                $res[$provId] = $newDatum;
            }
        }

        return $res;
    }

    private function getPostData(string $query): string
    {
        $endDate = new \DateTimeImmutable();
        $startDate = $endDate->sub(new \DateInterval('PT4H'));

        return '{
            "size": 2000,
            "sort":
            [
                {
                    "@timestamp": {
                        "order": "desc",
                        "unmapped_type": "boolean"
                    }
                }
            ],
            "query":
            {
                "bool":
                {
                    "must":
                    [
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
                                    "gte": ' . $startDate->getTimestamp() * 1000 . ',
                                    "lte": ' . $endDate->getTimestamp() * 1000 . ',
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
        }';
        /*
                return '
                {
                    "size": 2000,
                    "sort": [
                    {
                        "@timestamp": {
                        "order": "desc",
                        "unmapped_type": "boolean"
                      }
                    }
                  ],
                  "query": {
                    "filtered": {
                      "query": {
                        "query_string": {
                          "analyze_wildcard": true,
                          "query": "'. addslashes($query) .'"
                        }
                      },
                      "filter": {
                        "bool": {
                          "must": [
                            {
                              "range": {
                                "@timestamp": {
                                  "gte": '. $startDate->getTimestamp() * 1000 .',
                                  "lte": '. $endDate->getTimestamp() * 1000 .',
                                  "format": "epoch_millis"
                                }
                              }
                            }
                          ],
                          "must_not": []
                        }
                      }
                    }
                  },
                  "fields": [
                    "*",
                    "_source"
                  ],
                  "script_fields": {},
                  "fielddata_fields": [
                    "@timestamp",
                    "timestamp"
                  ]
                }';*/
    }
}
