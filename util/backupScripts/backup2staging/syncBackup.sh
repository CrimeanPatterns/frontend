#!/bin/bash
set -eo pipefail

[ -n "$1" ] && spotIP="$1" || exit 1

stagingIP='192.168.4.24'

ssh $stagingIP "sudo rm -Rf /btrfs/mysql-data-new"

scp ~/.ssh/staging.pem ec2-user@"$spotIP":/home/ec2-user/

ssh ec2-user@"$spotIP" bash -c 'true
set -eov pipefail
sudo chown -R ec2-user:ec2-user /ssd/database
du -hs /ssd/database
rsync --verbose --recursive -e "ssh -i ~/staging.pem -o StrictHostKeyChecking=no" /ssd/database/* ubuntu@'$stagingIP':/btrfs/mysql-data-new
du -hs /ssd/clickhouse-csv
rsync --verbose --recursive -e "ssh -i ~/staging.pem -o StrictHostKeyChecking=no" /ssd/clickhouse-csv/* ubuntu@'$stagingIP':/btrfs/clickhouse-csv
'
