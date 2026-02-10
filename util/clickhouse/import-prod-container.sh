#!/usr/bin/env bash

set -euxo pipefail

VERSION=$1

DATABASE=awardwallet_v$VERSION
SERVER=clickhouse.awardwallet.com

echo "create database if not exists $DATABASE" | clickhouse-client --host=$SERVER
clickhouse-client --host=$SERVER --database=$DATABASE --multiquery </app/tables.sql

cd /csv

for FILE in *.csv
do
  TABLE=`echo $FILE | sed s/\.csv//`
  clickhouse-client --host=$SERVER --database=$DATABASE --query="insert into $TABLE format CSV" < $FILE
  clickhouse-client --host=$SERVER --database=$DATABASE --query="select count(*) from $TABLE"
done

