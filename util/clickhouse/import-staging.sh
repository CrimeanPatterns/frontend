#!/usr/bin/env bash

set -euxo pipefail

CURRENT_VERSION=`mysql --defaults-file=~/vars/mysql/staging.cnf -e "select Val from Param where Name = 'clickhouse_db_version'" --skip-column-names --batch`
if [[ "$CURRENT_VERSION" == "1" ]]
then
    VERSION="2"
else
    VERSION="1"
fi

ssh staging "true
set -euxo pipefail
cd /www/staging/staging-1/util/clickhouse
docker-compose up -d clickhouse
docker-compose run --rm clickhouse-client /app/import-staging-container.sh $VERSION
"

mysql --defaults-file=~/vars/mysql/staging.cnf -e "update Param set Val = '$VERSION' where Name = 'clickhouse_db_version'"