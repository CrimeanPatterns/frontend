#!/usr/bin/php
<?php

declare(ticks=1);

include_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/testsCommon.php';

use AwardWallet\MainBundle\Security\SiegeModeDetector;

chdir('/www/awardwallet');

verbosePassthru('php app/console doctrine:migrations:migrate --no-interaction -vv');

if (getJenkinsFlag('frontendAcceptance')) {
    verbosePassthru('SYMFONY_ENV=acceptance SYMFONY_DEBUG=0 grunt --assets_version=1 --gruntfile desktopGrunt.js');
    verbosePassthru('SYMFONY_ENV=acceptance SYMFONY_DEBUG=0 app/console cache:warmup');
}

foreach ([
    'sudo chown user:user app/logs',
    'sudo chown user:user tests/_log',
    'rm -Rf src',
    'rm -f tests/acceptance/*Guy.php',
    'rm -f tests/functional/*Guy.php',
    'rm -f tests/functional-symfony/*Guy.php',
    'rm -f tests/unit/*Guy.php',
    'vendor/bin/codecept build',
] as $command) {
    verbosePassthru($command);
}

// verbosePassthru('app/console cache:warmup --env=staging --no-debug');
verbosePassthru('app/console cache:warmup --env=codeception --no-debug');

// hide pending popup for acceptance tests
verbosePassthru('mysql -e "delete from Account where UserID = 7 and State = -1"');
verbosePassthru('mysql -e "update AbBookerInfo set SmtpServer = \'mail\' where SmtpServer = \'localhost\'"');
verbosePassthru('mysql -e "delete from Param where Name like \'push_copy%\'"');
verbosePassthru('mysql -e "update Provider set Login2Caption = \'Login2\', Login2Required = 0 where ProviderID = 636"');
verbosePassthru('mysql -e "insert into Param(Name, Val) values(\'' . SiegeModeDetector::SIEGE_MODE_PARAM_NAME . '\', \'0\') on duplicate key update Val=\'0\'"');
