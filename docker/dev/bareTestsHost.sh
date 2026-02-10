#!/usr/bin/env bash
set -euv pipefail

echo "$DOCKER_PASSWORD" | docker login -u "$DOCKER_USERNAME" --password-stdin docker.awardwallet.com
docker-compose --no-ansi pull -q
docker network create awardwallet
docker-compose up -d
# TODO: add gitlab access token
# TODO: add gitub access token
docker-compose exec -T php /usr/local/bin/gosu user /www/awardwallet/docker/dev/bareTestsContainer.sh
