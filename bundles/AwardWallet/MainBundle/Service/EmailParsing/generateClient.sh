#!/usr/bin/env bash

set -euxo pipefail

rm -Rf tmp
mkdir tmp
docker run -ti --rm --link email_nginx_1:email.docker --network awardwallet -v ${PWD}:/local swaggerapi/swagger-codegen-cli:2.4.12 generate -i http://email.docker/email/swagger.json -l php -o /local/tmp -variableNamingConvention=camelCase -c /local/options.json
rm -Rf Client/*
mv tmp/SwaggerClient-php/lib/* Client/
rm -Rf tmp
# generated with: git diff --relative >fixes.patch
patch -i fixes.patch -p1 -s
git add Client