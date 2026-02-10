#!/bin/bash
set -euxo pipefail

backupFile='/tmp/providerTablesSync.sql'

cd `dirname "$0"`
source sync-common.sh

restoreBackup() {
    configName=$1

    configPath="$HOME/vars/mysql/$configName.cnf"
    echo "Restoring the tables using $configName.cnf file ..."
    mysql --defaults-extra-file="$configPath" < "$backupFile"
}

fixDump() {
    for rpl in `grep 'REPLACE INTO' "$backupFile" | cut -d " " -f 3 | sort -u`; do
        sed -i '0,/REPLACE INTO '"$rpl"'/s/REPLACE INTO '"$rpl"'/BEGIN; DELETE FROM '"$rpl"'; REPLACE INTO '"$rpl"'/' "$backupFile"
        sed -i 's/\/\*!40000 ALTER TABLE '"$rpl"' ENABLE KEYS \*\//COMMIT; ALTER TABLE '"$rpl"' ENABLE KEYS/' "$backupFile"
    done
}

syncTables() {
    configName=$1
    tables=$2

    echo "syncing $configName, tables: $tables"
    mysqldump --defaults-file=~/vars/mysql/frontend.cnf awardwallet $tables --set-gtid-purged=OFF --no-tablespaces --single-transaction  --no-create-db --no-create-info --replace --default-character-set=utf8 --skip-add-locks > $backupFile
    fixDump

    echo "Testing restore on the testwsdl database"
    mysql --defaults-file=~/vars/mysql/wsdltest.cnf -B -f < $backupFile
    restoreBackup $configName
}

#syncTables 'wsdl' "$wsdlTables"
syncTables 'loyalty' "$loyaltyTables"
syncTables 'juicymiles-ra' "$loyaltyTables"
syncTables 'ra-awardwallet' "$loyaltyTables"
syncTables 'email' "$emailTables"
syncTables 'email-eu' "$emailTables"

echo "Done updating the database"

