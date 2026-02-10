<?php
require __DIR__ . '/../../web/kernel/public.php';

$codes = [
    "I-GXF8D34JNKUP",
];

foreach ($codes as $code){
    $ipn = findIpn($code);
    if(!empty($ipn))
        sendIpn($ipn);
}

function findIpn($code)
{
    echo "looking for ipn $code\n";
    $data = curlRequest('http://docker.for.mac.localhost:9200/payments-2017.09.01/_search?pretty', 30, [
        CURLOPT_FAILONERROR => false,
        CURLOPT_POSTFIELDS => '
        {
          "size": 500,
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
                    "query": "\"' . $code . '\" AND \"request received\"",
                    "analyze_wildcard": true
                  }
                }
              ]
            }
          },
          "_source": {
            "excludes": []
          },
          "stored_fields": [
            "*"
          ],
          "script_fields": {},
          "docvalue_fields": [
            "context.New",
            "@timestamp",
            "context.Old",
            "context.ExpirationDate",
            "context.LastPayDate"
          ]
        }
    ']);
    $response = json_decode($data, true);
    if(!isset($response['hits']['total'])){
        echo "invalid response for $code\n";
        echo $data . "\n";
        return null;
    }
    if($response['hits']['total'] != 1){
        echo "not one hit: {$response['hits']['total']}\n";
        return null;
    }
    if(empty($response['hits']['hits'][0]['_source']['context']['request'])){
        echo "invalid data for $code\n";
        echo $data . "\n";
        return null;
    }
    echo "got hit\n";
    return $response['hits']['hits'][0]['_source']['context']['request'];
}


function sendIpn($ipn){
    echo "sending ipn {$ipn['recurring_payment_id']} {$ipn['first_name']} {$ipn['last_name']}\n";
    $requestInfo = [CURLINFO_HTTP_CODE];
    $result = curlRequest('https://awardwallet.com/paypal/IPNListener.php', 10, [
        CURLOPT_POSTFIELDS => $ipn
    ], $requestInfo, $curlErrno);
    if($curlErrno != 0 || $requestInfo[CURLINFO_HTTP_CODE] != 200)
        echo "curl request failed for {$ipn['recurring_payment_id']}, code: {$requestInfo[CURLINFO_HTTP_CODE]}, code: {$curlErrno}\n";
}
