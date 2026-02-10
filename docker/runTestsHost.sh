#!/bin/bash -v

set -euxo pipefail

cd /www/awardwallet
docker-compose down || true
git reset --hard
git clean -df
git fetch origin
git checkout $BRANCH
git reset --hard origin/$BRANCH
git submodule foreach '
    git reset --hard &&
    git clean -df &&
    git fetch origin &&
    TMPLOG=$(git checkout '$BRANCH' || git checkout master || exit 1) &&
    TMPLOG=$(git reset --hard origin/'$BRANCH' || git reset --hard origin/master || exit 1)'

docker-compose rm -v mysql mysql-data
docker volume rm awardwallet_mysql-data || true
docker-compose pull
docker-compose up -d
docker rmi $(docker images -f dangling=true -q) || true
docker volume prune --force
# create dirs, or docker will create it with root owner when mounting from docker-compose=tests.yml
docker-compose exec -T php /usr/local/bin/bin/gosu user /www/awardwallet/docker/runTestsContainer.sh
