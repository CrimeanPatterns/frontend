#!/usr/bin/env bash
HOST=http://localhost:9200

curl $HOST'/_reindex' -H 'Content-Type: application/json' -d '
{
  "source": {
    "index": "logstash-2020.05.12"
  },
  "dest": {
    "index": "logstash-2020.05.12-a"
  },
  "conflicts": "proceed"
}
'
