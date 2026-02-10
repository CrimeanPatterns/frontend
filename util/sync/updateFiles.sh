#!/bin/bash -xv
set -euxo pipefail

PROJECT=frontend

FILES=$1
LIST="/tmp/"$PROJECT"_update_list"
echo $FILES | sed "s/ /\n/" >$LIST


git pull origin master
git submodule foreach "git pull origin master; git checkout master"
echo $FILES
ssh files.awardwallet.com "rm -Rf /var/share/"$PROJECT"/*"
rsync --recursive --delete --max-delete=50 --compress --cvs-exclude --verbose --files-from=$LIST . files.awardwallet.com:/var/share/$PROJECT/