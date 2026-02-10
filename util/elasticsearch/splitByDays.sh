#!/usr/bin/env bash
HOST=http://localhost:9201

curl $HOST'/_reindex' -d '
{
  "source": {
    "index": "statistic"
  },
  "dest": {
    "index": "loyalty"
  },
  "script": {
    "lang": "painless",
    "source": "ctx._index = '"'"'loyalty-stat-'"'"' + (ctx._source.CheckCompleteDate.substring(0, '"'"'2017-08-01'"'"'.length()))"
  }
}
'

