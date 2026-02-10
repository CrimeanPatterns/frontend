#!/usr/bin/env bash
HOST=http://localhost:9201

curl -XPUT $HOST'/_template/loyalty-stat' -d '
{
  "template": "loyalty-stat*",
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
        }
      ]
    }
  }
}
'
