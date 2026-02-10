#!/usr/bin/env bash

set -euxo pipefail

#curl https://generator.swagger.io/api/gen/clients/php | jq "."

#curl -X POST --header 'Content-Type: application/json' --header 'Accept: application/json' -d '{ \
# "swaggerUrl": "https://developer.flightstats.com/swagger/spec/Trips-API.json",  \
# "options": {"invokerPackage": "AwardWallet\\MainBundle\\Service\\FlightStats\\TripAlerts\\API", "variableNamingConvention": "camelCase"} \
# }' 'https://generator.swagger.io/api/gen/clients/php'

docker run -ti --rm -v ${PWD}:/local swaggerapi/swagger-codegen-cli generate -i https://developer.flightstats.com/swagger/spec/Trips-API.json -l php -o /local/tmp -variableNamingConvention=camelCase -c /local/options.json

rm -Rf API/*
mv tmp/SwaggerClient-php/lib/* API/
rm -Rf tmp
patch -i tripImportResponse.patch API/Model/TripImportResponse.php
patch -i tripImportApi.patch API/Api/TripImportApi.php
patch -i objectSerializer.patch API/ObjectSerializer.php
git add API