#!/bin/bash -xv

cd /www/awardwallet || exit 1
err=''

app/console aw:service:remove-detached-files -vv || err="$err\service-remove-detached-files"


if [[ "$err" == "" ]]; then
  echo "success"
  exit 0
else
  echo -e "There are failures:\n$err"
  exit 1
fi
