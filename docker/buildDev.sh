#!/usr/bin/env bash

set -euxo pipefail

cd `dirname "$0"`
cd ../

docker-compose rm -v -s -f

rm -f app/config/parameters.yml
rm -f app/config/local_*.yml
cp ../shared/configs/parameters.yml app/config/

docker-compose pull

docker-compose run --rm -e GOSU=on php bash -c '
    set -euxo pipefail
    umask 0002
    whoami
    echo $HOME
    md5sum /home/user/.npmrc
    rm -Rf app/cache/*
    COMPOSER_PROCESS_TIMEOUT=600 ./install-vendors.sh --prefer-source 2>&1 | grep -v Ambiguous
    grunt --no-color --gruntfile desktopGrunt.js
    rm -Rf app/cache/*
    rm -Rf app/logs/*
    ASSETS_VERSION=$(date +%s)
    grunt --no-color --assets_version=$ASSETS_VERSION --gruntfile desktopGrunt.js
    docker/updateYml.php app/config/parameters.yml parameters/assets_version $ASSETS_VERSION
    rm -Rf app/cache/*
    app/console cache:warmup
    rm -Rf web/m/*
    grunt --no-color build:mobile:templates
    grunt --no-color build:mobile:release:git
    rm -Rf web/css/*
    rm -Rf web/js/*-*.js
    app/console doctrine:migrations:migrate --no-interaction -vv
    app/console rabbitmq:setup-fabric
    chown user:user app/logs
    exec 100500<>/dev/tcp/memcached/11211
    printf flush_all >&100500
    exec 100500<&-
'
docker-compose up -d php

