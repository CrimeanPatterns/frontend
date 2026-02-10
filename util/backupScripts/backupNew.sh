#!/bin/bash

set -euxo pipefail

PROJECT_HOME=`pwd`
RSYNC_OPTIONS='-azO --quiet'
STAGING_IP='192.168.4.24'
STAGING_PARAM='/www/staging/shared/configs/parameters.yml'
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
BACKUP_WORKER_IP='192.168.4.65'
BACKUP_WORKER_ID='i-04fcec73ab1ee21da'

# ------------ backup database --------------
backupDatabase() {
    DATE=`date +%F`
    BACKUP_NAME="awardwallet_$DATE.sql.gz.gpg"

    aws ec2 start-instances --instance-ids $BACKUP_WORKER_ID
    aws ec2 wait instance-running --instance-ids $BACKUP_WORKER_ID
    sleep 60

    scp ~/vars/jenkins/jenkins.asc $BACKUP_WORKER_IP:/home/ubuntu/
    scp ~/vars/mysql/frontend.cnf $BACKUP_WORKER_IP:/home/ubuntu/
    rsync --recursive ~/.aws $BACKUP_WORKER_IP:/home/ubuntu/

    IGNORE_TABLES=$(mysql --defaults-file=~/vars/mysql/frontend.cnf -Bs awardwallet <<<'show tables from awardwallet where tables_in_awardwallet like "MerchantRematchTransactionsExamples%" or tables_in_awardwallet like "LastTransactionsExamples%";' 2>/dev/null | sed 's/^/--ignore-table=awardwallet./' | tr '\n' ' ')

    set +e
    # mysql fixed to 8.0.19 because newer versions shows
    # Couldn't execute 'FLUSH TABLES': Access denied; you need (at least one of) the RELOAD or FLUSH_TABLES privilege(s)
    ssh $BACKUP_WORKER_IP <<EOF
    set -euo pipefail
    gpg --import ~/jenkins.asc
    docker run --rm -v /home/ubuntu/frontend.cnf:/root/frontend.cnf --rm mysql/mysql-server:8.0.19 mysqldump --defaults-file=/root/frontend.cnf --host=frontend-replica.mysql.awardwallet.com $IGNORE_TABLES --ignore-table=awardwallet.RAFlight --skip-opt --add-drop-table --add-locks --create-options --disable-keys --extended-insert --quick --single-transaction --set-gtid-purged=OFF --no-tablespaces awardwallet \
        | gpg --encrypt --yes --batch --trust-model always --recipient sysadmin@awardwallet.com \
        | aws --profile backups s3 cp --no-progress --expected-size 100000000000 - s3://aw-config-backups/databases/$BACKUP_NAME
EOF
    sshExitCode=$?
    set -e
    aws ec2 stop-instances --instance-ids $BACKUP_WORKER_ID
    aws ec2 wait instance-stopped --instance-ids $BACKUP_WORKER_ID
    if [ $sshExitCode -ne 0 ]; then
        echo "SSH to backup worker failed"
        exit 1
    fi
}

backupForParserbox() {
    cd $DIR
    mysqldump --defaults-file=~/vars/mysql/frontend.cnf --host=frontend-replica.mysql.awardwallet.com --skip-opt --add-drop-table --add-locks --create-options --disable-keys --extended-insert --quick --single-transaction --no-tablespaces --set-gtid-purged=OFF --no-data awardwallet GeoTag adminLeftNav -r /backups/databases/parserbox-web.sql
    source ../sync/sync-common.sh
    mysqldump --defaults-file=~/vars/mysql/frontend.cnf --host=frontend-replica.mysql.awardwallet.com --skip-opt --add-drop-table --add-locks --create-options --disable-keys --extended-insert --quick --single-transaction  --no-tablespaces --set-gtid-purged=OFF awardwallet $loyaltyTables AirCode StationCode Aircraft AirlineTicketPrefix >> /backups/databases/parserbox-web.sql
    mysqldump --defaults-file=~/vars/mysql/frontend.cnf --host=frontend-replica.mysql.awardwallet.com --skip-opt --add-drop-table --add-locks --create-options --disable-keys --extended-insert --quick --single-transaction  --no-tablespaces --set-gtid-purged=OFF --where 'LastSeen > adddate(now(), -4)' awardwallet Fingerprint >> /backups/databases/parserbox-web.sql
    cd $HOME/workspace/Docker/build-docker-images/mysql
    CONTAINER_NAME=parserbox-web DUMP_SQL=/backups/databases/parserbox-web.sql SCRIPT=$DIR/build-parserbox-web.sql MYSQL_VERSION=5.7 ./build-data.sh
}

backupLite() {
    docker-compose run -v /backups/databases:/backups/databases --rm php app/console -vv aw:backup backup-1000 -vv --file=/backups/databases/awardwallet_lite.sql
}

# ------------- backup files -----------------
backupFiles() {
    BKNAME="uploaded-`date +%F`.tgz"
    date
    echo "Backing up uploaded..."
    rsync --delete $RSYNC_OPTIONS --size-only --ignore-times --exclude=temp /mnt/uploaded/ /backups/uploaded
    cd /backups
    rm -f ./uploaded-*.tgz
    tar czf "/backups/$BKNAME" uploaded
    echo "Copying uploaded backup to s3"
    aws --profile backups s3 cp --no-progress "/backups/$BKNAME" "s3://aw-config-backups/uploaded/$BKNAME"
}

# ---------- backup card images -----------------
backupCardImages() {
    FOLDER='/backups/cardimage'
    SRCBUCKET='s3://prod-cardimagebucket'
    DSTBUCKET='s3://aw-config-backups/cardimage-backups'
    BKNAME="cardimage-`date +%F`.tgz"

    [ -f "$FOLDER/$BKNAME" ] && rm "$FOLDER/$BKNAME"
    aws s3 sync --no-progress "$SRCBUCKET" "$FOLDER/"
    cd "$FOLDER"
    rm -f *.tgz
    echo Making archive...
    find . -type f -print > /tmp/cardimages.list
    tar czf "$BKNAME" --files-from /tmp/cardimages.list --exclude='*.tgz'
    echo Uploading to the backup s3
    aws --profile backups s3 cp --no-progress "$BKNAME" "$DSTBUCKET/$BKNAME"
    cd "$PROJECT_HOME"
}


# ----------------- backup blog ------------------
backupBlog() {
    bkpath=/backups/blog
    WEEKDAY=`date +%A`
    mysqldump --defaults-file=/var/lib/jenkins/vars/mysql/blog.cnf --no-tablespaces --single-transaction --set-gtid-purged=OFF blog -r $bkpath/blog_1.sql
    mv "$bkpath/blog_1.sql" "$bkpath/blog.sql"
    rm -f "$bkpath/blog.sql.gz"
    gzip "$bkpath/blog.sql"
    aws --profile backups s3 cp --no-progress "$bkpath/blog.sql.gz" "s3://aw-config-backups/blog/$WEEKDAY.sql.gz"

    rsync --quiet --rsync-path="sudo rsync" --delete -avzO --exclude=html/wp-content/cache 192.168.2.139:/opt/blog "$bkpath/www"
    cd $bkpath
    rm -f blog_1.tar.gz
    tar czf blog_1.tar.gz www
    mv "$bkpath/blog_1.tar.gz" "$bkpath/blog.tar.gz"
    aws --profile backups s3 cp --no-progress "$bkpath/blog.tar.gz" "s3://aw-config-backups/blog/$WEEKDAY.tar.gz"
    cd "$PROJECT_HOME"

    MONTHDAY=`date +%d`
    if [ $MONTHDAY -eq 1 ]; then
        MONTH=`date +%m`
        aws --profile backups s3 cp --no-progress "$bkpath/blog.tar.gz" "s3://aw-config-backups/blog/"$MONTH"-MONTH.tar.gz"
        aws --profile backups s3 cp --no-progress "$bkpath/blog.sql.gz" "s3://aw-config-backups/blog/"$MONTH"-MONTH.sql.gz"
    fi

    #rm "$bkpath/blog.sql.gz"
}

# --------------- build mysql-data container ---------------
buildMySQLData() {
    SCRIPT=`pwd`/util/backupScripts/build-frontend.sql
    cd $HOME/workspace/Docker/build-docker-images/mysql
    CONTAINER_NAME=awardwallet8 DUMP_SQL=/backups/databases/awardwallet_lite.sql SCRIPT=$SCRIPT MYSQL_VERSION=8.0 ./build-data.sh
}

[ -n "$*" ] && params="$*" || params='all'
[ -n "$(echo "$params" | grep '\([^a-zA-Z]\|^\)all\([^a-zA-Z]\|$\)')" ] && params='all'

if [ "$params" = "all" ]; then
    backupDatabase
    backupForParserbox
    backupLite
    backupFiles
    backupCardImages
    backupBlog
    cleanupBackups
    buildMySQLData
else
    for p in $params; do
        echo "Backup stage: $p"
        case $p in
            database)
                backupDatabase
            ;;
            parserbox)
                backupForParserbox
            ;;
            lite)
                backupLite
            ;;
            files)
                backupFiles
            ;;
            cardimages)
                backupCardImages
            ;;
            blog)
                backupBlog
            ;;
            copyToS3)
                copyToS3
            ;;
            cleanup)
                cleanupBackups
            ;;
            mysqldata)
                buildMySQLData
            ;;
            *)
                echo "Wrong parameter"
                exit 1
            ;;
        esac
    done
fi

cd "$PROJECT_HOME"

echo SUCCESS
