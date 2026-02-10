#!/usr/bin/env bash
HOST=http://localhost:9201

indexes=`curl -s $HOST/loyalty-stat-*-*?pretty | jq -r ". | keys[]"`
for index in $indexes
do
  echo $index
  curl -X DELETE http://localhost:9201/$index
done