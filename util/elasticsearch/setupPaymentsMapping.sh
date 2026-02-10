#!/usr/bin/env bash
HOST=http://localhost:9201

curl -XPUT $HOST'/_template/payments' -d '
{
  "template": "payments*",
  "mappings": {
    "fluentd": {
      "properties": {
        "context": {
          "type": "object"
        }
      },
      "dynamic_templates": [
        {
          "context_exclusion": {
            "path_match": "context.*",
            "mapping": {
              "dynamic": false
            }
          }
        },
        {
          "string_fields" : {
            "match_mapping_type" : "string",
            "match" : "*",
            "mapping" : {
              "index" : "analyzed",
              "omit_norms" : true,
              "type" : "string",
              "fields" : {
                 "raw" : {
                   "ignore_above" : 256,
                   "index" : "not_analyzed",
                   "type" : "string"
                 }
              }
            }
          }
        }
      ]
    }
  }
}
'
