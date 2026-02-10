<?php
require __DIR__ . '/../../web/kernel/public.php';

$requests = queryEs('useragent:"lenovo" AND useragent:"megafon" AND (first_line_request:"/login" OR first_line_request:"/user/check" OR first_line_request:"/login_check")');
echo "got " . count($requests) . " login attempts\n";
$requestIds = array_map(function(array $doc){  return $doc['_source']['RequestID']; }, $requests);

$ips = array_map(function(array $doc){  return $doc['_source']['ip']; }, $requests);
$ips = array_unique($ips);
echo "from " . count($ips) . " ips\n";

$requests = queryEs('message:"User was loaded"');
$requests = array_filter($requests, function(array $doc) use($requestIds){ return in_array($doc['_source']['RequestID'], $requestIds); });
$users = array_map(function(array $doc){ return $doc['_source']['context']['userid']; }, $requests);
$users = array_unique($users);
echo "users under attack: " . count($users) . "\n";

$requests = queryEs('message:"Credentials check passed"');
$requests = array_filter($requests, function(array $doc) use($requestIds){ return in_array($doc['_source']['RequestID'], $requestIds); });
$users = array_map(function(array $doc){ return $doc['_source']['UserID']; }, $requests);
$users = array_unique($users);
echo "attacker acquired valid password for " . count($users) . " users\n";

$requests = queryEs('message:"Auth success"');
$requests = array_filter($requests, function(array $doc) use($requestIds){ return in_array($doc['_source']['RequestID'], $requestIds); });
$users = array_map(function(array $doc){ return $doc['_source']['UserID']; }, $requests);
$users = array_unique($users);
echo "successfully logged in to " . count($users) . " users\n";

$requests = queryEs('message:"Email-OTC is not provided"');
$requests = array_filter($requests, function(array $doc) use($requestIds){ return in_array($doc['_source']['RequestID'], $requestIds); });
$users = array_map(function(array $doc){ return $doc['_source']['UserID']; }, $requests);
$users = array_unique($users);
echo "sent email OTC to " . count($users) . " users\n";
echo "users with hacked passwords: " . implode(", ", $users) . "\n";

function queryEs($query)
{
    $cacheFile = "/tmp/cache_" . sha1($query);
    if(file_exists($cacheFile)) {
        return json_decode(file_get_contents($cacheFile), true);
    }

//    echo "searching $query\n";
    $data = curlRequest('http://docker.for.mac.localhost:9200/logstash-2018.11.01/_search?scroll=5m&pretty', 30, [
        CURLOPT_FAILONERROR => false,
        CURLOPT_POSTFIELDS => '
        {
          "size": 5000,
          "sort": [
            {
              "@timestamp": {
                "order": "desc",
                "unmapped_type": "boolean"
              }
            }
          ],
          "highlight": {
            "pre_tags": [
              "@kibana-highlighted-field@"
            ],
            "post_tags": [
              "@/kibana-highlighted-field@"
            ],
            "fields": {
              "*": {}
            },
            "require_field_match": false,
            "fragment_size": 2147483647
          },
          "query": {
            "filtered": {
              "query": {
                "query_string": {
                  "query": "' . addslashes($query) . '",
                  "analyze_wildcard": true
                }
              },
              "filter": {
                "bool": {
                  "must": [
                    {
                      "range": {
                        "@timestamp": {
                          "gte": 1541078570792,
                          "lte": 1541079051532,
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
            "context.New",
            "@timestamp",
            "context.flightDate",
            "context.update",
            "context.startDate",
            "timestamp",
            "context.StatDate",
            "context.endDate"
          ]
        }
    ']);
    $response = json_decode($data, true);

    if(!isset($response['hits']['total'])){
        throw new \Exception("invalid response for $query: $data");
    }
    $result = $response['hits']['hits'];
    do {
        $data = curlRequest(
            'http://docker.for.mac.localhost:9200/_search/scroll?scroll=5m&pretty&scroll_id=' . urlencode($response['_scroll_id']),
            30,
            [
                CURLOPT_FAILONERROR => false,
            ]
        );
        $response = json_decode($data, true);
        if(!isset($response['hits']['total'])){
            throw new \Exception("invalid response for $query: $data");
        }
        $result = array_merge($result, $response['hits']['hits']);
    } while (count($response['hits']['hits']) > 0);

    //echo "got " . count($result) . " hits\n";

    file_put_contents($cacheFile, json_encode($result));

    return $result;
}

