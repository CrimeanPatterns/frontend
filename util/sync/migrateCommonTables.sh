#!/usr/bin/env bash

set -euxo pipefail

sql=$1

configs=(wsdltest juicymiles-ra ra-awardwallet loyalty email email-eu)
for config in "${configs[@]}"
do
  echo $sql | mysql --defaults-file=/var/lib/jenkins/vars/mysql/$config.cnf
done