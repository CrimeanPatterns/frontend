#!/bin/sh

cd `dirname "$0"`
. ./common.sh || exit 1

#dbPass=`getParam database_password`
#dbUser=`getParam database_user`
db=`getParam database_name`
#echo 'USE '$db'; FLUSH TABLES WITH READ LOCK;' | mysql --user=$dbUser --password=$dbPass --host=localhost
$mysql --database=$db < "/www/backups/providerTables.sql"
#echo 'USE '$db'; UNLOCK TABLES;' | mysql --user=$dbUser --password=$dbPass --host=localhost
echo "restore completed"
