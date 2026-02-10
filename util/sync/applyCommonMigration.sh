#!/bin/bash
set -euxo pipefail

SQL=$1

for CONFIG in wsdltest loyalty juicymiles-ra email email-eu ra-awardwallet
do
	mysql --defaults-file=~/vars/mysql/$CONFIG.cnf -e "$SQL"
done


