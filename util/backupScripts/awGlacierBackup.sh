#!/bin/bash
# For this script you need install https://github.com/vsespb/mt-aws-glacier
# date %A day of week
storageBackups=93 # Storage backups count

filename=${1%.*}
echo "Do glacier backup for: " $filename
ZipFileName=$(date +%Y_%m_%d_%A)_$filename'.7z'
echo "7zip name: " $ZipFileName
sqlFileName=$filename'.sql'
echo "Sql name: " $sqlFileName

cd /backups/databases/glacier/
if [ ! -f "$ZipFileName" ]; then
  rm -f *_$filename'.7z'

  cd /backups/databases
  7z -pHAk7PYbivaEbMZKMNrWfMkdqSz7kTwqawuHH2HMBRMd9vDAmxPyqAcFuTFw2MWZXubqj -m0=lzma2 -mx5 a /backups/databases/glacier/$ZipFileName $sqlFileName || exit 1
  echo "compressed"
fi

echo "uploading"
mtglacier upload-file --config=$HOME/glacier.cfg --filename=/backups/databases/glacier/$ZipFileName || exit 1

# Delete after three mount
if [ -f /backups/databases/glacier/purge_glacier_tmp.log ]; then
  rm /backups/databases/glacier/purge_glacier_tmp.log || exit 1
fi
grep "CREATED.*day_"$filename".7z" /backups/databases/glacier/glacier_do_no_delete.log > /backups/databases/glacier/existing.log
if [ ! $? -eq 0 ]; then
  exit 1
fi
backupsCount=`cat /backups/databases/glacier/existing.log | wc -l `
if [ ! $? -eq 0 ]; then
  exit 1
fi
echo "backupsCount: "$backupsCount
# If  Created backups more than three mount storage
if [ "$backupsCount" -ge "$storageBackups" ] ; then
  deleteBackups=$(($backupsCount-$storageBackups))
  echo "deleteBackups: "$deleteBackups
  head -n $deleteBackups /backups/databases/glacier/existing.log > /backups/databases/glacier/purge_glacier_tmp.log
  if [ ! $? -eq 0 ]; then
    exit 1
  fi
fi

# IF needed delete old backups
if [ -f /backups/databases/glacier/purge_glacier_tmp.log ]; then
  echo "deleting old backups:"
  cat /backups/databases/glacier/purge_glacier_tmp.log
  # Delete from file if first day of mounth
  sed -i '/_01_01/d' /backups/databases/glacier/purge_glacier_tmp.log
  sed -i '/_02_01/d' /backups/databases/glacier/purge_glacier_tmp.log
  sed -i '/_03_01/d' /backups/databases/glacier/purge_glacier_tmp.log
  sed -i '/_04_01/d' /backups/databases/glacier/purge_glacier_tmp.log
  sed -i '/_05_01/d' /backups/databases/glacier/purge_glacier_tmp.log
  sed -i '/_06_01/d' /backups/databases/glacier/purge_glacier_tmp.log
  sed -i '/_07_01/d' /backups/databases/glacier/purge_glacier_tmp.log
  sed -i '/_08_01/d' /backups/databases/glacier/purge_glacier_tmp.log
  sed -i '/_09_01/d' /backups/databases/glacier/purge_glacier_tmp.log
  sed -i '/_10_01/d' /backups/databases/glacier/purge_glacier_tmp.log
  sed -i '/_11_01/d' /backups/databases/glacier/purge_glacier_tmp.log
  sed -i '/_12_01/d' /backups/databases/glacier/purge_glacier_tmp.log
  #Delete from file if Monday and four mount ago
  cat /backups/databases/glacier/purge_glacier_tmp.log | grep Monday  | tail -4 > /backups/databases/glacier/monday_delete.log
  cat /backups/databases/glacier/purge_glacier_tmp.log | grep -v -f /backups/databases/glacier/monday_delete.log > /backups/databases/glacier/purge_glacier.log
  purgeGlacierLines=`cat  /backups/databases/glacier/purge_glacier.log | wc -l`
  if [ "$purgeGlacierLines" -gt 0 ]; then
    echo "*** next files will be deleted from glacier"
    cut -f 8 /backups/databases/glacier/purge_glacier.log
    mtglacier purge-vault --config=$HOME/glacier.cfg --journal=/backups/databases/glacier/purge_glacier.log || exit 1
  fi
fi

#rm -f /backups/databases/glacier/purge_glacier.log

#echo "*** backup glacier joarnal to aw1"
#rsync --compress --progress --inplace /backups/databases/glacier/glacier_do_no_delete.log aw1.awardwallet.com:/home/veresch/glacier_do_no_delete$(date +%Y_%m_%d)'.log' || exit 1
echo "done"


