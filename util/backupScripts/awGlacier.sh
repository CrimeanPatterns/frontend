#!/bin/bash
#

case $1 in
        --list)
                FUNC="f_list"
        ;;
        --restore)
                FUNC="f_restore"
        ;;
        --download)
                FUNC="f_download"
        ;;
        --download-list)
                FUNC="f_download_list"
        ;;
        --help)
                FUNC="f_help"
        ;;
        *)
                FUNC="f_help"
        ;;
esac

f_list() {
  umask 0002
  echo "List files in Amazon Glacier"
  cat /backups/databases/glacier/glacier_do_no_delete.log | cut -f 8
}

f_restore() {
  umask 0002
  echo "This files located in Amazon Glacier"
  cat  /backups/databases/glacier/glacier_do_no_delete.log > /backups/databases/glacier/glacier_restore_tmp.log
  grep -n CREATED /backups/databases/glacier/glacier_restore_tmp.log | cut -f 1,8 | sed -e ':a' -e 'N' -e '$!ba' -e 's/:B/ /g'

  echo "Please select files for restoring, use space for few backups"
  echo "Example: '1 2 56' or '45'"
  echo "Enter backups numbers"
  read string
  case $string in
      ''|*[!0-9\ ]*) flag=false ;;
      *) flag=true ;;
  esac
  if [ $flag = "false" ]
  then
      echo "Please check syntax"
      return
  fi
  backupCount=`grep -n CREATED /backups/databases/glacier/glacier_restore_tmp.log | wc -l`
  echo $string | grep -Eo '[0-9]+' | while read -r number ; do
      if [ "$backupCount" -ge "$number" ]; then
        cat /backups/databases/glacier/glacier_restore_tmp.log | head -n $number | tail -n 1 >> /backups/databases/glacier/glacier_restore_tmp2.log
      fi
  done

  if ! [ -f /backups/databases/glacier/glacier_restore_tmp2.log ]; then
    return
  fi

  awk '!_[$0]++'  /backups/databases/glacier/glacier_restore_tmp2.log > /backups/databases/glacier/glacier_restore.log

  rm -f /backups/databases/glacier/glacier_restore_tmp.log
  rm -f /backups/databases/glacier/glacier_restore_tmp2.log

  echo "Next file prepare for restore"
  cut -f 8 /backups/databases/glacier/glacier_restore.log

  maxNumberOfFiles=`cat /backups/databases/glacier/glacier_restore.log | wc -l`

  mtglacier restore --config=$HOME/glacier.cfg --journal=/backups/databases/glacier/glacier_restore.log --max-number-of-files=$maxNumberOfFiles || exit 1

  cp /backups/databases/glacier/glacier_restore.log /backups/databases/glacier/glacier_restore_completed.log

  echo "Please Wait 4+ hours for Amazon Glacier to complete archive retrieval..."
}

f_download() {
  umask 0002
  echo "Next files will bee download from Glacier"
  cat /backups/databases/glacier/glacier_restore_completed.log | cut -f 8
  trickle -d 250 mtglacier restore-completed --config=$HOME/glacier.cfg --journal=/backups/databases/glacier/glacier_restore_completed.log --concurrency=1
}

f_download_list() {
  umask 0002
  echo "Next files will bee download from Glacier"
  cat /backups/databases/glacier/glacier_restore_completed.log | cut -f 8
}


f_help() {
        echo "  awGlacier.sh options"
        echo "          --list           List all file in Amazon Glacier"
        echo "          --restore        Prepare backups to download"
        echo "          --download       Download prepared backups"
        echo "          --download-list  Show list of prepared backups for download"
        echo "          --help           This help"
}

$FUNC

