#!/usr/bin/env bash
set -uxo pipefail

space=`df / | grep '/' | sed 's/%//' | awk '{print $5}'`
if [[ $space -gt 80 ]]
then
  echo "low on disk space"
  exit 1
fi

space=`df -i / | grep '/' | sed 's/%//' | awk '{print $5}'`
if [[ $space -gt 80 ]]
then
  echo "low on inodes"
  exit 1
fi

running=$(supervisorctl status | grep FATAL | wc -l)
if [[ $running -gt 2 ]]
then
  echo "too many supervisor processes in fatal state"
  exit 1
fi

echo "success"