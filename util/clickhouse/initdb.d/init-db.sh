#!/bin/bash
set -euxo pipefail

clickhouse client -n <<-EOSQL
    DROP DATABASE IF EXISTS awardwallet_v1;
    DROP DATABASE IF EXISTS awardwallet_v2;
    CREATE DATABASE awardwallet_v1;
    CREATE DATABASE awardwallet_v2;
EOSQL

clickhouse client --database awardwallet_v1 --queries-file /tmp/tables.sql
clickhouse client --database awardwallet_v2 --queries-file /tmp/tables.sql

