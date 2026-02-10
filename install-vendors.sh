#!/usr/bin/env bash

set -x

yarn
yarn run grunt --gruntfile desktopGrunt.js

composer install "$@"

app/console assets:install web
app/console bazinga:js-translation:dump --merge-domains --format=js web/assets
app/console fos:js-routing:dump --target=web/js/routes.json --format=json
app/console fos:js-routing:dump --target=web/js/routes.js
app/console elfinder:install --docroot=web
node_modules/.bin/encore dev
