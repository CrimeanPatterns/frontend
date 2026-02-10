#!/usr/bin/env bash
set -euxo pipefail
mysql='mysql -h 127.0.0.1 -u awardwallet -pawardwallet'

if [ -f '/www/staging/shared/configs/dbname' ]; then
    DBNAME=`cat /www/staging/shared/configs/dbname`
else
    echo "Fail to get last database name from /www/staging/shared/configs/dbname"
    exit 1
fi

if ! [ -f '/backup/awardwallet_clean.sql' ]; then
    echo "Fail to get mysqldump /backup/awardwallet_clean.sql"
    exit 2
fi

time $mysql "$DBNAME" < /backup/awardwallet_clean.sql

sed -i '/^[ \t]*database_name/s/: awardwallet_.*$/: '"$DBNAME"'/' /www/staging/shared/configs/parameters.yml

docker exec -i staging-1_php_1 bash <<EOF
cd /www/awardwallet
docker/updateYml.php app/config/parameters.yml parameters/database_name $DBNAME
export SYMFONY_ENV=staging
export SYMFONY_DEBUG=0
umask 0002
php app/console cache:warmup
EOF

docker exec -i staging-2_php_1 bash <<EOF
cd /www/awardwallet
docker/updateYml.php app/config/parameters.yml parameters/database_name $DBNAME
export SYMFONY_ENV=staging
export SYMFONY_DEBUG=0
umask 0002
php app/console cache:warmup
EOF

echo "show databases like 'awardwallet\_%'" | $mysql --skip-column-names --silent | grep -v $DBNAME | sed 's/^/drop database /i' | sed 's/$/;/i' | $mysql

echo SUCCESS
