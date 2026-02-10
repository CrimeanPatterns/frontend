#!/bin/bash -xv

set -euxo pipefail

#Set the variables
siteName=awardwallet
backupFile='/tmp/'$siteName'_airports.sql'
awtables='AirCode Aircraft Airline StationCode'

echo "*** Backing up the following tables:
$awtables"
mysqldump --defaults-extra-file=~/vars/mysql/frontend.cnf $siteName $awtables --set-gtid-purged=OFF --no-tablespaces --single-transaction  --no-create-db --no-create-info --replace --default-character-set=utf8 > $backupFile || exit 3
for rpl in `grep 'REPLACE INTO' "$backupFile" | cut -d " " -f 3 | sort -u`; do
    sed -i '0,/REPLACE INTO '"$rpl"'/s/REPLACE INTO '"$rpl"'/BEGIN; DELETE FROM '"$rpl"'; REPLACE INTO '"$rpl"'/' "$backupFile" || exit 3
done
sed -i 's/UNLOCK TABLES;/COMMIT; UNLOCK TABLES;/' "$backupFile" || exit 3
echo created backup $backupFile

echo "restoring the tables to the wsdl rds database at Amazon..."
#mysql --defaults-extra-file=~/vars/mysql/wsdl.cnf < $backupFile || exit 5
mysql --defaults-extra-file=~/vars/mysql/loyalty.cnf < $backupFile || exit 5
mysql --defaults-extra-file=~/vars/mysql/juicymiles-ra.cnf < $backupFile || exit 5
mysql --defaults-extra-file=~/vars/mysql/ra-awardwallet.cnf < $backupFile || exit 5
mysql --defaults-extra-file=~/vars/mysql/email.cnf < $backupFile || exit 5
mysql --defaults-extra-file=~/vars/mysql/email-eu.cnf < $backupFile || exit 5

echo "Done updating the database"

