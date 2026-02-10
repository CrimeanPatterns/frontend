#!/usr/bin/env bash
set -euv pipefail

composer global require hirak/prestissimo
# TODO: add gitlab access token
# TODO: add gitub access token
composer install --prefer-dist --no-progress --no-scripts
exec /www/awardwallet/docker/runTests1.php