#!/usr/bin/env bash

set -euxo pipefail
STAGING_IP='192.168.4.24'
STAGING_PARAM='/www/staging/shared/configs/parameters.yml'

rsync --inplace --update /backups/databases/awardwallet_clean/* $STAGING_IP:/www/staging/shared/awardwallet_clean/

# Getting numbers of active and passive staging mysql containers
abase=`ssh $STAGING_IP cat "$STAGING_PARAM" 2>/dev/null | sed '/ database_host:/!d;s/^.*: mysql//'`
pbase=''
case $abase in
    1)
        pbase=2
    ;;
    2)
        pbase=1
    ;;
    *)
        echo "Can't find staging passive database container number"
        exit 1
    ;;
esac

# Cleaning passive staging database container
ssh $STAGING_IP "set -euxo pipefail; cd /opt/serverscripts/staging; sudo -u user ./services staging stop mysql$pbase; sudo rm -rf /www/staging/shared/mysql-data$pbase/*; sudo -u user ./services staging up -d mysql$pbase"
echo "Waiting for database will be ready for connections..."
sleep 15
ecode=1
while [ "$ecode" -eq "1" ]; do
    echo "Waiting for database will be ready for connections..."
    sleep 2;
    ecode=0
    /usr/bin/timeout --preserve-status -s 9 20 nc -z "$STAGING_IP" "3300$pbase" || ecode="$?"
done

ssh $STAGING_IP "set -euxo pipefail; cd /opt/serverscripts/staging; sudo -u user ./services staging exec mysql$pbase mysql --database=awardwallet -e 'source /var/lib/mysql-files/infile/awardwallet_clean.sql'"

