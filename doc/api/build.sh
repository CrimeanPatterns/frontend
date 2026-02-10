#!/usr/bin/env bash

set -euxo pipefail

rm -Rf tmp
mkdir -p tmp
php swagger2html/run -s export.yml -d tmp -p
cp tmp/index.html ../../bundles/AwardWallet/MainBundle/Resources/views/ApiDocumentation/account_access.html.twig