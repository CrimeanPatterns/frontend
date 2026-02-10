#!/bin/bash
set -euxo pipefail

# Variables
databases='loyalty email email-eu'
tables='Partner PartnerApiKey PartnerCallback EmailBlock PartnerMailbox'
backupFile='/tmp/loyalty_tables.sql'
failedDumps='/tmp/failedLoyaltyUpload'

restoreBackup() {
    [ -z "$backupFile$databases" ] && echo "Empty backup name or cnf files" && exit 8
    for cnf in $databases; do
        cnfpath="$HOME/vars/mysql/$cnf.cnf"
        ! [ -f "$cnfpath" ] && echo "Can't find file $cnfpath" && exit 9
        echo "Restoring the tables using $cnf.cnf file ..."
        mysql --defaults-extra-file="$cnfpath" < "$backupFile" || exit 5
    done
}

HOME='/var/lib/jenkins'
cd "$HOME"

echo "Making backup"
db=`sed '/^database=/!d;s/^.*=//' ~/vars/mysql/wsdl.cnf`
rm -f .wsdl_md.cnf
sed '/^database=/d' ~/vars/mysql/wsdl.cnf > .wsdl_md.cnf
chmod 400 .wsdl_md.cnf

echo "Making backup for tables $tables..."
mysqldump --defaults-extra-file=.wsdl_md.cnf $db $tables --skip-add-locks --single-transaction  --no-create-db --no-create-info --replace --default-character-set=utf8 > $backupFile || exit 1
for rpl in `grep 'REPLACE INTO' "$backupFile" | cut -d " " -f 3 | sort -u`; do                                                                                              
    sed -i '0,/REPLACE INTO '"$rpl"'/s/REPLACE INTO '"$rpl"'/BEGIN; DELETE FROM '"$rpl"'; REPLACE INTO '"$rpl"'/' "$backupFile" || exit 1
done                                   
sed -i 's/UNLOCK TABLES;/COMMIT; UNLOCK TABLES;/' "$backupFile" || exit 1
 
echo "Created backup $backupFile"
echo "Trying to upload tables to the test database..."
if [ -f "$HOME//vars/mysql/wsdltest.cnf" ]; then
    echo "Test restoring tables to the testwsdl rds database at Amazon..."
    mout=`mysql --defaults-file=~/vars/mysql/wsdltest.cnf -B -f < $backupFile 2>&1`
    if [ -z "$(echo "$mout" | grep '^ERROR')" ]; then
        restoreBackup
    else
        echo "There are some errors while trying to upload table dumps..."
        echo "$mout"
        [ -d "$failedDumps" ] || mkdir -p "$failedDumps"
        echo "Failed dump will be saved in $failedDumps"
        cp -v "$backupFile" "$failedDumps/$(date +%F-%T)-$(basename "$backupFile")"
        exit 8
    fi
else
    restoreBackup
fi

rm "$backupFile"

