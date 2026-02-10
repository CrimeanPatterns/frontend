<?php

namespace AwardWallet\MainBundle\Loyalty\SuccessRate;

use Elasticsearch\Client;
use Psr\Log\LoggerInterface;

class SuccessRateCalculator
{
    private LoggerInterface $logger;
    private Client $elastic;

    public function __construct(LoggerInterface $logger, Client $elastic)
    {
        $this->logger = $logger;
        $this->elastic = $elastic;
    }

    /**
     * @param array $serverFixDates array in which keys are provider codes and values are server side last fix dates
     * (in the format "YYYY-MM-DD hh:mm:ss")
     *
     * For example,
     * ```php
     * $dates = [
     *     'british' => '2025-06-20 12:10:00',
     *     'marriott' => '2025-06-19 15:06:00',
     *     'qmiles' => '2025-06-19 14:48:00',
     * ];
     *
     * $successRate = $calculator->getSuccessRate($dates);
     * ```
     * @return ProviderStats[]
     */
    public function getSuccessRate(array $serverFixDates): array
    {
        $body = json_decode(/** @lang JSON */ '{
  "aggs": {
    "3": {
      "terms": {
        "field": "context.Provider",
        "order": {
          "_count": "desc"
        },
        "size": 10000
      },
      "aggs": {
        "2": {
          "filters": {
            "filters": {
              "Errors": {
                "bool": {
                  "must": [
                    {
                      "query_string": {
                        "query": "context.ErrorCode: (6 OR 10 OR 4)",
                        "analyze_wildcard": true,
                        "time_zone": "UTC"
                      }
                    }
                  ],
                  "filter": [],
                  "should": [],
                  "must_not": []
                }
              },
              "Success": {
                "bool": {
                  "must": [
                    {
                      "query_string": {
                        "query": "NOT context.ErrorCode: (6 OR 10 OR 4)",
                        "analyze_wildcard": true,
                        "time_zone": "UTC"
                      }
                    }
                  ],
                  "filter": [],
                  "should": [],
                  "must_not": []
                }
              },
              "ErrorsXxx": {
                "bool": {
                  "must": [
                    {
                      "query_string": {
                        "query": "context.Provider: Xxx AND context.ErrorCode: (6 OR 10 OR 4) AND @timestamp: [\"2020-01-01T00:00:00.000Z\" TO \"2050-01-01T00:00:00.000Z\"]",
                        "analyze_wildcard": true,
                        "time_zone": "UTC"
                      }
                    }
                  ],
                  "filter": [],
                  "should": [],
                  "must_not": []
                }
              },
              "SuccessXxx": {
                "bool": {
                  "must": [
                    {
                      "query_string": {
                        "query": "context.Provider: Xxx AND @timestamp: [\"2020-01-01T00:00:00.000Z\" TO \"2050-01-01T00:00:00.000Z\"] AND NOT context.ErrorCode: (6 OR 10 OR 4)",
                        "analyze_wildcard": true,
                        "time_zone": "UTC"
                      }
                    }
                  ],
                  "filter": [],
                  "should": [],
                  "must_not": []
                }
              }
            }
          }
        }
      }
    }
  },
  "size": 0,
  "stored_fields": [
    "*"
  ],
  "_source": {
    "excludes": []
  },
  "query": {
    "bool": {
      "must": [
        {
          "query_string": {
            "query": "context.message: \"Partner statistic\" AND app: loyalty AND context.CheckedByClientBrowser: false",
            "analyze_wildcard": true,
            "time_zone": "UTC"
          }
        }
      ],
      "filter": [
        {
          "range": {
            "@timestamp": {
              "gte": "' . date('Y-m-d\TH:i:s', strtotime("-1 day")) . '.000Z",
              "lte": "' . date('Y-m-d\TH:i:s') . '.000Z",
              "format": "strict_date_optional_time"
            }
          }
        }
      ],
      "should": [],
      "must_not": []
    }
  }
}', true);

        foreach ($serverFixDates as $providerCode => $serverFixDate) {
            $body['aggs']['3']['aggs']['2']['filters']['filters']["Success{$providerCode}"] = $body['aggs']['3']['aggs']['2']['filters']['filters']["SuccessXxx"];
            $body['aggs']['3']['aggs']['2']['filters']['filters']["Success{$providerCode}"]["bool"]["must"][0]["query_string"]["query"] = str_replace(
                'Xxx',
                $providerCode,
                str_replace(
                    '2020-01-01T00:00:00.000Z',
                    date('Y-m-d\TH:i:s.000\Z', strtotime($serverFixDate)),
                    $body['aggs']['3']['aggs']['2']['filters']['filters']["Success{$providerCode}"]["bool"]["must"][0]["query_string"]["query"]
                )
            );

            $body['aggs']['3']['aggs']['2']['filters']['filters']["Errors{$providerCode}"] = $body['aggs']['3']['aggs']['2']['filters']['filters']["ErrorsXxx"];
            $body['aggs']['3']['aggs']['2']['filters']['filters']["Errors{$providerCode}"]["bool"]["must"][0]["query_string"]["query"] = str_replace(
                'Xxx',
                $providerCode,
                str_replace(
                    '2020-01-01T00:00:00.000Z',
                    date('Y-m-d\TH:i:s.000\Z', strtotime($serverFixDate)),
                    $body['aggs']['3']['aggs']['2']['filters']['filters']["Errors{$providerCode}"]["bool"]["must"][0]["query_string"]["query"]
                )
            );
        }

        unset($body['aggs']['3']['aggs']['2']['filters']['filters']["SuccessXxx"]);
        unset($body['aggs']['3']['aggs']['2']['filters']['filters']["ErrorsXxx"]);

        $request = [
            "index" => "logstash-*",
            "size" => 0,
            "body" => $body,
            "timeout" => "5s",
        ];

        $response = $this->elastic->search($request);

        return $this->convertResponse($response);
    }

    private function convertResponse(array $response): array
    {
        $result = [];

        foreach ($response["aggregations"]["3"]["buckets"] as $value) {
            // do we have specific (limited by fix server fix date) aggregation for this provider?
            if (isset($value['2']['buckets']['Errors' . $value['key']])) {
                $result[$value['key']] = new ProviderStats($value['key'], $value['2']['buckets']['Errors' . $value['key']]['doc_count'], $value['2']['buckets']['Success' . $value['key']]['doc_count']);

                continue;
            }

            $result[$value['key']] = new ProviderStats($value['key'], $value['2']['buckets']['Errors']['doc_count'], $value['2']['buckets']['Success']['doc_count']);
        }

        return $result;
    }
}
