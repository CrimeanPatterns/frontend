#!/bin/bash
set -eov pipefail
export PYTHON_UNBUFFERED=true

cd `dirname "$0"`

ipFile=`mktemp`

./spotCancel.py
echo "Creating Spot Instance"
./spotUp.py --ipfile "$ipFile"

spotIP=`cat "$ipFile"`
rm "$ipFile"

echo "Uploading script to the Spot Instance $spotIP"
scp spotBackupUpload.sh ec2-user@"$spotIP":/home/ec2-user/
echo "Dumping vendors structure"
echo "create database vendor;
grant all privileges on vendor.* to awardwallet;
use vendor;" >/tmp/02-vendors-structure.sql
mysqldump --defaults-file=~/vars/mysql/vendor.cnf --no-data vendor >>/tmp/02-vendors-structure.sql
scp /tmp/02-vendors-structure.sql ec2-user@"$spotIP":/home/ec2-user/
echo "Run script on the spot instance"
ssh ec2-user@"$spotIP" /home/ec2-user/spotBackupUpload.sh | tee /tmp/spotBackupUpload.log
scp ec2-user@"$spotIP":/tmp/detected-threads /tmp/spot-detected-threads
threads=$(cat /tmp/spot-detected-threads)
# not needed after merging into master
../../../docker/prod/prepare-ecs-task.py --source-task-family "frontend-worker" --target-task-family "frontend-task"
./prepare-backup-task.py --source-task-family "frontend-task" --target-task-family "frontend-backup-task"
docker build -t 718278292471.dkr.ecr.us-east-1.amazonaws.com/frontend/worker:backup-mysql ../../../docker/prod/backup-mysql
aws ecr get-login-password | docker login --username AWS --password-stdin 718278292471.dkr.ecr.us-east-1.amazonaws.com
docker push 718278292471.dkr.ecr.us-east-1.amazonaws.com/frontend/worker:backup-mysql

sleep 60
../../../docker/prod/run-ecs-task.py --cluster frontend --capacity-provider clean-base-for-staging-workers --task-family "frontend-backup-task" --container worker --command \
  "app/console aw:backup --no-ansi -vv backup-clean --threads $threads --targetHost mysql --clickhouse-dump-path /clickhouse-csv --cards-report-path /cards-report/"
scp spotBackupFinish.sh ec2-user@"$spotIP":/home/ec2-user/
ssh ec2-user@"$spotIP" /home/ec2-user/spotBackupFinish.sh
echo "Grab artifacts"
rm -f ../../../build/historyPatternsReport.csv
scp ec2-user@"$spotIP":/ssd/cards-report/historyPatternsReport.csv ../../../build/
echo "Synchronizing database to the staging"
./syncBackup.sh "$spotIP"
echo "Cancelling Spot Request"
./spotCancel.py
echo "Publishing new database"
./publishBackup.sh
echo "Publishing new clickhouse database to staging"
echo "allow mysql to start"
sleep 300
../../clickhouse/import-staging.sh
echo "Publishing new clickhouse database to prod"
rsync --progress staginglocal.awardwallet.com:/btrfs/clickhouse-csv/* /tmp/clickhouse-csv
../../clickhouse/import-prod.sh
#cat /home/VSilantyev/raflight.sql | sed 's/RAFlight/RAFlightOld/g' | mysql --defaults-file=/var/lib/jenkins/vars/mysql/staging.cnf
echo "Done"
