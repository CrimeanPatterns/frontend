#!/bin/bash
set -euxo pipefail

cd `dirname "$0"`
source sync-common.sh

mysqldump --defaults-file=~/vars/mysql/frontend.cnf awardwallet $loyaltyTables AirCode Aircraft StationCode Fingerprint --set-gtid-purged=OFF --no-tablespaces --no-create-db --no-data --default-character-set=utf8
mysqldump --defaults-file=~/vars/mysql/loyalty.cnf wsdlawardwallet Partner PartnerApiKey --set-gtid-purged=OFF --no-tablespaces --no-create-db --no-data --default-character-set=utf8
mysqldump --defaults-file=~/vars/mysql/shared.cnf shared FlightStatsCache --set-gtid-purged=OFF --no-tablespaces --no-create-db --no-data --default-character-set=utf8
