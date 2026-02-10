#!/usr/bin/env bash
HOST=http://localhost:9201

indexes=`curl -s $HOST/loyalty-stat-*?pretty | jq -r ". | keys[]"`
for index in $indexes
do
  target=${index//-/.}
  target=${target//loyalty.stat./loyalty-stat-}
  echo $index' -> '$target
  curl $HOST'/_reindex' -d '
  {
    "source": {
      "index": "'$index'"
    },
    "dest": {
      "index": "'$target'"
    }
  }
  '
done