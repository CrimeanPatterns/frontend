#!/usr/bin/env bash
set -euxo pipefail

mysql --defaults-file=~/vars/mysql/vendor.cnf -e "drop table if exists PasswordHashNew"
mysqldump --defaults-file=~/vars/mysql/vendor.cnf  --no-data awardwallet_vendor PasswordHash | sed 's/PasswordHash/PasswordHashNew/' | mysql --defaults-file=~/vars/mysql/vendor.cnf
mysql --defaults-file=~/vars/mysql/vendor.cnf -e "load data local infile '/tmp/pwned-passwords-sha1-ordered-by-count-v4.txt' into table PasswordHashNew columns terminated by ':'"
mysql --defaults-file=~/vars/mysql/vendor.cnf -e "RENAME TABLE PasswordHash TO PasswordHashOld, PasswordHashNew To PasswordHash"
mysql --defaults-file=~/vars/mysql/vendor.cnf -e "DROP TABLE PasswordHashOld"
