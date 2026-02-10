#!/usr/bin/env bash

# move indexes from one ES to another
indexes=`curl -s http://192.168.2.163:9200/payments-*?pretty | jq -r ". | keys[]"`; for index in $indexes; do docker run --rm -ti taskrabbit/elasticsearch-dump --input=http://192.168.2.163:9200/$index --output=http://192.168.2.69:9200/$index; done

# list indexes
curl -s http://localhost:9201/_cat/indices?v | grep payments | wc -l
