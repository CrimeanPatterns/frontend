#!/bin/sh
set -eov pipefail

sync
sleep 30
sync
sudo chown -R ec2-user:ec2-user /ssd/database
sudo chown -R ec2-user:ec2-user /ssd/clickhouse-csv
sudo chown -R ec2-user:ec2-user /ssd/cards-report
ls -l /ssd/cards-report