#!/bin/bash -xv

set -euxo pipefail

for config in juicymiles-ra.cnf,D=ra loyalty.cnf,D=wsdlawardwallet email.cnf,D=wsdlawardwallet email-eu.cnf,D=email email-beta.cnf,D=wsdlawardwallet ra-awardwallet.cnf,D=loyalty
do
  set +e
  # needs: grant REPLICATION CLIENT on *.* to user_name;
  pt-table-sync F=/var/lib/jenkins/vars/mysql/frontend.cnf,D=awardwallet,t=TpoHotel F=/var/lib/jenkins/vars/mysql/$config --verbose --execute
  exitCode=$?
  set -e
  #STATUS  MEANING
  #======  =======================================================
  #0       Success.
  #1       Internal error.
  #2       At least one table differed on the destination.
  #3       Combination of 1 and 2.
  if [ $exitCode -ne 0 ] && [ $exitCode -ne 2 ] && [ $exitCode -ne 3 ]
  then
    echo "sync failed: $exitCode"
    exit $exitCode
  fi
done

for config in juicymiles-ra.cnf,D=ra loyalty.cnf,D=wsdlawardwallet ra-awardwallet.cnf,D=loyalty
do
  set +e
  # needs: grant REPLICATION CLIENT on *.* to user_name;
  pt-table-sync F=/var/lib/jenkins/vars/mysql/frontend.cnf,D=awardwallet,t=Fingerprint F=/var/lib/jenkins/vars/mysql/$config --verbose --execute --transaction
  exitCode=$?
  set -e
  #STATUS  MEANING
  #======  =======================================================
  #0       Success.
  #1       Internal error.
  #2       At least one table differed on the destination.
  #3       Combination of 1 and 2.
  if [ $exitCode -ne 0 ] && [ $exitCode -ne 2 ] && [ $exitCode -ne 3 ]
  then
    echo "sync failed: $exitCode"
    exit $exitCode
  fi
done

