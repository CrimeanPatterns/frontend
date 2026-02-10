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
#rsync "$RSYNC_OPTIONS" awardwallet_lite.sql staging2:/tmp/

echo "drop database if exists $DBNAME; create database $DBNAME CHARACTER SET utf8 COLLATE utf8_general_ci;" | $mysql
echo "grant all privileges on $DBNAME.* to awardwallet identified by 'awardwallet'" | $mysql
#$mysql --database=$DBNAME < awardwallet_clean.sql
$mysqlupload --database=$DBNAME < awardwallet_clean.sql
echo "grant all privileges on $DBNAME.* to awardwallet identified by 'awardwallet'" | $mysql

ssh staging2 bash <<EOF
sed -i '/^[ \t]*database_name/s/: awardwallet_.*$/: '"$DBNAME"'/' /www/staging/shared/configs/parameters.yml
EOF

ssh staging2 docker exec -i staging-1_php_1 bash <<EOF
cd /www/awardwallet
docker/updateYml.php app/config/parameters.yml parameters/database_name $DBNAME
export SYMFONY_ENV=staging
export SYMFONY_DEBUG=0
umask 0002
php app/console cache:warmup
EOF

ssh staging2 docker exec -i staging-2_php_1 bash <<EOF
cd /www/awardwallet
docker/updateYml.php app/config/parameters.yml parameters/database_name $DBNAME
export SYMFONY_ENV=staging
export SYMFONY_DEBUG=0
umask 0002
php app/console cache:warmup
EOF

echo "show databases like 'awardwallet\_%'" | $mysql --skip-column-names --silent | grep -v $DBNAME | sed 's/^/drop database /i' | sed 's/$/;/i' | $mysql

echo SUCCESS
