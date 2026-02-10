#!/bin/bash -v

set -euxo pipefail

cd /www/awardwallet
rm -Rf app/cache/*

if [[ "$RUN_COMPOSER_INSTALL" == "true" ]]; then
  ./install-vendors.sh --prefer-source 2>&1 | grep -v Ambiguous
fi
php app/console doctrine:migrations:migrate --no-interaction

if [[ "$RUN_GROUPS" == *"acceptance"* ]]; then
    grunt --gruntfile desktopGrunt.js
fi

mkdir -p tests/_log
sudo chown user:user tests/_log
sudo chown user:user app/logs
rm -f tests/acceptance/*Guy.php
rm -f tests/functional/*Guy.php
rm -f tests/functional-symfony/*Guy.php
rm -f tests/unit/*Guy.php
vendor/bin/codecept build

app/console cache:warmup --env=staging --no-debug
app/console cache:warmup --env=codeception --no-debug

mysql -e "update AbBookerInfo set SmtpServer = 'mail' where SmtpServer = 'localhost'";

if [[ ( "$RUN_GROUPS" == " -vvv" || -z "$RUN_GROUPS" ) &&  -n "$TEST_NAME" ]]; then
    vendor/bin/codecept run --no-colors --no-interaction "$TEST_NAME"
else
    vendor/bin/codecept run --no-colors --no-interaction $RUN_GROUPS
fi

