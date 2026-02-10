#!/usr/bin/env bash

set -euxo pipefail
RSYNC_OPTIONS='-azO'
mysql='ssh staging2 docker exec -i staging-1_mysql_1 mysql'
mysqlupload='mysql --defaults-file=~/vars/mysql/staging.cnf'

# ------------- sync files to test servers -------
#rsync --delete "$RSYNC_OPTIONS" --size-only --ignore-times --exclude=temp /backups/uploaded/ staging2:/tmp/

# ------------- sync databases to test servers -----

#cd $HOME/workspace/build-docker-images/mysql
#CONTAINER_NAME=awardwallet SCRIPT=build-frontend.sql ./build-data.sh

cd /backups/databases/
DBNAME=awardwallet_`date +%Y_%m_%d_%H_%M`

echo "drop database if exists $DBNAME; create database $DBNAME CHARACTER SET utf8 COLLATE utf8_general_ci;" | $mysql
echo "grant all privileges on $DBNAME.* to awardwallet identified by 'awardwallet'" | $mysql
ssh staging2 bash << EOF
    echo "$DBNAME" > /www/staging/shared/configs/dbname
EOF
rsync "$RSYNC_OPTIONS" awardwallet_clean.sql staging2:/backup


