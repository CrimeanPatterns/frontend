#!/usr/bin/env bash

set -euxo pipefail
cat carnival-tx.sql | mysql --defaults-file=/var/lib/jenkins/vars/mysql/frontend.cnf -t >~/carnival-tx.log
cat norwegian-tx.sql | mysql --defaults-file=/var/lib/jenkins/vars/mysql/frontend.cnf -t >~/norwegian-tx.log
cat royal-tx.sql | mysql --defaults-file=/var/lib/jenkins/vars/mysql/frontend.cnf -t >~/royal-tx.log
