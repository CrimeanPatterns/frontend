#!/usr/bin/env bash
HOST=http://localhost:9201

curl -XPUT $HOST'/_template/wsdl-stat' -d '
{
  "template": "wsdl-stat*",
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
