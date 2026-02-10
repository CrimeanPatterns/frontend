#!/bin/sh

cd `dirname "$0"`/../..
MPREF=`pwd`
cd "$MPREF/util/backupScripts"

export SYMFONY_ENV=prod
export SYMFONY_DEBUG=0
mysql='mysql -h localhost -u root'
mdump='mysqldump -h localhost -u root'
if ! rootDbPass=`sed '/^password/!d;s/^.*=[ \t]*//' ~/.my.cnf 2>/dev/null`; then
    echo "Can't find ~/.my.cnf file!"
    exit 1
fi

getParam() {
    [ -n "$1" ] && res=`sed '/^[ \t]*'"$1"'/!d;s/^.*'"$1"':[ \t]*//' "$MPREF/app/config/parameters.yml"`
    [ -z "$res" ] && echo "$1 parameter is not exists or empty" && exit 1 || echo "$res"
}

