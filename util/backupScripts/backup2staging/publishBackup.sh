#!/bin/bash
set -eux pipefail

staging='ssh 192.168.4.24'

$staging bash -c 'true
set -ux pipefail
sync
sleep 3

publish(){
    FOLDER=$1
    RUNNING=`/opt/serverscripts/staging/services $FOLDER ps --services --filter status=running | grep mysql`
    sudo /opt/serverscripts/staging/services $FOLDER stop mysql
    echo "updating database for $FOLDER"
    sleep 10
    sudo rm -rf /btrfs/$FOLDER-mysql-data
    sudo cp -R --reflink=auto /btrfs/staging-mysql-data /btrfs/$FOLDER-mysql-data
    if [[ "$RUNNING" == "mysql" ]]; then
        sudo /opt/serverscripts/staging/services $FOLDER up -d
        ACTIVE=`/opt/serverscripts/staging/getactive $FOLDER`
        cd /www/$FOLDER/$ACTIVE
        docker-compose exec php app/console doctrine:migrations:migrate --allow-no-migration  --no-interaction
    fi
}

sudo /opt/serverscripts/staging/services staging stop mysql
sleep 10
sudo /opt/serverscripts/staging/services staging pull mysql
sudo rm -rf /btrfs/staging-mysql-data
sudo chown -R 999:docker /btrfs/mysql-data-new
mv /btrfs/mysql-data-new /btrfs/staging-mysql-data

publish "clean-base"
publish "dev-desktop"

sudo /opt/serverscripts/staging/services staging up -d
'

sleep 180
