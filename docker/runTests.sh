#!/bin/bash -v

set -euxo pipefail

branch_folder="${branch//[^[:alnum:]]/_}"

mkdir -p tests/_log || true
cp /www/rerun-failed-storage/$branch_folder/fail-rerun-* tests/_log/ || true
sudo chown -R user:user tests/playwright

git submodule init
git submodule foreach '
    git reset --hard &&
    git clean -df &&
    git fetch origin &&
    TMPLOG=$(git checkout '$branch' || git checkout master || exit 1) &&
    TMPLOG=$(git reset --hard origin/'$branch' || git reset --hard origin/master || exit 1)'
# docker-compose --no-ansi pull -q
COMPOSE_HTTP_TIMEOUT=120 docker-compose --no-ansi up -d

case $vendors in
 install_all_vendors)
   docker-compose exec -T php bash -c "set -euxo pipefail; SYMFONY_ENV=codeception SYMFONY_DEBUG=0 /usr/local/bin/gosu user ./install-vendors.sh --prefer-source 2>&1 | grep -v Ambiguous";;
 just_composer_install)
   docker-compose exec -T php bash -c "set -euxo pipefail; SYMFONY_ENV=codeception SYMFONY_DEBUG=0 /usr/local/bin/gosu user composer install --prefer-source 2>&1 | grep -v Ambiguous";;
 none)
   docker-compose exec -T php bash -c "set -euxo pipefail; SYMFONY_ENV=codeception SYMFONY_DEBUG=0 /usr/local/bin/gosu user composer run-script build-params";;
esac

docker-compose exec -T php /usr/local/bin/gosu user /www/awardwallet/docker/prepareTestEnv.php
set +e
PLAYWRIGHT_TESTS_CODE=0
if [[ "$frontendAcceptance" == "true" ]]; then
  docker-compose exec -T php bash -c "set -euxo pipefail; SYMFONY_ENV=codeception SYMFONY_DEBUG=0 /usr/local/bin/gosu user vendor/bin/codecept run tests/playwright/tests/" \
  && docker-compose run  -T --rm --entrypoint bash playwright -c "npm ci && npx -y playwright test --reporter=dot"
  PLAYWRIGHT_TESTS_CODE=$?
fi
docker-compose exec -T php /usr/local/bin/gosu user /www/awardwallet/docker/runTests1.php
TESTS_CODE=$?
set -e
if [[ "$PLAYWRIGHT_TESTS_CODE" != "0" && "$TESTS_CODE" == "0" ]]; then
  TESTS_CODE=$PLAYWRIGHT_TESTS_CODE
fi

mkdir /www/rerun-failed-storage/$branch_folder || true
rm /www/rerun-failed-storage/$branch_folder/fail-rerun-* || true
cp tests/_log/fail-rerun-* /www/rerun-failed-storage/$branch_folder || true

exit $TESTS_CODE