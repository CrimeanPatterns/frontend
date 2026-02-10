#!/bin/bash

set -eov pipefail

RECIPIENTS='sysadmin@awardwallet.com'
BPATH=/backups/databases
FNAME=awardwallet.sql
DT=`date +%F`
BKNAME="awardwallet_$DT.sql.gz.gpg"

cd "$BPATH"
find . -name 'awardwallet_*.gz.gpg' -mtime +1 -delete

cd `dirname "$0"`
gpg --import jenkins.asc
! [ -f "$BPATH/$FNAME" ] && echo "File $BPATH/$FNAME is not exist!" && exit 1
[ -z "$(find "$BPATH" -name "$FNAME" -mtime -2)" ] && echo "SQL dump is too old!" && exit 2
[ -z "$(ls -la "$BPATH/$FNAME" | awk '$5 > 60177638602 { print $5 }')" ] && echo "SQL dump is suspicious small" && exit 3

cd "$BPATH"

REC=`echo "$RECIPIENTS" | sed 's/[ \t]*$//;s/^/ /;s/ / --recipient /g'`
gzip -c "$FNAME" | gpg -o "$BKNAME" --encrypt --yes --batch --trust-model always$REC

aws --profile backups s3 cp "$BKNAME" s3://aw-config-backups/databases/


