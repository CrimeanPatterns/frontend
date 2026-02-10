#!/usr/bin/env bash

set -euxo pipefail

cd `dirname "$0"`

CURRENT_VERSION=`mysql --defaults-file=~/vars/mysql/frontend.cnf -e "select Val from Param where Name = 'clickhouse_db_version'" --skip-column-names --batch`
if [[ "$CURRENT_VERSION" == "1" ]]
then
    VERSION="2"
else
    VERSION="1"
fi

docker-compose -f docker-compose-builder.yml run --rm clickhouse-client /app/import-prod-container.sh $VERSION
mysql --defaults-file=~/vars/mysql/frontend.cnf -e "update Param set Val = '$VERSION' where Name = 'clickhouse_db_version'"