<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use Psr\Log\LoggerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iter\average;
use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class ResponseTimeMonitor
{
    public const BUCKETS_IN_DAY = 24;

    private string $esAddress;

    private \HttpDriverInterface $httpDriver;

    private Mailer $mailer;

    private ?string $to;

    private LoggerInterface $logger;

    public function __construct(
        string $elasticSearchHost,
        \HttpDriverInterface $curlDriver,
        Mailer $mailer,
        ?string $mailerDeliveryAddress,
        LoggerInterface $logger
    ) {
        $this->esAddress = $elasticSearchHost;
        $this->httpDriver = $curlDriver;
        $this->mailer = $mailer;
        $this->to = $mailerDeliveryAddress;
        $this->logger = $logger;
    }

    public function search(string $route, int $length, ?string $routePath)
    {
        $request = $this->getPostData($route, $length);
        $data = $this->httpDriver->request(new \HttpDriverRequest("http://{$this->esAddress}:9200/_search", 'POST', $request, ["Content-Type" => "application/json"], 120));

        if (!empty($data->errorMessage) && false !== stripos($data->errorMessage, 'Operation timed out')) {
            return 'Operation timed out';
        }
        $data = $data->body;
        $response = json_decode($data, true);

        $summary = 0.0;
        $lastBucketAvgTime = 0;
        $buckets = it($response['aggregations'][2]['buckets'] ?? [])
            ->collect()
            ->reverse()
            ->propertyPath('[1][value]')
            ->chunkWithKeys(self::BUCKETS_IN_DAY)
            ->map(function (array $day) { return average($day); })
            ->take($length)
            ->toArray();

        if (0 < count($buckets)) {
            $lastBucketAvgTime = round($buckets[0], 2);
            $summary = round(array_sum($buckets), 2);
        }

        $countBuckets = count($buckets);

        $send = false;

        if (!empty($summary) && !empty($lastBucketAvgTime)) {
            $avgFor2Weeks = $summary / $length;
            $threshold = round($avgFor2Weeks * 2, 2);

            if ($threshold <= $lastBucketAvgTime) {
                $send = true;
            } else {
                $this->logger->info("{$route} - alert was not sent for - {$routePath}: threshold - {$threshold}; last bucket avg time - {$lastBucketAvgTime}; summary - {$summary}; count buckets - {$countBuckets}");
            }
        }

        $routeForLink = urlencode("'context.route:{$route}'");

        if ($send) {
            $linkOnKibana = "https://kibana.awardwallet.com/app/kibana#/visualize/create?type=histogram&indexPattern=f7bcf3e0-1a67-11e9-8067-9bee5e3ddf43&_g=(refreshInterval:(pause:!t,value:0),time:(from:now-{$length}d,mode:relative,to:now))&_a=(filters:!(),linked:!f,query:(language:lucene,query:{$routeForLink}),uiState:(vis:(legendOpen:!t)),vis:(aggs:!((enabled:!t,id:'1',params:(field:context.php_time),schema:metric,type:avg),(enabled:!t,id:'2',params:(customInterval:'2h',drop_partials:!f,extended_bounds:(),field:'@timestamp',interval:h,min_doc_count:1,timeRange:(from:now-{$length}d,mode:relative,to:now),time_zone:UTC,useNormalizedEsInterval:!t),schema:segment,type:date_histogram)),params:(addLegend:!t,addTimeMarker:!f,addTooltip:!t,categoryAxes:!((id:CategoryAxis-1,labels:(show:!t,truncate:100),position:bottom,scale:(type:linear),show:!t,style:(),title:(),type:category)),grid:(categoryLines:!f,style:(color:%23eee)),legendPosition:right,seriesParams:!((data:(id:'1',label:'Average%20context.php_time'),drawLinesBetweenPoints:!t,mode:stacked,show:true,showCircles:!t,type:histogram,valueAxis:ValueAxis-1)),times:!(),type:histogram,valueAxes:!((id:ValueAxis-1,labels:(filter:!f,rotate:0,show:!t,truncate:100),name:LeftAxis-1,position:left,scale:(mode:normal,type:linear),show:!t,style:(),title:(text:'Average%20context.php_time'),type:value))),title:'New%20Visualization',type:histogram))";
            $msg = $this->mailer->getMessage(null, 'error@awardwallet.com', 'Alert! Response time monitor');
            $msg->setBody("{$route} - {$lastBucketAvgTime}ms for last bucket. Threshold: {$threshold}ms. Path: {$routePath}; summary - {$summary}; count buckets - {$countBuckets}<br/>Link: <br/><a href=\"{$linkOnKibana}\">{$linkOnKibana}</a><br/><br/>", 'text/plain');
            $this->mailer->send($msg, [Mailer::OPTION_SKIP_STAT => true]);

            return true;
        }

        return false;
    }

    private function getPostData(string $route, int $length)
    {
        $endDate = new \DateTimeImmutable();
        $startDate = $endDate->sub(new \DateInterval("P{$length}D"));

        return '
        {
          "size": 0,
          "query": {
            "bool": {
              "must": [
                {
                  "query_string": {
                    "query": "context.route:' . $route . '",
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
          },
          "aggs": {
            "2": {
              "date_histogram": {
                "field": "@timestamp",
                "interval": "1h",
                "time_zone": "UTC",
                "min_doc_count": 0,
                "extended_bounds": {
                  "min": ' . $startDate->getTimestamp() * 1000 . ',
                  "max": ' . $endDate->getTimestamp() * 1000 . '
                }
              },
              "aggs": {
                "1": {
                  "avg": {
                    "field": "context.php_time"
                  }
                }
              }
            }
          }
        }        
        ';
        /*
        return '{
          "size": 0,
          "query": {
            "filtered": {
              "query": {
                "query_string": {
                  "analyze_wildcard": true,
                  "query": "context.route:'. $route .'"
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
          "aggs": {
            "2": {
              "date_histogram": {
                "field": "@timestamp",
                "interval": "1h",
                "time_zone": "UTC",
                "min_doc_count": 0,
                "extended_bounds": {
                  "min": '. $startDate->getTimestamp() * 1000 .',
                  "max": '. $endDate->getTimestamp() * 1000 .'
                }
              },
              "aggs": {
                "1": {
                  "avg": {
                    "field": "context.php_time"
                  }
                }
              }
            }
          }
        }';*/
    }
}
