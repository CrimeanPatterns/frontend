#!/usr/bin/env bash
HOST=http://log.awardwallet.com:9200

curl -XDELETE $HOST'/twitter/'
echo ""
curl -XPUT $HOST'/twitter/' -d '
{
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
echo "
--- indexes created ---"

curl -XPUT $HOST'/twitter/fluent/2' -d '{a: 1, title: "alexi"}'
curl -XPUT $HOST'/twitter/fluent/3' -d '{a: 1, context: {a: 2}}'
curl -XPUT $HOST'/twitter/fluent/4' -d '{a: 1, context: {a: 2, b: 3, c: {x: 1, y:2, name: "veresch"}}}'
echo "
--- documents added, waiting for indexing, index info ---" 
curl $HOST'/twitter/?pretty=true'
sleep 3
echo "

--- all documents ---"
curl -g -XGET $HOST'/twitter/_search?pretty=true' 
echo "
--- search by mapped field ---"
curl -g -XGET $HOST'/twitter/_search?pretty=true' -d '{query: {bool: {must: [{match: {_all: "alexi"}}]}}}'
echo "
--- search by not-mapped field ---"
curl -g -XGET $HOST'/twitter/_search?pretty=true' -d '{query: {bool: {must: [{match: {_all: "veresch"}}]}}}'
echo "
--- adding mapping ---"
curl -XPUT $HOST'/twitter/_mapping/fluent' -d '
{
  "properties": {
    "context": {
      "properties": {
        "c": {
          properties: {
            "name": {
              "type" : "string"
            }
          }
        }
      }
    }
  }
}
'
curl -XPUT $HOST'/twitter/fluent/5' -d '{a: 1, context: {a: 2, b: 3, c: {x: 1, y:2, name: "veresch"}}}'
sleep 3
echo "
--- search by added mapped field ---"
curl -g -XGET $HOST'/twitter/_search?pretty=true' -d '{query: {bool: {must: [{match: {_all: "veresch"}}]}}}'

echo "
--- index info ---"
curl $HOST'/twitter/?pretty=true'
